<?php
// Simple diagnostic harness for the discovery service.
// URL: plugins/account_details/tools/dav_diag.php?_davdebug=1
declare(strict_types=1);

@ini_set('display_errors', '1');
@error_reporting(E_ALL);

$here = __DIR__;
$root = realpath($here . '/../../');

// Bootstrap Roundcube if possible
if (file_exists($root . '/program/include/iniset.php')) {
    require_once $root . '/program/include/iniset.php';
}

// Load service file
require_once $root . '/plugins/account_details/lib/DavDiscoveryService.php';

// Acquire an rcmail instance if available
$rc = class_exists('rcmail') ? rcmail::get_instance() : null;

// Get PDO handle either from Roundcube db or fail
$pdo = null;
if ($rc && $rc->db && method_exists($rc->db, 'dbh')) {
    $pdo = $rc->db->dbh;
} elseif ($rc && $rc->db) {
    // Older RC versions might expose a public dbh property
    $pdo = isset($rc->db->dbh) ? $rc->db->dbh : null;
}

if (!$pdo instanceof PDO) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No PDO handle available']);
    exit;
}

// Instantiate using any of the exposed names
$svc = null;
if (class_exists('AccountDetails\Lib\DavDiscoveryService')) {
    $svc = new \AccountDetails\Lib\DavDiscoveryService($pdo, $rc);
} elseif (class_exists('AccountDetails\DavDiscoveryService')) {
    $svc = new \AccountDetails\DavDiscoveryService($pdo, $rc);
} elseif (class_exists('DavDiscoveryService')) {
    $svc = new \DavDiscoveryService($pdo, $rc);
}

if (!$svc) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Service class not found']);
    exit;
}

$result = $svc->discover();

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
