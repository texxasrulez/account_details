<?php
/**
 * DavDiscoveryService - strict RFC discovery with robust URL joining.
 * Logging made quiet with configurable levels:
 *   $config['dav_log_level'] = 'off' | 'error' | 'warn' | 'info' | 'debug'  (default: 'error')
 */

if (!function_exists('__dav_level_num')) {
    function __dav_level_num($lvl) {
        switch (strtolower((string)$lvl)) {
            case 'error': return 1;
            case 'warn':  return 2;
            case 'info':  return 3;
            case 'debug': return 4;
            case 'off':   return 5;
            default:      return 1; // error
        }
    }
}
if (!function_exists('__dav_guess_level')) {
    function __dav_guess_level($tag) {
        $t = strtolower((string)$tag);
        if (strpos($t, 'error') !== false) return 'error';
        if (strpos($t, 'warn')  !== false) return 'warn';
        // noisy tags we only want when info/debug
        if (preg_match('#^(auth:|dav:|rfc:|discover:)#', $t)) return 'info';
        return 'debug';
    }
}
if (!function_exists('__dav_should_log')) {
    function __dav_should_log($tag) {
        $level = __dav_guess_level($tag);
        $threshold = 'error';
        if (class_exists('rcmail')) {
            $rc = rcmail::get_instance();
            if ($rc && $rc->config) {
                $cfg = (string)$rc->config->get('dav_log_level', 'error');
                if ($cfg !== '') $threshold = $cfg;
            }
        }
        return __dav_level_num($level) <= __dav_level_num($threshold);
    }
}
if (!function_exists('__dav_log')) {
    function __dav_log($tag, $payload = array()) {
        if (!__dav_should_log($tag)) return;
        if (!is_array($payload)) $payload = array('msg' => (string)$payload);
        error_log('[DAVDEBUG] ' . $tag . ' ' . json_encode($payload));
    }
}

__dav_log('bootstrap', array('file' => __FILE__));

if (!class_exists('rcmail') && file_exists(__DIR__ . '/../../program/include/iniset.php')) {
    @include_once __DIR__ . '/../../program/include/iniset.php';
}

class DavDiscoveryServiceCore
{
    private $rc;
    private $log_threshold;

    public function __construct()
    {
        $this->rc = class_exists('rcmail') ? rcmail::get_instance() : null;
        $this->log_threshold = 'error';
        if ($this->rc && $this->rc->config) {
            $this->log_threshold = (string)$this->rc->config->get('dav_log_level', 'error');
        }
        $this->log('init', array(
            'rc_user_id' => ($this->rc && $this->rc->user) ? (int)$this->rc->user->ID : null,
            'debug_on'   => ($this->log_threshold === 'debug'),
            'db_class'   => ($this->rc && $this->rc->db) ? get_class($this->rc->db) : null
        ));
    }

    public function discover()
    {
        $user = $this->currentUserIdentity();
        $this->log('discover:start', array('user' => $user));

        $cal = array();
        $card = array();

        $calroot = $this->wellKnown('caldav');
        if ($calroot) {
            $cal = $this->discoverByRfc($calroot['url'], 'caldav');
            if (empty($cal)) $cal[] = $calroot;
        }

        $cardroot = $this->wellKnown('carddav');
        if ($cardroot) {
            $card = $this->discoverByRfc($cardroot['url'], 'carddav');
        }

        $this->log('discover:end', array('cal_count' => count($cal), 'card_count' => count($card)));
        return array('caldav' => $cal, 'carddav' => $card);
    }

    /** ---------- Basic URL seed ---------- */
    private function wellKnown($type)
    {
        $base = '';
        if ($this->rc && $this->rc->config) {
            $base = (string)$this->rc->config->get('dav_base_url', '');
            $base = rtrim($base, '/');
        }
        if ($base === '') {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
            if ($host === '') return null;
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $host;
        }
        $url = $base . '/.well-known/' . $type;
        $this->log('rfc:well_known', array('input' => $url, 'target' => $url, 'status' => 0));
        return array('name' => strtoupper($type) . ' Root', 'url' => $url);
    }

    /** ---------- RFC discovery path ---------- */

