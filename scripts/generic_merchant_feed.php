<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/generic_merchant_feed.php <openai-jsonl-url-or-file> [output-tsv] [limit]\n");
    exit(2);
}

$source = $argv[1];
$output_file = $argv[2] ?? '';
$limit = isset($argv[3]) ? max(1, (int)$argv[3]) : 0;
$content = readFeed($source);

if ($content === false || $content === '') {
    fwrite(STDERR, "Unable to read feed: {$source}\n");
    exit(1);
}

$headers = [
    'id',
    'title',
    'description',
    'link',
    'image_link',
    'additional_image_link',
    'availability',
    'price',
    'sale_price',
    'brand',
    'gtin',
    'mpn',
    'identifier_exists',
    'condition',
    'product_type',
    'shipping',
];

$required_fields = [
    'id',
    'title',
    'description',
    'link',
    'image_link',
    'availability',
    'price',
    'condition',
];

$recommended_fields = [
    'brand',
    'gtin',
    'mpn',
    'product_type',
    'shipping',
];

$rows = [];
$stats = [
    'records' => 0,
    'exportable' => 0,
    'missing_required' => [],
    'recommended_coverage' => array_fill_keys($recommended_fields, 0),
    'availability' => [],
    'currencies' => [],
    'identifier_exists' => [],
    'target_countries' => [],
    'errors' => [],
];

$line_number = 0;

foreach (preg_split('/\R/', $content) as $line) {
    $line_number++;
    $line = trim($line);

    if ($line === '') {
        continue;
    }

    $record = json_decode($line, true);

    if (!is_array($record)) {
        $stats['errors'][] = "Line {$line_number}: invalid JSON: " . json_last_error_msg();
        continue;
    }

    $stats['records']++;
    $row = mapRecord($record);
    $has_required = true;

    foreach ($required_fields as $field) {
        if ($row[$field] === '') {
            increment($stats['missing_required'], $field);
            $has_required = false;
        }
    }

    foreach ($recommended_fields as $field) {
        if ($row[$field] !== '') {
            $stats['recommended_coverage'][$field]++;
        }
    }

    increment($stats['availability'], $row['availability'] ?: 'missing');
    increment($stats['identifier_exists'], $row['identifier_exists'] ?: 'missing');

    if ($row['price'] !== '' && preg_match('/\s([A-Z]{3})$/', $row['price'], $match)) {
        increment($stats['currencies'], $match[1]);
    } else {
        increment($stats['currencies'], 'missing_or_invalid');
        $has_required = false;
    }

    foreach ((array)($record['target_countries'] ?? ['missing']) as $country) {
        increment($stats['target_countries'], (string)$country);
    }

    if ($has_required) {
        $stats['exportable']++;
        $rows[] = $row;
    }

    if ($limit > 0 && $stats['records'] >= $limit) {
        break;
    }
}

if ($stats['records'] === 0) {
    $stats['errors'][] = 'No JSONL records found.';
}

if ($output_file !== '') {
    writeTsv($output_file, $headers, $rows);
}

echo "Generic merchant feed compatibility\n";
echo "===================================\n\n";
echo "Source: {$source}\n";
echo "Records checked: " . $stats['records'] . "\n";
echo "Exportable rows: " . $stats['exportable'] . "\n";

if ($output_file !== '') {
    echo "Output TSV: {$output_file}\n";
}

echo "\nGoogle/market note\n";
echo "------------------\n";
echo "This is a generic Google-like merchant TSV spine. Google Shopping ads/free listings availability depends on target country, language, currency, landing pages, shipping and Merchant Center settings. As of 2026-07-14, official Google supported countries/languages do not list Croatia/Croatian, so use this as future-market/channel compatibility, not a live HR Google launch promise.\n";

echo "\nRequired field gaps\n";
echo "-------------------\n";

if ($stats['missing_required']) {
    foreach ($stats['missing_required'] as $field => $count) {
        echo "{$field}: {$count}\n";
    }
} else {
    echo "OK: all checked records have the generic required field spine.\n";
}

echo "\nCatalog signals\n";
echo "---------------\n";
printCounts('availability', $stats['availability']);
printCounts('currencies', $stats['currencies']);
printCounts('identifier_exists', $stats['identifier_exists']);
printCounts('target_countries', $stats['target_countries']);

