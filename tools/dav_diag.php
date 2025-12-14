<?php
declare(strict_types=1);
define('ACCOUNT_DETAILS_DIAG', true);

$plugin_dir = realpath(__DIR__ . '/..');
$rc_root    = realpath(__DIR__ . '/../../..');

if ($rc_root && file_exists($rc_root . '/program/include/iniset.php')) {
    if (!defined('INSTALL_PATH')) {
        define('INSTALL_PATH', rtrim($rc_root, '/') . '/');
    }
    require_once INSTALL_PATH . 'program/include/iniset.php';
}

require_once $plugin_dir . '/account_details.php';

$rcmail = class_exists('rcmail') ? rcmail::get_instance() : null;
if (!$rcmail || !$rcmail->user || !$rcmail->user->ID) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No active Roundcube session']);
    exit;
}

$plugin = new account_details($rcmail);
$plugin->init(); // loads config and sets template overrides
$resources = $plugin->get_dav_resources();

header('Content-Type: application/json');
echo json_encode($resources, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
