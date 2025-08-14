<?php
class DavDiscoveryService
{
    /** @var rcube */
    private $rc;
    /** @var rcube_plugin */
    private $plugin;
    /** @var string */
    private $plugin_dir;
    /** @var array|null */
    private $plugin_cfg = null;

    public function __construct(rcube_plugin $plugin, ?string $plugin_dir = null)
    {
        $this->plugin = $plugin;
        $this->rc = rcube::get_instance();
        // Do NOT access protected $plugin->home directly
        $this->plugin_dir = $plugin_dir ?: dirname(__DIR__); // lib/.. => plugin root
        $this->load_plugin_cfg();
    }

    private function load_plugin_cfg(): void
    {
        if ($this->plugin_cfg !== null) return;
        $this->plugin_cfg = [];
        // Load config (plugin-local first, then .dist as fallback)
        $paths = array(
            $this->plugin_dir . '/config.inc.php',
            $this->plugin_dir . '/config.inc.php.dist',
        );
        foreach ($paths as $p) {
            if (@is_file($p) && @is_readable($p)) {
                $account_details_config = null;
                ob_start();
                include($p);
                ob_end_clean();
                if (is_array($account_details_config)) {
                    $this->plugin_cfg = array_merge($this->plugin_cfg, $account_details_config);
                }
            }
        }
    }

    private function cfg(string $key, $default = null)
    {
        // Prefer Roundcube config, then plugin config file
        $val = $this->rc->config->get($key, null);
        if ($val !== null) return $val;
        if ($this->plugin_cfg !== null && array_key_exists($key, $this->plugin_cfg)) {
            return $this->plugin_cfg[$key];
        }
        return $default;
    }

    private function is_debug(): bool
    {
        $cfg = $this->cfg('enable_dav_debug', false);
        $flag = is_bool($cfg) ? $cfg : (bool)$cfg;
        $get  = isset($_GET['_davdebug']) && $_GET['_davdebug'];
        return $flag || $get;
    }

    private function dbg(string $msg, array $ctx = []): void
    {
        if (!$this->is_debug()) return;
        $line = '[DAVDEBUG] ' . $msg . (!empty($ctx) ? (' ' . json_encode($ctx)) : '');
        if (is_callable(['rcube','write_log'])) { @rcube::write_log('dav_debug', $line); }
        @error_log($line);
    }

    public function discover(bool $use_db = true): array
    {
        $this->dbg('discover:start', ['use_db' => $use_db, 'user_id' => $this->rc->user->ID ?? null]);

        $cal  = [];
        $card = [];

        $cal_mode  = (string)$this->cfg('dav_caldav_mode', 'rfc'); // 'none'|'db'|'rfc'|'static'
        $card_mode = (string)$this->cfg('dav_carddav_mode', 'db');  // 'none'|'db'|'rfc'

        if ($cal_mode === 'none') {
            $this->dbg('caldav:disabled');
            $cal = [];
        } elseif ($cal_mode === 'static') {
            $cal = (array)$this->cfg('dav_caldav_static', []);
        } elseif ($cal_mode === 'db') {
            $cal = $this->from_db('caldav');
        } else {
            if ($use_db) $cal = $this->from_db('caldav');
            if (empty($cal)) {
                $this->dbg('rfc:fallback_trigger', ['type' => 'caldav']);
                $cal = $this->discover_via_rfc('caldav');
            }
        }

        if ($card_mode === 'none') {
            $this->dbg('carddav:disabled');
            $card = [];
        } elseif ($card_mode === 'db') {
            $card = $this->from_db('carddav');
        } else {
            if ($use_db) $card = $this->from_db('carddav');
            if (empty($card)) {
                $this->dbg('rfc:fallback_trigger', ['type' => 'carddav']);
                $card = $this->discover_via_rfc('carddav');
            }
        }

        // Replacement maps
        $cal  = $this->apply_replacements($cal,  (array)$this->cfg('caldav_url_replace', []));
        $card = $this->apply_replacements($card, (array)$this->cfg('carddav_url_replace', []));

        // Optional hide patterns (regex) for names/urls
        $cal_hide  = (array)$this->cfg('caldav_hide_patterns', []);
        $card_hide = (array)$this->cfg('carddav_hide_patterns', []);
        $cal  = $this->apply_hide_patterns($cal,  $cal_hide);
        $card = $this->apply_hide_patterns($card, $card_hide);

        // Final sanitation: drop empties and weird placeholders
        $cal  = array_values(array_filter($cal,  function($it){ return is_array($it) && !empty($it['url']) && isset($it['name']) && trim($it['name']) !== '' && trim($it['name']) !== '%N'; }));
        $card = array_values(array_filter($card, function($it){ return is_array($it) && !empty($it['url']) && isset($it['name']) && trim($it['name']) !== '' && trim($it['name']) !== '%N'; }));

        // Sort by name
        usort($cal,  function($a,$b){ return strcasecmp($a['name'],$b['name']); });
        usort($card, function($a,$b){ return strcasecmp($a['name'],$b['name']); });

        $this->dbg('discover:end', ['cal_count' => count($cal), 'card_count' => count($card)]);
        return ['caldav' => $cal, 'carddav' => $card];
    }

