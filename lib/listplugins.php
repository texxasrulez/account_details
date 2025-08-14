<?php
function rcmail_ad_plugin_list($attrib)
{
    $rcmail = rcmail::get_instance();

    // Make sure we always have an array here (callers sometimes pass bool/null)
    $attrib = is_array($attrib) ? $attrib : [];

    if (empty($attrib['id'])) {
        $attrib['id'] = 'rcmpluginlist';
    }

    // Active plugins may be unset/false; normalize to array to avoid PHP 8.1+ deprecations
    $plugins_raw = isset($rcmail->plugins->active_plugins) ? $rcmail->plugins->active_plugins : [];
    $plugins     = array_filter(is_array($plugins_raw) ? $plugins_raw : []);

    $plugin_info = [];

    foreach ($plugins as $name) {
        $info = $rcmail->plugins->get_info($name);
        if (is_array($info) && !empty($info)) {
            $plugin_info[$name] = $info;
        }
    }

    // Include info for required plugins as well
    foreach ($plugin_info as $name => $info) {
        if (!empty($info['require']) && is_array($info['require'])) {
            foreach ($info['require'] as $req_name) {
                if (!isset($plugin_info[$req_name])) {
                    $req_info = $rcmail->plugins->get_info($req_name);
                    if (is_array($req_info) && !empty($req_info)) {
                        $plugin_info[$req_name] = $req_info;
                    }
                }
            }
        }
    }

    if (empty($plugin_info)) {
        return '';
    }

    ksort($plugin_info, SORT_LOCALE_STRING);

    $table = new html_table($attrib);

    // Header
    $table->add_header('name',    $rcmail->gettext('plugin'));
    $table->add_header('version', $rcmail->gettext('version'));
    $table->add_header('license', $rcmail->gettext('license'));
    $table->add_header('source',  $rcmail->gettext('source'));

    foreach ($plugin_info as $name => $data) {
        // Build Source link
        $uri = '';
        if (!empty($data['src_uri'])) {
            $uri = $data['src_uri'];
        } elseif (!empty($data['uri'])) {
            $uri = $data['uri'];
        }

        if ($uri && stripos($uri, 'http') !== 0) {
            $uri = 'http://' . $uri;
        }

        $uri_cell = $uri
            ? html::a(['target' => '_blank', 'href' => rcube::Q($uri)], rcube::Q($rcmail->gettext('download')))
            : '';

        // Build License cell
        $license_text = isset($data['license']) ? $data['license'] : '';
        if (!empty($data['license_uri'])) {
            $license_cell = html::a(['target' => '_blank', 'href' => rcube::Q($data['license_uri'])], rcube::Q($license_text));
        } else {
            $license_cell = rcube::Q($license_text);
        }

        $table->add_row();
        $table->add('name',    rcube::Q(!empty($data['name']) ? $data['name'] : $name));
        $table->add('version', !empty($data['version']) ? rcube::Q($data['version']) : '');
        $table->add('license', $license_cell);
        $table->add('source',  $uri_cell);
    }

    return $table->show();
}
?>
