<?php

/**
 * Submits queued URL changes to IndexNow.
 *
 * Usage:
 *   php scripts/indexnow_submit.php https://www.chipoteka.hr
 *   php scripts/indexnow_submit.php https://www.chipoteka.hr --limit=250
 *   php scripts/indexnow_submit.php https://www.chipoteka.hr --dry-run
 */

$options = parseArguments($argv);

if ($options['help']) {
    printUsage();
    exit(0);
}

$upload_dir = realpath(__DIR__ . '/../upload');

if (!$upload_dir) {
    fwrite(STDERR, "Could not locate upload directory.\n");
    exit(1);
}

bootstrapOpenCartConfig($upload_dir);

$base_url = rtrim($options['base_url'] ?: defaultBaseUrl(), '/');
$key = findIndexNowKey($upload_dir);

if (!$key) {
    fwrite(STDERR, "No IndexNow key file found in upload root.\n");
    exit(1);
}

$db = connectDatabase();
ensureQueueTable($db);

$rows = loadQueueRows($db, $options['limit'], $options['retry_failed']);

if (!$rows) {
    echo "OK: No pending IndexNow URLs.\n";
    exit(0);
}

$urls = array_values(array_unique(array_column($rows, 'url')));
$payload = [
    'host' => parse_url($base_url, PHP_URL_HOST),
    'key' => $key['key'],
    'keyLocation' => $base_url . '/' . $key['filename'],
    'urlList' => $urls
];

if ($options['dry_run']) {
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    echo "DRY RUN: " . count($rows) . " queue rows, " . count($urls) . " unique URLs.\n";
    exit(0);
}

$response = submitIndexNow($options['endpoint'], $payload);
$ids = array_map('intval', array_column($rows, 'indexnow_queue_id'));

if (in_array($response['status'], [200, 202], true)) {
    markRowsSubmitted($db, $ids, $response['status']);
    echo "OK: Submitted " . count($urls) . " unique URLs to IndexNow. HTTP " . $response['status'] . ".\n";
    exit(0);
}

markRowsFailed($db, $rows, $response['status'], $response['error'] ?: $response['body']);
fwrite(STDERR, "FAIL: IndexNow returned HTTP " . $response['status'] . ". " . trim($response['error'] ?: $response['body']) . "\n");
exit(1);

function parseArguments(array $argv): array
{
    $options = [
        'base_url' => '',
        'endpoint' => 'https://api.indexnow.org/indexnow',
        'limit' => 1000,
        'retry_failed' => false,
        'dry_run' => false,
        'help' => false,
    ];

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--dry-run') {
            $options['dry_run'] = true;
        } elseif ($argument === '--retry-failed') {
            $options['retry_failed'] = true;
        } elseif ($argument === '--help' || $argument === '-h') {
            $options['help'] = true;
        } elseif (strpos($argument, '--limit=') === 0) {
            $options['limit'] = max(1, min(10000, (int)substr($argument, 8)));
        } elseif (strpos($argument, '--endpoint=') === 0) {
            $options['endpoint'] = substr($argument, 11);
        } elseif (strpos($argument, '--') === 0) {
            fwrite(STDERR, "Unknown option: {$argument}\n");
            $options['help'] = true;
        } else {
            $options['base_url'] = $argument;
        }
    }

    return $options;
}

function printUsage(): void
{
    echo "Usage: php scripts/indexnow_submit.php [base-url] [--limit=1000] [--dry-run] [--retry-failed] [--endpoint=https://api.indexnow.org/indexnow]\n";
}

function bootstrapOpenCartConfig(string $upload_dir): void
{
    $config = $upload_dir . '/config.php';
    $env = $upload_dir . '/env.php';

    if (is_file($config)) {
        require_once $config;
    }

    if (is_file($env)) {
        require_once $env;
    }

    if (!defined('DB_HOSTNAME') || !defined('DB_USERNAME') || !defined('DB_DATABASE') || !defined('DB_PREFIX')) {
        fwrite(STDERR, "OpenCart DB constants are not defined.\n");
        exit(1);
    }
}

