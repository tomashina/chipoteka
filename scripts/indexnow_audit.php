<?php

/**
 * Verifies that the public IndexNow key file is reachable on the live host.
 *
 * Usage:
 *   php scripts/indexnow_audit.php https://www.chipoteka.hr
 */

$base_url = isset($argv[1]) && strpos($argv[1], '--') !== 0 ? rtrim($argv[1], '/') : 'https://www.chipoteka.hr';
$upload_dir = realpath(__DIR__ . '/../upload');

if (!$upload_dir) {
    fwrite(STDERR, "Could not locate upload directory.\n");
    exit(1);
}

$key = findIndexNowKey($upload_dir);

if (!$key) {
    fwrite(STDERR, "No IndexNow key file found in upload root.\n");
    exit(1);
}

$key_url = $base_url . '/' . $key['filename'];
$response = fetchUrl($key_url);

if ($response['status'] !== 200) {
    fwrite(STDERR, "IndexNow key file returned HTTP " . $response['status'] . ": " . $key_url . "\n");
    exit(1);
}

if (trim($response['body']) !== $key['key']) {
    fwrite(STDERR, "IndexNow key file body does not match expected key: " . $key_url . "\n");
    exit(1);
}

echo "OK: IndexNow key file is reachable: " . $key_url . "\n";
exit(0);

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

function fetchUrl(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-IndexNowAudit/1.0',
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
