<?php

/**
 * Roundcube "Account Details" plugin — modernized with CalDAV/CardDAV tutorial links
 * and optional mobile detection to force Elastic skin.
 *
 * Notes:
 * - New config flags (optional): 
 *     - 'force_elastic_on_mobile' (bool, default true)
 *     - 'account_details_show_tutorial_links' (bool, default true)
 * - Uses DavDiscoveryService like your newer version. If not available, tutorial
 *   links simply won't render.
 */

class account_details extends rcube_plugin
{
    /** @var string */
    public $task = 'settings';

    /** @var rcube */
    private $rc;

    /** @var array */
    private $config = [];

    public function init(): void
    {
        $this->rc = rcube::get_instance();

        // Optional: force Elastic on mobile devices
        if ($this->rc->config->get('force_elastic_on_mobile', true)) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
                // Prefer output setter if available, otherwise fall back to config override
                if (method_exists($this->rc->output, 'set_skin')) {
                    $this->rc->output->set_skin('elastic');
                } else {
                    $this->rc->config->set('skin', 'elastic');
                }
            }
        }

        $this->add_texts('localization/', ['account_details']);
        $this->register_action('plugin.account_details', [$this, 'infostep']);
        $this->add_hook('settings_actions', [$this, 'settings_actions']);
        $this->include_stylesheet($this->local_skin_path() . '/account_details.css');

        // plugin deps (keep your originals)
        @require_once $this->home . '/lib/mail_count.php';
        @require_once $this->home . '/lib/Browser.php';
        @require_once $this->home . '/lib/OS.php';
        @require_once $this->home . '/lib/CPU_usage.php';
        @require_once $this->home . '/lib/listplugins.php';
        @require_once $this->home . '/lib/DavDiscoveryService.php';
        @require_once $this->home . '/lib/getip.php';
    }

    private function _load_config(): void
    {
        $dist = $this->home . '/config.inc.php.dist';
        $conf = $this->home . '/config.inc.php';

        $acc_dist = [];
        $acc_user = [];

        if (is_file($dist) && is_readable($dist)) {
            $account_details_config = [];
            $config = null;
            include $dist;
            if (isset($account_details_config) && is_array($account_details_config)) {
                $acc_dist = $account_details_config;
            } elseif (isset($config) && is_array($config)) {
                $acc_dist = $config;
            }
        }

        if (is_file($conf) && is_readable($conf)) {
            $account_details_config = [];
            $config = null;
            include $conf;
            if (isset($account_details_config) && is_array($account_details_config)) {
                $acc_user = $account_details_config;
            } elseif (isset($config) && is_array($config)) {
                $acc_user = $config;
            }
        }

        if (!$acc_dist && !$acc_user) {
            raise_error([
                'code'    => 527,
                'type'    => 'php',
                'message' => 'Failed to load Account Details plugin config',
            ], true, true);
        }

        $this->config = array_replace((array) $acc_dist, (array) $acc_user);
    }

    public function infostep(): void
    {
        $this->register_handler('plugin.body', [$this, 'infohtml']);
        $this->api->output->set_pagetitle($this->gettext('account_details'));
        $this->api->output->send('plugin');
    }

    public function settings_actions(array $args): array
    {
        $args['actions'][] = [
            'action' => 'plugin.account_details',
            'class'  => 'account_details',
            'label'  => 'account_details',
            'type'   => 'link',
            'title'  => 'account_details_title',
            'domain' => 'account_details',
        ];

        return $args;
    }

    public function infohtml()
    {
        $this->_load_config();

        $user = $this->rc->user;

        // Formatting toggles
        $pn_newline     = !empty($this->config['pn_newline']);
        $pn_parentheses = !$pn_newline;

        $browser = class_exists('Browser') ? new Browser() : null;

        // Screen Resolution (client-side)
        $width  = ' <script>document.write(screen.width);</script>';
        $height = ' <script>document.write(screen.height);</script>';

        // Server Uptime (Linux /proc)
        $days = $hours = $mins = $secs = 0;
        $str = @file_get_contents('/proc/uptime');
        if ($str !== false) {
            $num   = (float) $str;
            $secs  = fmod($num, 60);
            $num   = (int) ($num / 60);
            $mins  = $num % 60;
            $num   = (int) ($num / 60);
            $hours = $num % 24;
            $num   = (int) ($num / 24);
            $days  = $num;
        }

        $url_box_length = (int) ($this->config['urlboxlength'] ?? 80);

        // Build base 2-col table
        $table = new html_table([
            'class'       => 'account_details',
            'cols'        => 2,
            'cellpadding' => 0,
            'cellspacing' => 0,
        ]);

        // USER DETAILS
        $table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('userdet') . ':')));
        $table->add('', '');

        $identity = (array) $user->get_identity();

        if (!empty($this->config['enable_userid'])) {
            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('userid') . ':'));
            $table->add('value', rcube_utils::rep_specialchars_output($user->ID));
        }

        $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('defaultidentity') . ':'));
        $table->add('value', rcube_utils::rep_specialchars_output(($identity['name'] ?? '') . ' '));

        $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('email') . ':'));
        $table->add('value', rcube_utils::rep_specialchars_output($identity['email'] ?? ''));

        $date_format = $this->rc->config->get('date_format', 'm/d/Y') . ' ' . $this->rc->config->get('time_format', 'H:i');

        // Created
        $created_raw = $user->data['created'] ?? null;
        if (!empty($this->config['display_create']) && $created_raw && (int) date('Y', strtotime($created_raw)) > 1970) {
            $created = new DateTime($created_raw);
            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('created') . ':'));
            $table->add('value', rcube_utils::rep_specialchars_output($created->format($date_format)));
        }

        // Last login
        $last_raw = $user->data['last_login'] ?? null;
        if (!empty($this->config['display_lastlogin']) && $last_raw) {
            $lastlogin = new DateTime($last_raw);
            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('last') . ' ' . $this->gettext('login') . ':'));
            $table->add('value', rcube_utils::rep_specialchars_output($lastlogin->format($date_format)));
        }

        // Storage / Quota
        $this->rc->storage_connect(true);
        $imap  = $this->rc->get_storage();
        $quota = is_object($imap) && method_exists($imap, 'get_quota') ? (array) $imap->get_quota() : null;

        if (!empty($this->config['enable_quota']) && $quota) {
            $total_bytes = (int) (($quota['total'] ?? 0) * 1024);
            $used_bytes  = (int) (($quota['used'] ?? 0) * 1024);
            $percent     = (int) ($quota['percent'] ?? 0);

            $quotatotal = $this->rc->show_bytes($total_bytes);
            $quotaused  = $this->rc->show_bytes($used_bytes) . ' (' . $percent . '%)';

            if (!empty($quota) && ($quota['total'] == 0) && $this->rc->config->get('quota_zero_as_unlimited')) {
                $quotatotal = 'unlimited';
            }

            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('storagequota') . ':'));
            $table->add('value', rcube_utils::rep_specialchars_output($quotatotal));

            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('usedstorage') . ':'));
            $table->add('value', rcube_utils::rep_specialchars_output($quotaused));
        }

        // IP
        if (!empty($this->config['enable_ip'])) {
            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('ipaddress') . ':'));
            $table->add('value', rcube_utils::rep_specialchars_output(function_exists('get_client_ip_server') ? get_client_ip_server() : ($_SERVER['REMOTE_ADDR'] ?? '')));
        }

        // Support URL
        if (!empty($this->config['enable_support'])) {
            $support_url = (string) $this->rc->config->get('support_url');
            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('support') . ':'));
            $table->add('value', html::tag('a', ['href' => $support_url, 'title' => $this->gettext('supporturl'), 'target' => '_blank'], rcube_utils::rep_specialchars_output($support_url)));
        }

        // OS / Browser / Resolution
        if (!empty($this->config['enable_osystem'])) {
            $table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('usystem') . ':')));
            $table->add('', '');

            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('os') . ':'));
            $uagent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $table->add('value', rcube_utils::rep_specialchars_output(function_exists('os_info') ? os_info($uagent) : $uagent));

            if (!empty($this->config['enable_resolution'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('resolution') . ':'));
                $table->add('value', $width . ' x ' . $height);
            }

            if (!empty($this->config['enable_browser']) && $browser) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('webbrowser') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output($browser->getBrowser()));

                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('version') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output($browser->getVersion()));

                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('browser-user-agent') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output($browser->getUserAgent()));
            }
        }

        // MAILBOX DETAILS
        $imap_ok = (!empty($this->config['enable_mailbox']) && $imap);
        if ($imap_ok) {
            $table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('mailboxdetails') . ':')));
            $table->add('', '');

            $delim = method_exists($imap, 'get_hierarchy_delimiter') ? $imap->get_hierarchy_delimiter() : '.';

            $inbox_base   = $this->config['folder_inbox']   ?? 'INBOX';
            $drafts_base  = $this->config['folder_drafts']  ?? 'Drafts';
            $sent_base    = $this->config['folder_sent']    ?? 'Sent';
            $trash_base   = $this->config['folder_trash']   ?? 'Trash';
            $junk_base    = $this->config['folder_junk']    ?? 'Junk';
            $archive_base = $this->config['folder_archive'] ?? 'Archive';

            $folder_exists = function ($name) use ($imap) {
                if (!$name) return false;
                if (method_exists($imap, 'folder_exists')) {
                    return $imap->folder_exists($name);
                }
                $c = $imap->count($name, 'ALL');
                return $c !== false && $c !== null;
            };

            $maybe_children = [$drafts_base, $sent_base, $trash_base, $junk_base, $archive_base];
            $needs_prefix   = false;
            foreach ($maybe_children as $b) {
                if ($folder_exists($inbox_base . $delim . $b) && !$folder_exists($b)) {
                    $needs_prefix = true;
                    break;
                }
            }
            $prefix = $needs_prefix ? $inbox_base . $delim : '';

            $resolve = function ($base) use ($prefix, $inbox_base, $delim, $folder_exists) {
                $candidates = [];
                if ($prefix !== '') $candidates[] = $prefix . $base;
                $candidates[] = $inbox_base . $delim . $base;
                $candidates[] = $base;
                foreach ($candidates as $f) {
                    if ($folder_exists($f)) return $f;
                }
                return ($prefix !== '' ? $prefix . $base : $base);
            };

            // INBOX
            $inbox_folder = $inbox_base;
            $inbox_size_mb = 0.0;
            if (method_exists($imap, 'folder_size')) {
                $sz = $imap->folder_size($inbox_folder);
                $inbox_size_mb = $sz ? round(((float) $sz) / 1024 / 1024, 2) : 0.0;
            }
            $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('inbox') . ':'));
            $table->add(
                'value',
                rcube_utils::rep_specialchars_output((string) $imap->count($inbox_folder, 'UNSEEN')) . '&nbsp;' .
                rcube_utils::rep_specialchars_output($this->gettext('unread') . '&nbsp;-&nbsp;') .
                rcube_utils::rep_specialchars_output((string) $imap->count($inbox_folder, 'ALL')) . '&nbsp;' .
                rcube_utils::rep_specialchars_output($this->gettext('total') . '&nbsp;-&nbsp;') .
                rcube_utils::rep_specialchars_output((string) $inbox_size_mb) . '&nbsp;' .
                rcube_utils::rep_specialchars_output($this->gettext('MB'))
            );

            $add_folder_row = function (string $label_key, string $base) use ($resolve, $imap, $table) {
                $f = $resolve($base);
                $size_mb = 0.0;
                if (method_exists($imap, 'folder_size')) {
                    $sz = $imap->folder_size($f);
                    $size_mb = $sz ? round(((float) $sz) / 1024 / 1024, 2) : 0.0;
                }
                $table->add(
                    'title',
                    '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;-&nbsp;' .
                    rcube_utils::rep_specialchars_output($this->gettext($label_key) . ' ' . $this->gettext('folder') . ':')
                );
                $table->add(
                    'value',
                    rcube_utils::rep_specialchars_output((string) $imap->count($f, 'UNSEEN')) . '&nbsp;' .
                    rcube_utils::rep_specialchars_output($this->gettext('unread') . '&nbsp;-&nbsp;') .
                    rcube_utils::rep_specialchars_output((string) $imap->count($f, 'ALL')) . '&nbsp;' .
                    rcube_utils::rep_specialchars_output($this->gettext('total') . '&nbsp;-&nbsp;') .
                    rcube_utils::rep_specialchars_output((string) $size_mb) . '&nbsp;' .
                    rcube_utils::rep_specialchars_output($this->gettext('MB'))
                );
            };

            if (!empty($this->config['enable_drafts']))  $add_folder_row('drafts',  $drafts_base);
            if (!empty($this->config['enable_sent']))    $add_folder_row('sent',    $sent_base);
            if (!empty($this->config['enable_trash']))   $add_folder_row('trash',   $trash_base);
            if (!empty($this->config['enable_junk']))    $add_folder_row('junk',    $junk_base);
            if (!empty($this->config['enable_archive'])) $add_folder_row('archive', $archive_base);
        }

        // SERVER DETAILS
        if (!empty($this->config['enable_server_os'])) {
            if (!empty($this->config['location'])) {
                $table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('details') . ':')));
                $table->add('', '');

                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('location') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output($this->config['location']));
            }

            if (!empty($this->config['enable_server_os_name'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('os') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output(php_uname('s')));
            }

            if (!empty($this->config['enable_server_os_rel'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('os') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output(php_uname('s') . ' - ' . php_uname('r')));
            }

            if (!empty($this->config['enable_server_os_ver'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('os') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output(php_uname('s') . ' - ' . php_uname('r') . ' - ' . php_uname('v')));
            }

            if (!empty($this->config['enable_server_memory'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('memory') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output($this->rc->show_bytes((int) round(memory_get_usage(), 2))) . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('used')));
            }

            if (!empty($this->config['enable_server_cpu'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('cpu') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output((string) (function_exists('get_server_cpu_usage') ? get_server_cpu_usage() : '')) . ' % Used');
            }

            if (!empty($this->config['enable_server_uptime'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' ' . $this->gettext('uptime') . ':'));
                $table->add('value',
                    rcube_utils::rep_specialchars_output((string) $days) . '&nbsp;' . $this->gettext('days') . '&nbsp;' .
                    rcube_utils::rep_specialchars_output((string) $hours) . '&nbsp;' . $this->gettext('hours') . '&nbsp;' .
                    rcube_utils::rep_specialchars_output((string) $mins) . '&nbsp;' . $this->gettext('minutes') . '&nbsp;' .
                    rcube_utils::rep_specialchars_output((string) $secs) . '&nbsp;' . $this->gettext('seconds'));
            }

            if (!empty($this->config['display_php_version'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('php_version') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output(PHP_VERSION));
            }

            if (!empty($this->config['display_http_server'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('htmlserver') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output($_SERVER['SERVER_SOFTWARE'] ?? ''));
            }

            if (!empty($this->config['display_admin_email']) && !empty($_SERVER['SERVER_ADMIN'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('server') . ' Admin ' . $this->gettext('email') . ':'));
                $table->add('value', html::tag(
                    'a',
                    ['href' => 'mailto:' . $_SERVER['SERVER_ADMIN'], 'title' => $this->gettext('contactadmin')],
                    rcube_utils::rep_specialchars_output($_SERVER['SERVER_ADMIN'])
                ));
            }

            // Custom fields (server section)
            $this->_custom_fields('customfields_server', $table);

            // ----- PORT NUMBERS -----
            $smtp_notes_array_all           = [];
            $smtp_notes_array_regularonly   = [];
            $smtp_notes_array_encryptedonly = [];
            $smtp_notes_regular             = '';
            $smtp_notes_encrypted           = '';
            $imap_notes_regular             = '';
            $imap_notes_encrypted           = '';
            $pop_notes_regular              = '';
            $pop_notes_encrypted            = '';
            $spa_all                        = false;
            $commalist_ucfirst              = !empty($this->config['commalist_ucfirst']);

            if (!empty($this->config['spa_support_smtp']) && !empty($this->config['spa_support_imap']) && !empty($this->config['spa_support_pop'])) {
                $spa_all = true;
            } else {
                if (!empty($this->config['spa_support_smtp'])) $smtp_notes_array_regularonly[] = $this->gettext('spaauthsupported');
                if (!empty($this->config['spa_support_imap'])) $imap_notes_regular = ' (' . $this->gettext('spaauthsupported') . ')';
                if (!empty($this->config['spa_support_pop']))  $pop_notes_regular  = ' (' . $this->gettext('spaauthsupported') . ')';
            }

            $smtp_after_text = '';
            if (!empty($this->config['smtp_after_pop']) && empty($this->config['smtp_after_imap'])) {
                $smtp_after_text = $this->gettext('smtpafterpop');
            } elseif (empty($this->config['smtp_after_pop']) && !empty($this->config['smtp_after_imap'])) {
                $smtp_after_text = $this->gettext('smtpafterimap');
            } elseif (!empty($this->config['smtp_after_pop']) && !empty($this->config['smtp_after_imap'])) {
                $smtp_after_text = $this->gettext('smtpafterpopimap');
            }

            if (!empty($this->config['smtp_auth_required_always'])) {
                $smtp_notes_array_all[] = $this->gettext('authrequired');
            } else {
                if (!empty($this->config['smtp_auth_required_else'])) {
                    if (!empty($this->config['smtp_relay_local']) && empty($this->config['smtp_after_pop']) && empty($this->config['smtp_after_imap'])) {
                        $smtp_notes_array_all[] = $this->gettext('authrequired_local');
                    } elseif (!empty($this->config['smtp_relay_local']) && (!empty($this->config['smtp_after_pop']) || !empty($this->config['smtp_after_imap']))) {
                        $smtp_notes_array_all[] = str_replace('%s', $smtp_after_text, $this->gettext('authrequired_local_smtpafter'));
                    } elseif (empty($this->config['smtp_relay_local']) && (!empty($this->config['smtp_after_pop']) || !empty($this->config['smtp_after_imap']))) {
                        $smtp_notes_array_all[] = str_replace('%s', $smtp_after_text, $this->gettext('authrequired_smtpafter'));
                    }
                } else {
                    if (!empty($this->config['smtp_relay_local'])) $smtp_notes_array_all[] = $this->gettext('openrelaylocal');
                    if ($smtp_after_text) $smtp_notes_array_all[] = $smtp_after_text;
                }
            }

            $smtp_notes_array_regular   = array_merge((array) $smtp_notes_array_all, (array) $smtp_notes_array_regularonly);
            $smtp_notes_array_encrypted = array_merge((array) $smtp_notes_array_all, (array) $smtp_notes_array_encryptedonly);

            if (!empty($smtp_notes_array_regular))   $smtp_notes_regular   = ucfirst($this->_separated_list($smtp_notes_array_regular, false, true, !empty($commalist_ucfirst), $pn_parentheses, $pn_newline));
            if (!empty($smtp_notes_array_encrypted)) $smtp_notes_encrypted = ucfirst($this->_separated_list($smtp_notes_array_encrypted, false, true, !empty($commalist_ucfirst), $pn_parentheses, $pn_newline));

            // Regular ports
            if (!empty($this->config['port_smtp']) || !empty($this->config['port_imap']) || !empty($this->config['port_pop']) || !empty($this->config['customfields_regularports'])) {
                $table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('portnumbers') . ' - ' . $this->gettext('portnumbersregular'))));
                $table->add('', '');

                if ($spa_all) {
                    $table->add(['colspan' => 2, 'class' => 'categorynote'], ucfirst($this->gettext('spaauthsupported')));
                    $table->add_row();
                }

                if (!empty($this->config['port_smtp'])) {
                    $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('smtp') . ':'));
                    $table->add('value', $this->_host_replace($this->config['hostname_smtp']) . ':' . $this->_separated_list((array) $this->config['port_smtp'], true) . ($smtp_notes_regular ? ' ' . $smtp_notes_regular : ''));
                }

                if (!empty($this->config['port_imap'])) {
                    $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('imap') . ':'));
                    $table->add('value', $this->_host_replace($this->config['hostname_imap']) . ':' . $this->_separated_list((array) $this->config['port_imap'], true) . ($imap_notes_regular ? ' ' . $imap_notes_regular : ''));
                }

                if (!empty($this->config['port_pop'])) {
                    $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('pop') . ':'));
                    $table->add('value', $this->_host_replace($this->config['hostname_pop']) . ':' . $this->_separated_list((array) $this->config['port_pop'], true) . ($pop_notes_regular ? ' ' . $pop_notes_regular : ''));
                }

                $this->_custom_fields('customfields_regularports', $table);
            }

            // Encrypted ports
            if (!empty($this->config['port_smtp-ssl']) || !empty($this->config['port_imap-ssl']) || !empty($this->config['port_pop-ssl']) || !empty($this->config['customfields_encryptedports'])) {
                $header = html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('portnumbers') . ' - ' . $this->gettext('portnumbersencrypted')));
                if (!empty($this->config['recommendssl'])) {
                    $header .= ' ' . html::tag('div', ['style' => 'color:red;'], '&nbsp;' . $this->gettext('recommended'));
                }

                $table->add(['colspan' => 2, 'class' => 'header'], $header);
                $table->add_row();

                if (!empty($this->config['port_smtp-ssl'])) {
                    $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('smtp-ssl') . ':'));
                    $table->add('value', $this->_host_replace($this->config['hostname_smtp']) . ':' . $this->_separated_list((array) $this->config['port_smtp-ssl'], true) . ($smtp_notes_encrypted ? ' ' . $smtp_notes_encrypted : ''));
                }

                if (!empty($this->config['port_imap-ssl'])) {
                    $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('imap-ssl') . ':'));
                    $table->add('value', $this->_host_replace($this->config['hostname_imap']) . ':' . $this->_separated_list((array) $this->config['port_imap-ssl'], true) . ($imap_notes_encrypted ? ' ' . $this->gettext('spaauthsupported') : ''));
                }

                if (!empty($this->config['port_pop-ssl'])) {
                    $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('pop-ssl') . ':'));
                    $table->add('value', $this->_host_replace($this->config['hostname_pop']) . ':' . $this->_separated_list((array) $this->config['port_pop-ssl'], true) . ($pop_notes_encrypted ? ' ' . $pop_notes_encrypted : ''));
                }

                $this->_custom_fields('customfields_encryptedports', $table);
            }
        }

        // ROUNDCUBE SECTION
        if (!empty($this->config['display_rc'])) {
            $table->add('title', html::tag('h4', null, '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('rcdetails') . ':')));
            $table->add('', '');

            if (!empty($this->config['display_rc_version'])) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('currver') . ':'));
                $table->add('value', rcube_utils::rep_specialchars_output($this->gettext('roundcube')) . '<span style="font-weight:bold">&nbsp;v' . RCMAIL_VERSION . '</span>');
            }

            $rc_latest = $this->_print_file_contents($this->config['rc_latest'] ?? '');
            if (!empty($this->config['display_rc_release']) && $rc_latest) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('latestversion') . ':'));
                $table->add('value',
                    rcube_utils::rep_specialchars_output($this->gettext('roundcube')) .
                    '<span style="font-weight:bold">&nbsp;v' . rcube_utils::rep_specialchars_output($rc_latest) . '</span>&nbsp;' .
                    $this->gettext('is_available') .
                    html::tag('a', ['href' => 'https://roundcube.net/download', 'title' => $this->gettext('downloadupdate'), 'target' => '_blank'], $this->gettext('download')) .
                    '!'
                );
            }

            // Webmail URL
            $webmail_url = $this->_host_replace($this->config['webmail_url'] ?? '');
            if (!empty($this->config['hostname']) && $webmail_url) {
                $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . rcube_utils::rep_specialchars_output($this->gettext('web_url') . ':'));
                $table->add('value', html::tag('a', ['href' => $webmail_url, 'title' => $this->gettext('web_url_alt'), 'target' => '_top'], rcube_utils::rep_specialchars_output($webmail_url)));
            }

            // Plugin list
            if (!empty($this->config['rc_pluginlist']) && function_exists('rcmail_ad_plugin_list')) {
                $plugin_html = rcmail_ad_plugin_list(['id' => 'rcmpluginlist', 'class' => 'rcm-plugin-list']);

                if ($plugin_html && stripos($plugin_html, '<table') !== false) {
                    $cols = $this->config['rc_pluginlist_cols'] ?? null;
                    if (is_array($cols) && count($cols) > 0) {
                        $plugin_html = $this->_inject_colgroup($plugin_html, $cols);
                    }
                    $plugin_html = $this->_force_first_table_width(
                        $plugin_html,
                        (string)($this->config['rc_pluginlist_width'] ?? '100%')
                    );
                }

                $table->add('top', '&nbsp;&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' .
                    rcube_utils::rep_specialchars_output($this->gettext('installedplugins') . ':'));
                $table->add('value', $plugin_html ?: '<em>No plugins found</em>');
            }
        }

        // ===== CalDAV / CardDAV tutorial links (clean modernized integration) =====
        if (!empty($this->config['enable_dav_urls'])) {
            $clients_html = '';
            $i = 0;

            if (class_exists('DavDiscoveryService')) {
                $svc       = new DavDiscoveryService($this, $this->home);
                $resources = (array) $svc->discover(true);

                // CALDAV
                $calitems = $resources['caldav'] ?? [];
                if (!empty($calitems)) {
                    $i++;
                    $table->add('title', html::tag('h4', null, '&nbsp;' . $this->gettext('calendars') . ':&nbsp;<sup>' . (int)$i . '</sup>'));
                    $table->add('', '');

                    foreach ($calitems as $idx => $item) {
                        $name     = rcube::Q($item['name'] ?? '');
                        $url      = rcube::Q($item['url'] ?? '');
                        $input_id = 'dav-url-cal-' . $idx;
                        $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . $name);
                        $table->add('', html::tag('input', [
                            'id'    => $input_id,
                            'class' => 'account_details',
                            'value' => $url,
                            'onclick' => 'this.setSelectionRange(0, this.value.length)',
                            'name'  => $name,
                            'type'  => 'text',
                            'size'  => $url_box_length,
                        ]));
                    }

                    if ($this->rc->config->get('account_details_show_tutorial_links', true)) {
                        $clients_html .= html::tag('hr') . '&nbsp;<sup>' . (int)$i . '</sup>&nbsp;'
                            . sprintf($this->gettext('clients'), 'CalDAV') . ':'
                            . html::tag('br')
                            . '&nbsp;&nbsp;- '
                            . html::tag('a', ['href' => 'https://www.mozilla.org/en-US/thunderbird/all.html', 'target' => '_blank'], 'Thunderbird')
                            . ' + '
                            . html::tag('a', ['href' => 'https://addons.mozilla.org/en-US/thunderbird/addon/lightning/', 'target' => '_blank'], 'Lightning');

                        $url = (string) $this->rc->config->get('caldav_thunderbird','../tutorials/thunderbird-caldav');
                        $clients_html .= html::tag('a', ['href' => $url, 'target' =>'_blank'], html::tag('div', ['style' => 'display:inline;float:right;'], 'Thunderbird ' . $this->gettext('tutorial')));

                        $url = (string) $this->rc->config->get('caldav_android_app','https://play.google.com/store/apps/details?id=org.dmfs.caldav.lib&hl=en');
                        $clients_html .= html::tag('br') . '&nbsp;&nbsp;- '
                            . html::tag('a', ['href' => 'https://www.android.com/', 'target' => '_blank'], 'Android')
                            . ' + '
                            . html::tag('a', ['href' => $url, 'target' => '_blank'], 'CalDAV-sync');

                        $url = (string) $this->rc->config->get('caldav_android','../tutorials/android-caldav');
                        $clients_html .= html::tag('a', ['href' => $url, 'target' =>'_blank'], html::tag('div', ['style' => 'display:inline;float:right;'], 'Android ' . $this->gettext('tutorial')));

                        $url = (string) $this->rc->config->get('caldav_iphone','../tutorials/iphone-caldav');
                        $clients_html .= html::tag('br') . '&nbsp;&nbsp;- '
                            . html::tag('a', ['href' => 'https://www.apple.com/iphone/', 'target' => '_blank'], 'iPhone')
                            . html::tag('a', ['href' => $url, 'target' => '_blank'], html::tag('div', ['style' => 'display:inline;float:right;'], 'iPhone ' . $this->gettext('tutorial')))
                            . html::tag('br') . html::tag('br');
                    }
                }

                // CARDDAV
                $abitems = $resources['carddav'] ?? [];
                if (!empty($abitems)) {
                    $i++;
                    $table->add('title', html::tag('h4', null, '&nbsp;' . $this->gettext('addressbook') . ':&nbsp;<sup>' . (int)$i . '</sup>'));
                    $table->add('', '');

                    foreach ($abitems as $idx => $item) {
                        $name     = rcube::Q($item['name'] ?? '');
                        $url      = rcube::Q($item['url'] ?? '');
                        $input_id = 'dav-url-card-' . $idx;
                        $table->add('title', '&nbsp;' . ($this->config['bulletstyle'] ?? '•') . '&nbsp;' . $name);
                        $table->add('', html::tag('input', [
                            'id'    => $input_id,
                            'class' => 'account_details',
                            'value' => $url,
                            'onclick' => 'this.setSelectionRange(0, this.value.length)',
                            'name'  => $name,
                            'type'  => 'text',
                            'size'  => $url_box_length,
                        ]));
                    }

                    if ($this->rc->config->get('account_details_show_tutorial_links', true)) {
                        if ($clients_html === '') $clients_html = html::tag('hr');
                        $clients_html .= '&nbsp;<sup>' . (int)$i . '</sup>&nbsp;' . sprintf($this->gettext('clients'), 'CardDAV') . ':'
                            . html::tag('br') . '&nbsp;&nbsp;- '
                            . html::tag('a', ['href' => 'https://www.mozilla.org/en-US/thunderbird/all.html', 'target' => '_blank'], 'Thunderbird');
                        $url = (string) $this->rc->config->get('carddav_thunderbird','../tutorials/thunderbird-carddav');
                        $clients_html .= ' + ' . html::tag('a', ['href' => 'https://sogo.nu/download.html#/frontends', 'target' => '_blank'], 'SOGo Connector')
                            . html::tag('a', ['href' => $url, 'target' =>'_blank'], html::tag('div', ['style' => 'display:inline;float:right;'], 'Thunderbird ' . $this->gettext('tutorial')));

                        $url = (string) $this->rc->config->get('carddav_android_app','https://play.google.com/store/apps/details?id=org.dmfs.carddav.sync&hl=en');
                        $clients_html .= html::tag('br') . '&nbsp;&nbsp;- '
                            . html::tag('a', ['href' => 'https://www.android.com/', 'target' => '_blank'], 'Android')
                            . ' + '
                            . html::tag('a', ['href' => $url, 'target' => '_blank'], 'CardDAV-sync');

                        $url = (string) $this->rc->config->get('carddav_android_app_editor','https://play.google.com/store/apps/details?id=org.dmfs.android.contacts&hl=en');
                        if ($url) {
                            $clients_html .= ' + ' . html::tag('a', ['href' => $url, 'target' => '_blank'], 'Contact Editor');
                        }

                        $url = (string) $this->rc->config->get('carddav_android','../tutorials/android-carddav');
                        $clients_html .= html::tag('a', ['href' => $url, 'target' =>'_blank'], html::tag('div', ['style' => 'display:inline;float:right;'], 'Android ' . $this->gettext('tutorial')));

                        $url = (string) $this->rc->config->get('carddav_iphone','../tutorials/iphone-carddav');
                        $clients_html .= html::tag('br') . '&nbsp;&nbsp;- '
                            . html::tag('a', ['href' => 'https://www.apple.com/iphone/', 'target' => '_blank'], 'iPhone')
                            . html::tag('a', ['href' => $url, 'target' => '_blank'], html::tag('div', ['style' => 'display:inline;float:right;'], 'iPhone ' . $this->gettext('tutorial')));
                    }
                }
            }

            if ($clients_html !== '') {
                $table->add(['colspan' => 2, 'class' => 'wholeline'], $clients_html);
                $table->add_row();
            }
        }

        // Bottom custom fields
        $this->_custom_fields('customfields_bottom', $table);

        // Render and inject main table column widths
        $rendered_table = $table->show();
        $col1 = trim((string) ($this->config['col1_width'] ?? '20%'));
        $col2 = trim((string) ($this->config['col2_width'] ?? '80%'));
        if ($col1 === '' || $col2 === '') { $col1 = '20%'; $col2 = '80%'; }
        $rendered_table = $this->_inject_colgroup($rendered_table, [$col1, $col2]);

        $out = html::div(['class' => 'settingsbox-account_details'],
                html::div(['class' => 'boxtitle'], $this->gettext('account_details') . ' for ' . rcube_utils::rep_specialchars_output($identity['name'] ?? ''))
            ) .
            html::div(['class' => 'box formcontent scroller'], $rendered_table);

        // Optional custom box content
        if (!empty($this->config['enable_custombox']) && !empty($this->config['custombox_file'])) {
            $rendered_table2 = $table->show();
            $rendered_table2 = $this->_inject_colgroup($rendered_table2, [$col1, $col2]);
            $out = html::div(['class' => 'settingsbox-account_details'],
                    html::div(['class' => 'boxtitle'], $this->gettext('account_details') . ' for ' . rcube_utils::rep_specialchars_output($identity['name'] ?? ''))
                ) .
                html::div(['class' => 'box formcontent scroller'], $rendered_table2 . $this->_print_file_contents($this->config['custombox_file']));
        }

        return $out;
    }

    /**
     * Add custom fields from a defined config array name into the table
     */
    private function _custom_fields(string $arrayname, html_table $table): bool
    {
        $arr = $this->config[$arrayname] ?? null;
        if (!is_array($arr) || count($arr) === 0) {
            return false;
        }

        foreach ($arr as $key => $arrayvalue) {
            $coltype = $arrayvalue['type'] ?? '';
            $coltext = $arrayvalue['text'] ?? '';

            if ($coltype === 'header' || $coltype === 'wholeline') {
                $table->add(['colspan' => 2, 'class' => $coltype], $coltext);
                $table->add_row();
            } elseif ($coltype === 'title' || $coltype === 'value') {
                $table->add($coltype, $coltext);
            }
        }

        return true;
    }

    /**
     * Return array as a separated list with formatting options
     */
    private function _separated_list($array, bool $and = false, bool $sentences = false, bool $ucfirst = false, bool $parentheses = false, bool $newline = false): string
    {
        $array = array_values((array) $array);
        $size  = count($array);
        if ($size === 0) {
            return '';
        }

        $str       = '';
        $separator = $sentences ? '. ' : ', ';

        if ($parentheses && $newline) {
            $str .= '<span class="fieldnote-parentheses fieldnote-newline">(';
        } elseif ($parentheses) {
            $str .= '<span class="fieldnote-parentheses">(';
        } elseif ($newline) {
            $str .= '<span class="fieldnote-newline">';
        }

        foreach ($array as $i => $item) {
            $item = (string) $item;
            if ($i === 0 && $ucfirst) {
                $item = ucfirst($item);
            }
            $str .= $item;

            if ($i < $size - 2) {
                $str .= $separator;
            } elseif ($i === $size - 2) {
                $str .= $and ? ' ' . $this->gettext('and') . ' ' : $separator;
            } elseif ($i === $size - 1 && $sentences && $size > 1) {
                $str .= '.';
            }
        }

        if ($parentheses || $newline) {
            $str .= ($parentheses ? ')' : '') . '</span>';
        }

        return $str;
    }

    /**
     * Return contents of a file or a failure message
     */
    private function _print_file_contents(string $filename): string
    {
        if ($filename && is_file($filename) && is_readable($filename)) {
            $size = filesize($filename);
            if ($size === 0 || $size === false) {
                return '';
            }
            $handle   = fopen($filename, 'r');
            $contents = $handle ? fread($handle, $size) : '';
            if ($handle) {
                fclose($handle);
            }
            return (string) $contents;
        }

        return 'Could not output file ' . rcube_utils::rep_specialchars_output($filename);
    }

    /**
     * Replace placeholders in a host string
     *
     * %h = user mail_host
     * %s = $_SERVER['SERVER_NAME']
     * %p = http/https based on HTTPS
     * %H = mail_host without first label
     * %S = server_name without first label
     */
    private function _host_replace(string $host): string
    {
        $this->rc = rcube::get_instance();
        $user     = $this->rc->user;

        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        $mail_host   = $user->data['mail_host'] ?? '';

        $host = str_replace('%h', $mail_host, $host);
        $host = str_replace('%s', $server_name, $host);

        $protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
        $host     = str_replace('%p', $protocol, $host);

        $stripped_h_array = $mail_host ? explode('.', $mail_host) : [];
        $stripped_s_array = $server_name ? explode('.', $server_name) : [];

        if (!empty($stripped_h_array)) array_shift($stripped_h_array);
        if (!empty($stripped_s_array)) array_shift($stripped_s_array);

        $stripped_h = implode('.', $stripped_h_array);
        $stripped_s = implode('.', $stripped_s_array);

        $host = str_replace('%H', $stripped_h, $host);
        $host = str_replace('%S', $stripped_s, $host);

        return $host;
    }

    /* =========================
     * Layout helpers (safe/limited)
     * ========================= */

    /**
     * Inject a <colgroup> with the given widths into the first <table> in $html.
     * Leaves $html untouched if no <table> is found.
     */
    private function _inject_colgroup(string $html, array $widths): string
    {
        $cols = [];
        foreach ($widths as $w) {
            $w = trim((string) $w);
            if ($w === '') continue;
            $cols[] = '<col style="width:' . rcube_utils::rep_specialchars_output($w) . '">';
        }
        if (empty($cols)) return $html;

        $colgroup = '<colgroup>' . implode('', $cols) . '</colgroup>';

        $res = preg_replace('/(<table\b[^>]*>)/i', '$1' . $colgroup, $html, 1);
        return is_string($res) ? $res : $html;
    }

    /**
     * Force the first <table> in $html to a specific width (default 100%).
     * If the table already has a width, we leave it alone.
     */
    private function _force_first_table_width(string $html, string $width = '100%'): string
    {
        $width = trim($width) ?: '100%';

        $result = preg_replace_callback(
            '/<table\b([^>]*)>/i',
            function ($m) use ($width) {
                $attrs = $m[1];

                if (stripos($attrs, 'style=') === false) {
                    return '<table' . $attrs . ' style="width:' . rcube_utils::rep_specialchars_output($width) . '">';
                }

                if (!preg_match('/width\s*:/i', $attrs)) {
                    $attrs = preg_replace(
                        '/style="([^"]*)"/i',
                        'style="width:' . rcube_utils::rep_specialchars_output($width) . '; $1"',
                        $attrs,
                        1
                    );
                    return '<table' . $attrs . '>';
                }

                return '<table' . $attrs . '>';
            },
            $html,
            1
        );

        return is_string($result) ? $result : $html;
    }
}
