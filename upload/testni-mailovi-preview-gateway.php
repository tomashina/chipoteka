<?php

$previewRoot = __DIR__ . '/testni-mailovi-preview';
$passwordFile = '/home/pluschip/.testni-mailovi-preview.htpasswd';
$username = $_SERVER['PHP_AUTH_USER'] ?? '';
$password = $_SERVER['PHP_AUTH_PW'] ?? '';
$authenticated = false;

if (is_readable($passwordFile)) {
    foreach (file($passwordFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $entry) {
        [$storedUser, $storedHash] = array_pad(explode(':', $entry, 2), 2, '');
        if (hash_equals($storedUser, $username) && password_verify($password, $storedHash)) {
            $authenticated = true;
            break;
        }
    }
}

if (!$authenticated) {
    header('WWW-Authenticate: Basic realm="Chipoteka testni mailovi"');
    http_response_code(401);
    exit('Autorizacija je potrebna.');
}

$relativePath = trim((string) ($_GET['path'] ?? ''), '/');
if ($relativePath === '') {
    $relativePath = 'index.html';
}

$previewRootReal = realpath($previewRoot);
$requestedFile = realpath($previewRoot . '/' . $relativePath);

if ($previewRootReal === false || $requestedFile === false || !is_file($requestedFile)
    || strncmp($requestedFile, $previewRootReal . DIRECTORY_SEPARATOR, strlen($previewRootReal) + 1) !== 0) {
    http_response_code(404);
    exit('Datoteka nije pronađena.');
}

$extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
$contentTypes = [
    'html' => 'text/html; charset=UTF-8',
    'css' => 'text/css; charset=UTF-8',
    'js' => 'application/javascript; charset=UTF-8',
    'svg' => 'image/svg+xml',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
];

header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
readfile($requestedFile);

