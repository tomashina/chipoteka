<?php

/**
 * Checks page and image URLs inside a sitemap file.
 *
 * Usage:
 *   php scripts/sitemap_url_audit.php https://www.chipoteka.hr/sitemaps/sitemap_0_2_product_12.xml 50
 */

$sitemap_url = isset($argv[1]) ? $argv[1] : '';
$limit = isset($argv[2]) ? (int)$argv[2] : 50;

if (!$sitemap_url) {
    fwrite(STDERR, 'Usage: php scripts/sitemap_url_audit.php <sitemap-url> [limit]' . PHP_EOL);
    exit(2);
}

$xml = fetchBody($sitemap_url);

if ($xml === null) {
    fwrite(STDERR, 'Could not fetch sitemap: ' . $sitemap_url . PHP_EOL);
    exit(1);
}

libxml_use_internal_errors(true);
$sitemap = simplexml_load_string($xml);

if (!$sitemap) {
    fwrite(STDERR, 'Could not parse sitemap XML: ' . $sitemap_url . PHP_EOL);
    exit(1);
}

$sitemap->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$sitemap->registerXPathNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');

$urls = $sitemap->xpath('//sm:url') ?: [];
$rows = [];
$checked = 0;
$has_failure = false;

foreach ($urls as $url) {
    if ($limit > 0 && $checked >= $limit) {
        break;
    }

    $loc = trim((string)$url->children('http://www.sitemaps.org/schemas/sitemap/0.9')->loc);

    if ($loc) {
        $status = fetchStatus($loc);
        $rows[] = ['type' => 'page', 'status' => $status, 'url' => $loc];

        if ($status >= 400 || $status === 0) {
            $has_failure = true;
        }
    }

    foreach ($url->xpath('image:image/image:loc') ?: [] as $image_loc) {
        $image_url = trim((string)$image_loc);
        $status = fetchStatus($image_url);
        $rows[] = ['type' => 'image', 'status' => $status, 'url' => $image_url];

        if ($status >= 400 || $status === 0) {
            $has_failure = true;
        }
    }

    $checked++;
}

printRows($rows);

if ($has_failure) {
    exit(1);
}

exit(0);

function fetchBody($url) {
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'ChipotekaSitemapAudit/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($status >= 400 || $body === false) {
        return null;
    }

    return $body;
}

function fetchStatus($url) {
    if (!function_exists('curl_init')) {
        return 0;
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'ChipotekaSitemapAudit/1.0',
    ]);

    curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return $status;
}

function printRows(array $rows) {
    $width_type = strlen('type');
    $width_status = strlen('status');

    foreach ($rows as $row) {
        $width_type = max($width_type, strlen($row['type']));
        $width_status = max($width_status, strlen((string)$row['status']));
    }

    echo str_pad('type', $width_type) . '  ' . str_pad('status', $width_status) . '  url' . PHP_EOL;
    echo str_repeat('-', $width_type) . '  ' . str_repeat('-', $width_status) . '  ---' . PHP_EOL;

    foreach ($rows as $row) {
        echo str_pad($row['type'], $width_type) . '  ' . str_pad((string)$row['status'], $width_status) . '  ' . $row['url'] . PHP_EOL;
    }
}