    private function from_db(string $type): array
    {
        $this->dbg('from_db:start', ['type' => $type]);

        $uid   = (int)($this->rc->user->ID ?? 0);
        $uname = $this->rc->user ? (string)$this->rc->user->get_username() : '';

        if ($type === 'caldav') {
            $candidates = [
                ['table' => $this->rc->db->table_name('caldav_calendars'), 'url' => 'caldav_url', 'name' => 'name'],
                ['table' => $this->rc->db->table_name('caldav_calendars'), 'url' => 'url',        'name' => 'name'],
                ['table' => $this->rc->db->table_name('caldav_calendars'), 'url' => 'url',        'name' => 'displayname'],
            ];
        } else {
            $candidates = [
                ['table' => $this->rc->db->table_name('carddav_addressbooks'), 'url' => 'url', 'name' => 'name'],
            ];
        }

        foreach ($candidates as $idx => $c) {
            $table = $c['table'];
            $this->dbg('from_db:candidate', ['idx' => $idx, 'table' => $table, 'name_col' => $c['name'], 'url_col' => $c['url']]);

            if (!$this->table_exists($table)) {
                $this->dbg('from_db:skip_missing_table', ['table' => $table]);
                continue;
            }
            if (!$this->column_exists($table, $c['name']) || !$this->column_exists($table, $c['url'])) {
                $this->dbg('from_db:skip_missing_columns', ['table' => $table, 'name_col' => $c['name'], 'url_col' => $c['url']]);
                continue;
            }

            $owner_cols = $this->find_owner_columns($table);
            $this->dbg('from_db:owner_cols', ['table' => $table, 'cols' => $owner_cols]);

            $where_sets = [];
            if (in_array('user_id', $owner_cols, true))   $where_sets[] = ['col' => 'user_id', 'val' => $uid];
            if (in_array('user', $owner_cols, true))      $where_sets[] = ['col' => 'user', 'val' => $uname];
            if (in_array('username', $owner_cols, true))  $where_sets[] = ['col' => 'username', 'val' => $uname];
            if (in_array('account', $owner_cols, true))   $where_sets[] = ['col' => 'account', 'val' => $uname];
            if (in_array('email', $owner_cols, true))     $where_sets[] = ['col' => 'email', 'val' => $uname];
            $where_sets[] = null; // try unfiltered

            $items = [];
            foreach ($where_sets as $widx => $w) {
                if ($w) {
                    $sql = sprintf('SELECT %s AS name, %s AS url FROM %s WHERE %s = ?', $c['name'], $c['url'], $table, $w['col']);
                    $this->dbg('from_db:query', ['sql' => $sql, 'val' => $w['val']]);
                    $res = $this->rc->db->query($sql, $w['val']);
                } else {
                    $sql = sprintf('SELECT %s AS name, %s AS url FROM %s', $c['name'], $c['url'], $table);
                    $this->dbg('from_db:query', ['sql' => $sql]);
                    $res = $this->rc->db->query($sql);
                }
                while ($res && ($row = $this->rc->db->fetch_assoc($res))) {
                    if (!isset($row['name']) || !isset($row['url'])) continue;
                    $nm = (string)$row['name'];
                    $u  = (string)$row['url'];
                    if ($nm === '' || $u === '') continue;
                    $items[] = ['name' => $nm, 'url' => $u];
                }
                $this->dbg('from_db:rows', ['count' => count($items), 'where_idx' => $widx]);
                if (!empty($items)) break;
            }

            if (!empty($items)) return $items;
        }

        return [];
    }

