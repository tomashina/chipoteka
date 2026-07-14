<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/openai_product_feed_audit.php <feed-url-or-file> [limit]\n");
    exit(2);
}

$source = $argv[1];
$limit = isset($argv[2]) ? max(1, (int)$argv[2]) : 50;
$content = readFeed($source);

if ($content === false || $content === '') {
    fwrite(STDERR, "Unable to read feed: {$source}\n");
    exit(1);
}

$required_fields = [
    'is_eligible_search',
    'is_eligible_checkout',
    'item_id',
    'title',
    'description',
    'url',
    'brand',
    'image_url',
    'price',
    'availability',
    'seller_name',
    'seller_url',
    'return_policy',
    'target_countries',
    'store_country',
];

$availability_values = ['in_stock', 'out_of_stock', 'pre_order', 'backorder', 'unknown'];
$errors = [];
$checked = 0;
$line_number = 0;

foreach (preg_split('/\R/', $content) as $line) {
    $line_number++;
    $line = trim($line);

    if ($line === '') {
        continue;
    }

    $record = json_decode($line, true);

    if (!is_array($record)) {
        $errors[] = "Line {$line_number}: invalid JSON: " . json_last_error_msg();
        continue;
    }

    $checked++;
    $prefix = 'Line ' . $line_number . ' item ' . ($record['item_id'] ?? '#');

    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $record) || $record[$field] === '' || $record[$field] === []) {
            $errors[] = "{$prefix}: missing {$field}";
        }
    }

    foreach (['is_eligible_search', 'is_eligible_checkout'] as $field) {
        if (isset($record[$field]) && !in_array($record[$field], ['true', 'false'], true)) {
            $errors[] = "{$prefix}: {$field} must be true or false string";
        }
    }

    foreach (['url', 'image_url', 'seller_url', 'return_policy'] as $field) {
        if (!empty($record[$field]) && !preg_match('#^https?://#i', (string)$record[$field])) {
            $errors[] = "{$prefix}: {$field} must be absolute URL";
        }
    }

    foreach (['price', 'sale_price'] as $field) {
        if (!empty($record[$field]) && !preg_match('/^\d+(?:\.\d{2})? [A-Z]{3}$/', (string)$record[$field])) {
            $errors[] = "{$prefix}: {$field} must look like 79.99 EUR";
        }
    }

    if (!empty($record['availability']) && !in_array($record['availability'], $availability_values, true)) {
        $errors[] = "{$prefix}: unsupported availability " . $record['availability'];
    }

    if (!empty($record['description']) && preg_match('/<[^>]+>/', (string)$record['description'])) {
        $errors[] = "{$prefix}: description contains HTML";
    }

    if (!empty($record['gtin']) && !preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', (string)$record['gtin'])) {
        $errors[] = "{$prefix}: gtin must be 8, 12, 13 or 14 digits";
    }

    foreach (['mpn', 'offer_id'] as $field) {
        if (!empty($record[$field]) && isSuspiciousIdentifier((string)$record[$field])) {
            $errors[] = "{$prefix}: {$field} looks like warranty, delivery or policy text";
        }
    }

    if ($checked >= $limit) {
        break;
    }
}

if ($checked === 0) {
    $errors[] = 'No JSONL records found.';
}

if ($errors) {
    echo "FAIL\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "OK: OpenAI product feed JSONL basic fields are valid for {$checked} records.\n";
exit(0);

function readFeed(string $source)
{
    if (is_readable($source) && !preg_match('#^https?://#i', $source)) {
        return file_get_contents($source);
    }

    $ch = curl_init($source);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Chipoteka-OpenAIProductFeedAudit/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        return false;
    }

    return $body;
}

function isSuspiciousIdentifier(string $identifier): bool
{
    $identifier = trim($identifier);

    if ($identifier === '' || $identifier === '-' || $identifier === '0') {
        return true;
    }

    $normalized = strtolower($identifier);

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        if ($converted !== false) {
            $normalized = $converted;
        }
    }

    if (preg_match('/^\d+\s*(godin|mjesec|mj|year|years|month|months)/', $normalized)) {
        return true;
    }

    return (bool)preg_match('/\b(garanc|jamstvo|warranty|dobavlj|naruc|narud|isporuk)\b/', $normalized);
}
