<?php
function rcmail_ad_plugin_list($attrib)
{
 
        $rcmail = rcmail::get_instance();

        if (empty($attrib['id'])) {
            $attrib['id'] = 'rcmpluginlist';
        }

        $plugins     = array_filter($rcmail->plugins->active_plugins);
        $plugin_info = [];

        foreach ($plugins as $name) {
            if ($info = $rcmail->plugins->get_info($name)) {
                $plugin_info[$name] = $info;
            }
        }

        // load info from required plugins, too
        foreach ($plugin_info as $name => $info) {
            if (!empty($info['require']) && is_array($info['require'])) {
                foreach ($info['require'] as $req_name) {
                    if (!isset($plugin_info[$req_name]) && ($req_info = $rcmail->plugins->get_info($req_name))) {
                        $plugin_info[$req_name] = $req_info;
                    }
                }
            }
        }

        if (empty($plugin_info)) {
            return '';
        }

        ksort($plugin_info, SORT_LOCALE_STRING);

        $table = new html_table($attrib);

        // add table header
        $table->add_header('name', $rcmail->gettext('plugin'));
        $table->add_header('version', $rcmail->gettext('version'));
        $table->add_header('license', $rcmail->gettext('license'));
        $table->add_header('source', $rcmail->gettext('source'));

        foreach ($plugin_info as $name => $data) {
            $uri = !empty($data['src_uri']) ? $data['src_uri'] : ($data['uri'] ?? '');
            if ($uri && stripos($uri, 'http') !== 0) {
                $uri = 'http://' . $uri;
            }

            if ($uri) {
                $uri = html::a([
                        'target' => '_blank',
                        'href'   => rcube::Q($uri)
                    ],
                    rcube::Q($rcmail->gettext('download'))
                );
            }

            $license = isset($data['license']) ? $data['license'] : '';

            if (!empty($data['license_uri'])) {
                $license = html::a([
                        'target' => '_blank',
                        'href' => rcube::Q($data['license_uri'])
                    ],
                    rcube::Q($data['license'])
                );
            }
            else {
                $license = rcube::Q($license);
            }

            $table->add_row();
            $table->add('name', rcube::Q(!empty($data['name']) ? $data['name'] : $name));
            $table->add('version', !empty($data['version']) ? rcube::Q($data['version']) : '');
            $table->add('license', $license);
            $table->add('source', $uri);
        }

        return $table->show();
    }
?>