    private function table_exists(string $table): bool
    {
        try {
            $res = $this->rc->db->query('SHOW TABLES LIKE ?', $table);
            return ($res && $this->rc->db->num_rows($res) > 0);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function column_exists(string $table, string $col): bool
    {
        try {
            $res = $this->rc->db->query('SHOW COLUMNS FROM ' . $table . ' LIKE ?', $col);
            return ($res && $this->rc->db->num_rows($res) > 0);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function find_owner_columns(string $table): array
    {
        $cands = ['user_id','user','username','account','email'];
        $have  = [];
        foreach ($cands as $c) {
            if ($this->column_exists($table, $c)) $have[] = $c;
        }
        return $have;
    }

    private function guess_base_url(): ?string
    {
        $host = $this->cfg('dav_host', null);
        if ($host) return (stripos($host, 'http') === 0) ? $host : ('https://' . $host);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $h = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? '');
        if ($h) return $scheme . '://' . $h;
        $imap_host = $this->rc->config->get('default_host');
        if (is_string($imap_host) && preg_match('~[a-z0-9.-]+\.[a-z]{2,}$~i', $imap_host)) return 'https://' . $imap_host;
        return null;
    }

    private function discover_via_rfc(string $type): array
    {
        $base = $this->guess_base_url();
        if (!$base) { $this->dbg('rfc:base_missing'); return []; }

        $wk = rtrim($base, '/') . '/.well-known/' . ($type === 'caldav' ? 'caldav' : 'carddav');
        $head = $this->http_request('HEAD', $wk, null, 4.0, []);
        $target = (!empty($head['effective_url'])) ? $head['effective_url'] : $wk;
        $this->dbg('rfc:well_known', ['input' => $wk, 'target' => $target, 'status' => $head['status']]);

        // Accept 401 on well-known if configured; still surface target
        $accept401 = (bool)$this->cfg('dav_accept_401_well_known', true);
        if ($head['status'] >= 400 && !($accept401 && $head['status'] == 401)) {
            return [];
        }

        $label = (string)$this->cfg('dav_caldav_label', 'Calendars');
        if ($type !== 'caldav') $label = (string)$this->cfg('dav_carddav_label', 'Address Books');
        return [[ 'name' => $label, 'url' => $target ]];
    }

    private function http_request(string $method, string $url, ?string $body, float $timeout, array $extra_headers = []): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            $headers = array_merge(['User-Agent: Roundcube-DAV-Discovery'], $extra_headers);
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_NOBODY => ($method === 'HEAD'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_HEADER => false,
                CURLOPT_CONNECTTIMEOUT => max(1, (int)floor($timeout / 2)),
                CURLOPT_TIMEOUT => max(1, (int)ceil($timeout)),
                CURLOPT_HTTPHEADER => $headers,
            ]);
            if ($body !== null && $method !== 'HEAD') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $resp_body = curl_exec($ch);
            $info = curl_getinfo($ch);
            $err  = curl_error($ch);
            curl_close($ch);
            return [
                'status' => isset($info['http_code']) ? (int)$info['http_code'] : 0,
                'body'   => $resp_body,
                'error'  => $err ?: null,
                'effective_url' => isset($info['url']) ? $info['url'] : $url
            ];
        }
        $headers = array_merge(['User-Agent: Roundcube-DAV-Discovery'], $extra_headers);
        $opts = ['http' => ['method' => $method,'timeout' => $timeout,'ignore_errors' => true,'header' => implode("\r\n", $headers)]];
        if ($body !== null && $method !== 'HEAD') $opts['http']['content'] = $body;
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $ctx);
        $status = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) { if (preg_match('~HTTP/\d\.\d\s+(\d+)~', $h, $m)) { $status = (int)$m[1]; break; } }
        }
        return ['status' => $status, 'body' => $resp, 'error' => null, 'effective_url' => $url];
    }

    private function apply_replacements(array $items, array $repl): array
    {
        foreach ($items as &$it) {
            if (!is_array($it) || !isset($it['url'])) continue;
            $u = $this->normalize_url($it['url']);
            foreach ($repl as $k => $v) {
                if (is_string($k) && strlen($k) > 2 && $k[0] === '/' && substr($k, -1) === '/') {
                    $u = @preg_replace($k, (string)$v, $u);
                } else {
                    $u = str_replace((string)$k, (string)$v, $u);
                }
            }
            $it['url'] = $u;
        }
        unset($it);
        return $items;
    }

    private function apply_hide_patterns(array $items, array $patterns): array
    {
        if (empty($patterns)) return $items;
        $out = [];
        foreach ($items as $it) {
            if (!is_array($it)) continue;
            $name = (string)($it['name'] ?? '');
            $url  = (string)($it['url'] ?? '');
            $hide = false;
            foreach ($patterns as $rx) {
                if (!is_string($rx) || $rx === '') continue;
                $ok = @preg_match($rx, '') !== false; // validate regex
                if ($ok) {
                    if (@preg_match($rx, $name) || @preg_match($rx, $url)) { $hide = true; break; }
                } else {
                    // treat as plain substring
                    if (stripos($name, $rx) !== false || stripos($url, $rx) !== false) { $hide = true; break; }
                }
            }
            if (!$hide) $out[] = $it;
        }
        return $out;
    }

    private function normalize_url(string $raw): string
    {
        $parts = explode('?', $raw, 2);
        $base = $parts[0];
        $q = isset($parts[1]) ? $parts[1] : '';

        $p = @parse_url($base);
        if (!$p || empty($p['scheme']) || empty($p['host'])) {
            return $raw;
        }

        $scheme = strtolower($p['scheme']);
        $host   = $p['host'];
        $port   = isset($p['port']) ? (int)$p['port'] : null;
        $path   = isset($p['path']) ? $p['path'] : '/';

        if (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80)) {
            $port = null;
        }
        $path = preg_replace('~/{2,}~', '/', $path ?: '/');

        $url = $scheme . '://' . $host . ($port ? ':' . $port : '') . $path;
        if ($q !== '') $url .= '?' . $q;

        return $url;
    }
}