    private function discoverByRfc($rootUrl, $type)
    {
        // Depth:0 at .well-known target
        $r = $this->httpPropfind($rootUrl, 0);
        $code = isset($r['code']) ? (int)$r['code'] : 0;
        $body = isset($r['body']) ? $r['body'] : false;
        $headers = isset($r['headers']) ? $r['headers'] : array();

        if ($code >= 300 && $code < 400 && isset($headers['location'][0])) {
            $loc = $this->joinUrl($rootUrl, $headers['location'][0]);
            $this->log('dav:propfind:redirect', array('from' => $rootUrl, 'to' => $loc, 'code' => $code));
            $rootUrl = $loc;
            $r = $this->httpPropfind($rootUrl, 0);
            $code = (int)$r['code']; $body = $r['body']; $headers = isset($r['headers']) ? $r['headers'] : array();
        }

        $home = $this->extractHomeFromBody($rootUrl, $body, $type);
        if (!$home) {
            $home = $this->principalHome($rootUrl, $type);
        }
        if (!$home) return array();

        $this->log('dav:propfind:retry_home', array('home' => $home, 'type' => $type));

        // Depth:1 on home-set
        $r2 = $this->httpPropfind($home, 1);
        if (!isset($r2['code']) || $r2['code'] >= 400 || $r2['body'] === false) return array();
        return $this->parseCollections($home, $r2['body'], $type);
    }

    private function principalHome($baseUrl, $type)
    {
        $r1 = $this->httpPropfind($baseUrl, 0);
        $this->log('dav:principal:base', array('code' => $r1['code'], 'type' => $type));

        $principal = '';
        if ($r1['code'] >= 200 && $r1['body']) {
            $dom = new DOMDocument();
            if (@$dom->loadXML((string)$r1['body'])) {
                $xp = new DOMXPath($dom);
                $xp->registerNamespace('d', 'DAV:');
                $node = $xp->query('//d:current-user-principal/d:href')->item(0);
                if ($node) $principal = trim((string)$node->textContent);
            }
        }
        if ($principal === '') return null;

        $principal = $this->joinUrl($baseUrl, $principal);
        $this->log('dav:principal:href', array('href' => $principal));

        $r2 = $this->httpPropfind($principal, 0);
        $home = '';
        if ($r2['code'] >= 200 && $r2['body']) {
            $home = $this->extractHomeFromBody($baseUrl, $r2['body'], $type);
        }
        if ($home) {
            $this->log('dav:principal:home', array('href' => $home, 'type' => $type));
            return $home;
        }
        return null;
    }

    private function extractHomeFromBody($baseUrl, $xmlBody, $type)
    {
        if (!$xmlBody) return null;
        $dom = new DOMDocument();
        if (!@$dom->loadXML((string)$xmlBody)) return null;
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('d', 'DAV:');
        $xp->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xp->registerNamespace('ab', 'urn:ietf:params:xml:ns:carddav');

        $node = ($type === 'caldav')
            ? $xp->query('//cal:calendar-home-set/d:href')->item(0)
            : $xp->query('//ab:addressbook-home-set/d:href')->item(0);
        if (!$node) return null;

        $href = trim((string)$node->textContent);
        return $this->joinUrl($baseUrl, $href);
    }

    private function parseCollections($root, $xmlBody, $type)
    {
        $out = array();
        $dom = new DOMDocument();
        if (!@$dom->loadXML((string)$xmlBody)) { $this->log('dav:propfind:parse_error', array('error' => 'loadXML failed')); return array(); }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('d', 'DAV:');
        $xp->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xp->registerNamespace('ab', 'urn:ietf:params:xml:ns:carddav');

        $responses = $xp->query('//d:response');
        for ($i = 0; $i < $responses->length; $i++) {
            $resNode = $responses->item($i);
            $hrefNode = $xp->query('d:href', $resNode)->item(0);
            if (!$hrefNode) continue;
            $href = (string)$hrefNode->textContent;

            $isWanted = ($type === 'caldav')
                ? ($xp->query('.//d:resourcetype/cal:calendar', $resNode)->length > 0)
                : ($xp->query('.//d:resourcetype/ab:addressbook', $resNode)->length > 0);
            if (!$isWanted) continue;

            $nameNode = $xp->query('.//d:propstat/d:prop/d:displayname', $resNode)->item(0);
            $name = $nameNode ? (string)$nameNode->textContent : trim(basename($href), '/');

            $out[] = array('name' => $name, 'url' => $this->joinUrl($root, $href));
        }

        $this->log('dav:propfind:found', array('count' => count($out), 'type' => $type));
        return $this->dedupe($out);
    }

    /** ---------- URL helpers ---------- */

    private function joinUrl($base, $href)
    {
        $href = (string)$href;
        if ($href === '') return $base;
        if (preg_match('#^https?://#i', $href)) return $href;

        $p = @parse_url($base);
        if (!$p || !isset($p['scheme']) || !isset($p['host'])) return $href;
        $origin = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? (':' . $p['port']) : '');