function defaultBaseUrl(): string
{
    if (defined('HTTPS_CATALOG')) {
        return HTTPS_CATALOG;
    }

    if (defined('HTTPS_SERVER')) {
        return HTTPS_SERVER;
    }

    if (defined('HTTP_CATALOG')) {
        return HTTP_CATALOG;
    }

    return HTTP_SERVER;
}

function connectDatabase(): mysqli
{
    $db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, defined('DB_PORT') ? (int)DB_PORT : 3306);

    if ($db->connect_error) {
        fwrite(STDERR, "Database connection failed: " . $db->connect_error . "\n");
        exit(1);
    }

    $db->set_charset('utf8');

    return $db;
}

function ensureQueueTable(mysqli $db): void
{
    $db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "indexnow_queue` (
        `indexnow_queue_id` int(11) NOT NULL AUTO_INCREMENT,
        `url` varchar(2048) NOT NULL,
        `entity_type` varchar(32) NOT NULL DEFAULT '',
        `entity_id` int(11) NOT NULL DEFAULT 0,
        `action` varchar(16) NOT NULL DEFAULT 'updated',
        `status` varchar(16) NOT NULL DEFAULT 'pending',
        `attempts` int(11) NOT NULL DEFAULT 0,
        `last_status_code` int(11) DEFAULT NULL,
        `last_error` text,
        `available_at` datetime NOT NULL,
        `submitted_at` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`indexnow_queue_id`),
        KEY `indexnow_status_available` (`status`, `available_at`),
        KEY `indexnow_entity` (`entity_type`, `entity_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
}

function loadQueueRows(mysqli $db, int $limit, bool $retry_failed): array
{
    $status_sql = $retry_failed ? "status IN ('pending', 'failed')" : "status = 'pending'";
    $result = $db->query("SELECT * FROM `" . DB_PREFIX . "indexnow_queue` WHERE {$status_sql} AND available_at <= NOW() ORDER BY created_at ASC LIMIT " . (int)$limit);
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function findIndexNowKey(string $upload_dir): ?array
{
    $files = glob($upload_dir . '/*.txt') ?: [];
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        $key = substr($filename, 0, -4);

        if (!preg_match('/^[A-Za-z0-9-]{8,128}$/', $key)) {
            continue;
        }

        if (trim((string)file_get_contents($file)) !== $key) {
            continue;
        }

        return [
            'filename' => $filename,
            'key' => $key
        ];
    }

    return null;
}

function submitIndexNow(string $endpoint, array $payload): array
{
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-IndexNowSubmit/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'body' => $body === false ? '' : $body,
        'error' => $error
    ];
}

function markRowsSubmitted(mysqli $db, array $ids, int $status_code): void
{
    if (!$ids) {
        return;
    }

    $db->query("UPDATE `" . DB_PREFIX . "indexnow_queue` SET status = 'submitted', attempts = attempts + 1, last_status_code = '" . (int)$status_code . "', last_error = '', submitted_at = NOW(), updated_at = NOW() WHERE indexnow_queue_id IN (" . implode(',', $ids) . ")");
}

function markRowsFailed(mysqli $db, array $rows, int $status_code, string $error): void
{
    $error = $db->real_escape_string(substr($error, 0, 1000));

    foreach ($rows as $row) {
        $attempts = (int)$row['attempts'] + 1;
        $status = $attempts >= 5 ? 'failed' : 'pending';
        $delay = min(60, (int)pow(2, min(5, $attempts)));

        $db->query("UPDATE `" . DB_PREFIX . "indexnow_queue` SET status = '" . $status . "', attempts = '" . $attempts . "', last_status_code = '" . (int)$status_code . "', last_error = '" . $error . "', available_at = DATE_ADD(NOW(), INTERVAL " . $delay . " MINUTE), updated_at = NOW() WHERE indexnow_queue_id = '" . (int)$row['indexnow_queue_id'] . "'");
    }
}