echo "\nRecommended field coverage\n";
echo "--------------------------\n";
ksort($stats['recommended_coverage']);

foreach ($stats['recommended_coverage'] as $field => $count) {
    $percent = $stats['records'] > 0 ? number_format(($count / $stats['records']) * 100, 1, '.', '') : '0.0';
    echo "{$field}: {$count}/{$stats['records']} ({$percent}%)\n";
}

if ($stats['errors']) {
    echo "\nErrors\n";
    echo "------\n";
    foreach ($stats['errors'] as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "\nREADY: generic merchant compatibility completed for checked records.\n";
exit(0);

function mapRecord(array $record): array
{
    $gtin = trim((string)($record['gtin'] ?? ''));
    $mpn = trim((string)($record['mpn'] ?? ''));
    $brand = trim((string)($record['brand'] ?? ''));
    $additional_images = trim((string)($record['additional_image_urls'] ?? ''));

    return [
        'id' => trim((string)($record['item_id'] ?? '')),
        'title' => trim((string)($record['title'] ?? '')),
        'description' => cleanText($record['description'] ?? ''),
        'link' => trim((string)($record['url'] ?? '')),
        'image_link' => trim((string)($record['image_url'] ?? '')),
        'additional_image_link' => $additional_images,
        'availability' => mapAvailability((string)($record['availability'] ?? '')),
        'price' => trim((string)($record['price'] ?? '')),
        'sale_price' => trim((string)($record['sale_price'] ?? '')),
        'brand' => $brand,
        'gtin' => $gtin,
        'mpn' => $mpn,
        'identifier_exists' => ($gtin !== '' || ($brand !== '' && $mpn !== '')) ? 'yes' : 'no',
        'condition' => trim((string)($record['condition'] ?? 'new')) ?: 'new',
        'product_type' => trim((string)($record['product_category'] ?? '')),
        'shipping' => trim((string)($record['shipping'] ?? '')),
    ];
}

function mapAvailability(string $availability): string
{
    switch ($availability) {
        case 'in_stock':
            return 'in stock';
        case 'out_of_stock':
            return 'out of stock';
        case 'pre_order':
            return 'preorder';
        case 'backorder':
            return 'backorder';
        case 'unknown':
            return 'out of stock';
        default:
            return '';
    }
}

function cleanText($text): string
{
    $text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8');
    $clean = preg_replace('/\s+/u', ' ', $text);

    if ($clean !== null) {
        $text = $clean;
    }

    return trim($text);
}

function readFeed(string $source)
{
    if (is_readable($source) && !preg_match('#^https?://#i', $source)) {
        $body = file_get_contents($source);

        return decodeIfGzip($source, $body);
    }

    $ch = curl_init($source);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'Chipoteka-GenericMerchantFeed/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        return false;
    }

    return decodeIfGzip($source, $body);
}

function decodeIfGzip(string $source, $body)
{
    if (!is_string($body)) {
        return false;
    }

    if (substr($source, -3) !== '.gz') {
        return $body;
    }

    $decoded = function_exists('gzdecode') ? gzdecode($body) : false;

    return $decoded !== false ? $decoded : false;
}

function writeTsv(string $output_file, array $headers, array $rows): void
{
    $handle = fopen($output_file, 'w');

    if (!$handle) {
        fwrite(STDERR, "Unable to write TSV: {$output_file}\n");
        exit(1);
    }

    fputcsv($handle, $headers, "\t", '"', "\\");

    foreach ($rows as $row) {
        $line = [];

        foreach ($headers as $header) {
            $line[] = $row[$header] ?? '';
        }

        fputcsv($handle, $line, "\t", '"', "\\");
    }

    fclose($handle);
}

function increment(array &$counts, string $key): void
{
    if ($key === '') {
        $key = 'empty';
    }

    if (!isset($counts[$key])) {
        $counts[$key] = 0;
    }

    $counts[$key]++;
}

function printCounts(string $label, array $counts): void
{
    arsort($counts);

    foreach ($counts as $value => $count) {
        echo "{$label}.{$value}: {$count}\n";
    }
}