        if ($href[0] === '/') {
            // server-absolute; ignore base path to avoid /dav//dav duplication
            return rtrim($origin, '/') . $href;
        }

        // relative; join with base *directory*
        $basePath = isset($p['path']) ? $p['path'] : '/';
        if ($basePath === '') $basePath = '/';
        if (substr($basePath, -1) !== '/') {
            $basePath = substr($basePath, 0, strrpos($basePath, '/') + 1);
        }
        return rtrim($origin, '/') . '/' . ltrim($basePath . $href, '/');
    }

    /** ---------- HTTP + auth ---------- */

    private function httpPropfind($url, $depth)
    {
        $xml = '<?xml version="1.0" encoding="utf-8" ?>'
             . '<d:propfind xmlns:d="DAV:" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:ab="urn:ietf:params:xml:ns:carddav">'
             . '<d:prop>'
             . '<d:displayname/><d:resourcetype/><d:current-user-principal/>'
             . '<cal:calendar-home-set/><ab:addressbook-home-set/>'
             . '</d:prop>'
             . '</d:propfind>';

        list($user, $pass, $local) = $this->getHttpBasicCreds();

        $code = 0; $body = false; $headers = array();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $code = 0; $body = false; $headers = array();

            $exec = function($u) use ($url, $depth, $xml, $pass, &$code, &$body, &$headers) {
                $code = 0; $body = false; $headers = array();
                if (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Depth: ' . (string)$depth,
                        'Content-Type: application/xml',
                        'User-Agent: Roundcube-AccountDetails-DAV/1.0',
                        'Accept: */*'
                    ));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
                    if ($u && $pass) {
                        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                        curl_setopt($ch, CURLOPT_USERPWD, $u . ':' . $pass);
                    }
                    $resp = curl_exec($ch);
                    if ($resp !== false) {
                        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                        $raw_headers = substr($resp, 0, $header_size);
                        $body        = substr($resp, $header_size);
                        $code        = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $lines = preg_split('/\r?\n/', (string)$raw_headers);
                        foreach ($lines as $line) {
                            $pos = strpos($line, ':');
                            if ($pos !== false) {
                                $hname = strtolower(trim(substr($line, 0, $pos)));
                                $hval  = trim(substr($line, $pos + 1));
                                if (!isset($headers[$hname])) $headers[$hname] = array();
                                $headers[$hname][] = $hval;
                            }
                        }
                    }
                    curl_close($ch);
                } else {
                    $hdrs = "Depth: " . (string)$depth . "\r\n"
                          . "Content-Type: application/xml\r\n"
                          . "User-Agent: Roundcube-AccountDetails-DAV/1.0\r\n"
                          . "Accept: */*\r\n";
                    if ($u && $pass) $hdrs .= "Authorization: Basic " . base64_encode($u . ':' . $pass) . "\r\n";
                    $ctx = stream_context_create(array('http' => array('method'=>'PROPFIND','header'=>$hdrs,'content'=>$xml,'ignore_errors'=>true,'timeout'=>12)));
                    $resp = @file_get_contents($url, false, $ctx);
                    $body = $resp;
                    if (isset($http_response_header) && is_array($http_response_header)) {
                        foreach ($http_response_header as $h) {
                            if (preg_match('#HTTP/\d\.\d\s+(\d{3})#', $h, $m)) $code = (int)$m[1];
                            $pos = strpos($h, ':');
                            if ($pos !== false) {
                                $hname = strtolower(trim(substr($h, 0, $pos)));
                                $hval  = trim(substr($h, $pos + 1));
                                if (!isset($headers[$hname])) $headers[$hname] = array();
                                $headers[$hname][] = $hval;
                            }
                        }
                    }
                }
            };

            $exec($user);
            if ($code == 401 && $user && strpos($user, '@') !== false && $local) {
                $this->log('auth:alt_try', array('switch_to' => $local));
                $exec($local);
            }

            if ($code == 429 || $code == 503) {
                usleep((int)(400000 * $attempt)); // 0.4s, 0.8s, 1.2s
                continue;
            }
            break;
        }

        return array('code' => $code, 'body' => $body, 'headers' => $headers);
    }

    private function getHttpBasicCreds()
    {
        $rcU = $this->rcCurrentUsername();
        $rcP = $this->rcCurrentPassword();
        $local = $this->rcUserLocalPart($rcU);
        $domain = $this->rcUserDomainPart($rcU);

        $u_src = ''; $p_src = '';

        if ($this->rc && $this->rc->config) {
            $cfgU = (string)$this->rc->config->get('calendar_caldav_user', '');
            $cfgP = (string)$this->rc->config->get('calendar_caldav_pass', '');
            if ($cfgU !== '') $u_src = str_replace(array('%u','%U','%email','%username','%l','%local','%d','%domain'), array($rcU,$rcU,$rcU,$rcU,$local,$local,$domain,$domain), $cfgU);
            if ($cfgP !== '') $p_src = str_replace(array('%p','%P','%pass','%password'), array($rcP,$rcP,$rcP,$rcP), $cfgP);

            if ($u_src === '' || $p_src === '') {
                $cfgU = (string)$this->rc->config->get('dav_basic_user', '');
                $cfgP = (string)$this->rc->config->get('dav_basic_pass', '');
                if ($cfgU !== '') $u_src = str_replace(array('%u','%U','%email','%username','%l','%local','%d','%domain'), array($rcU,$rcU,$rcU,$rcU,$local,$local,$domain,$domain), $cfgU);
                if ($cfgP !== '') $p_src = str_replace(array('%p','%P','%pass','%password'), array($rcP,$rcP,$rcP,$rcP), $cfgP);
            }
        }

        if ($u_src === '') $u_src = $rcU;
        if ($p_src === '') $p_src = $rcP;

        $this->log('auth:fallback', array('user' => $u_src, 'has_pass' => ($p_src !== '')));
        if ($u_src === '' || $p_src === '') return array(null, null, null);
        return array($u_src, $p_src, $local);
    }

    /** ---------- small helpers ---------- */
    private function currentUserIdentity()
    {
        $id = 0; $username = ''; $email = '';
        if ($this->rc && $this->rc->user) {
            $id = (int)$this->rc->user->ID;
            if (method_exists($this->rc->user, 'get_username')) $username = (string)$this->rc->user->get_username();
            $ident = method_exists($this->rc->user, 'get_identity') ? $this->rc->user->get_identity() : null;
            if (is_array($ident) && isset($ident['email'])) $email = (string)$ident['email'];
        }
        return array('id' => $id, 'username' => $username, 'email' => $email);
    }
    private function rcCurrentUsername() { $ident = $this->currentUserIdentity(); return (is_array($ident) && isset($ident['username'])) ? (string)$ident['username'] : ''; }
    private function rcCurrentPassword() {
        if ($this->rc) {
            if (method_exists($this->rc, 'get_user_password')) {
                $p = $this->rc->get_user_password();
                if (is_string($p) && $p !== '') return $p;
            }
            if (method_exists($this->rc, 'decrypt') && isset($_SESSION['password']) && is_string($_SESSION['password'])) {
                $p = @$this->rc->decrypt($_SESSION['password']);
                if (is_string($p) && $p !== '') return $p;
            }
        }
        if (isset($_SESSION['password']) && is_string($_SESSION['password']) && $_SESSION['password'] !== '') return (string)$_SESSION['password'];
        if (isset($_SESSION['imap_password']) && is_string($_SESSION['imap_password']) && $_SESSION['imap_password'] !== '') return (string)$_SESSION['imap_password'];
        return '';
    }
    private function rcUserLocalPart($username) { $at = strpos((string)$username, '@'); return ($at === false) ? (string)$username : substr((string)$username, 0, $at); }
    private function rcUserDomainPart($username) { $at = strpos((string)$username, '@'); return ($at === false) ? '' : substr((string)$username, $at + 1); }
    private function dedupe($rows)
    {
        $seen = array(); $out = array();
        for ($i = 0; $i < count($rows); $i++) {
            $k = $rows[$i]['name'] . "\0" . $rows[$i]['url'];
            if (!isset($seen[$k])) { $seen[$k] = true; $out[] = $rows[$i]; }
        }
        return $out;
    }
    private function log($tag, $payload = array()) {
        if (!__dav_should_log($tag)) return;
        if (!is_array($payload)) $payload = array('msg' => (string)$payload);
        error_log('[DAVDEBUG] ' . $tag . ' ' . json_encode($payload));
    }
}

if (!class_exists('DavDiscoveryService', false)) { class_alias('DavDiscoveryServiceCore', 'DavDiscoveryService'); }
if (!class_exists('AccountDetails\\DavDiscoveryService', false)) { class_alias('DavDiscoveryServiceCore', 'AccountDetails\\DavDiscoveryService'); }
if (!class_exists('AccountDetails\\Lib\\DavDiscoveryService', false)) { class_alias('DavDiscoveryServiceCore', 'AccountDetails\\Lib\\DavDiscoveryService'); }
?>
