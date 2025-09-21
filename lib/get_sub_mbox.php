<?php
/**
 * Safe helper for listing sub-mailboxes without opening raw IMAP sockets.
 * - Reuses Roundcube's existing storage session.
 * - Never forces a new connection (no localhost:143 fallbacks).
 * - Returns structured data; optional renderer provided for compatibility.
 */

if (!function_exists('account_details_get_sub_mboxes')) {
    function account_details_get_sub_mboxes($root = '') {
        $rc = rcmail::get_instance();
        $storage = $rc->get_storage();

        if (!is_object($storage) || !method_exists($storage, 'is_connected') || !$storage->is_connected()) {
            // No active IMAP session in this task/context
            return [];
        }

        $folders = [];
        try {
            // List all folders under $root. Avoid status calls that might be expensive.
            $list = $storage->list_folders($root, '*');
            if (is_array($list)) {
                foreach ($list as $mbox) {
                    if ($mbox === $root) {
                        continue;
                    }

                    $size_bytes = null;
                    // Some drivers provide folder_size(); guard existence and errors.
                    if (method_exists($storage, 'folder_size')) {
                        try {
                            $sz = $storage->folder_size($mbox);
                            if (is_array($sz)) {
                                // Some backends return arrays like ['size' => <bytes>]
                                $size_bytes = isset($sz['size']) ? (int)$sz['size'] : null;
                            } elseif (is_numeric($sz)) {
                                $size_bytes = (int)$sz;
                            }
                        } catch (Throwable $e) {
                            // Silently ignore size errors; keep listing folders.
                        }
                    }

                    $folders[] = [
                        'name'     => $mbox,
                        'size_kb'  => isset($size_bytes) ? (int)round($size_bytes / 1024) : null,
                        'size_b'   => isset($size_bytes) ? (int)$size_bytes : null,
                    ];
                }
            }
        } catch (Throwable $e) {
            // On any error, return what we have (likely empty)
            return [];
        }

        return $folders;
    }
}

/**
 * Optional renderer for legacy code paths that expect to append rows to a Roundcube html_table.
 * $table should be an instance compatible with $table->add('title', ...); $table->add('value', ...);
 */
if (!function_exists('account_details_render_sub_mboxes')) {
    function account_details_render_sub_mboxes($table, $label_folder = null, $label_kb = null, $root = '') {
        $rc = rcmail::get_instance();
        if ($label_folder === null) {
            $label_folder = $rc->gettext('folder');
        }
        if ($label_kb === null) {
            // fall back to literal if not present in translations
            $label_kb = $rc->gettext('KB') ?: 'KB';
        }

        $rows = account_details_get_sub_mboxes($root);
        foreach ($rows as $row) {
            $fname = rcube_utils::rep_specialchars_output($row['name']);
            $table->add('title', '&nbsp;&#9679;&nbsp;' . rcube_utils::rep_specialchars_output($label_folder . ':'));
            if (isset($row['size_kb'])) {
                $table->add('value', $fname . '&nbsp; ' . (int)$row['size_kb'] . '&nbsp;' . rcube_utils::rep_specialchars_output($label_kb));
            } else {
                $table->add('value', $fname);
            }
        }
    }
}
?>
