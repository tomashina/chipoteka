<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/openai_merchant_readiness.php <feed-url-or-file> [max-records]\n");
    exit(2);
}

$source = $argv[1];
$max_records = isset($argv[2]) ? max(1, (int)$argv[2]) : 0;
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

$optional_fields = [
    'gtin',
    'mpn',
    'condition',
    'product_category',
    'additional_image_urls',
    'sale_price',
    'pricing_trend',
    'weight',
    'item_weight_unit',
    'dimensions',
    'review_count',
    'star_rating',
    'shipping',
    'seller_privacy_policy',
    'seller_tos',
];

$stats = [
    'records' => 0,
    'unique_item_ids' => [],
    'missing_required' => [],
    'optional_coverage' => array_fill_keys($optional_fields, 0),
    'availability' => [],
    'currencies' => [],
    'target_countries' => [],
    'store_countries' => [],
    'eligible_search' => [],
    'eligible_checkout' => [],
    'categories' => [],
    'seller_urls' => [],
    'return_policies' => [],
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
    $item_id = (string)($record['item_id'] ?? ('line-' . $line_number));
    $stats['unique_item_ids'][$item_id] = true;

    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $record) || $record[$field] === '' || $record[$field] === []) {
            increment($stats['missing_required'], $field);
        }
    }

    foreach ($optional_fields as $field) {
        if (array_key_exists($field, $record) && $record[$field] !== '' && $record[$field] !== []) {
            $stats['optional_coverage'][$field]++;
        }
    }

    increment($stats['availability'], (string)($record['availability'] ?? 'missing'));
    increment($stats['eligible_search'], (string)($record['is_eligible_search'] ?? 'missing'));
    increment($stats['eligible_checkout'], (string)($record['is_eligible_checkout'] ?? 'missing'));
    increment($stats['seller_urls'], (string)($record['seller_url'] ?? 'missing'));
    increment($stats['return_policies'], (string)($record['return_policy'] ?? 'missing'));

    if (!empty($record['price']) && preg_match('/\s([A-Z]{3})$/', (string)$record['price'], $match)) {
        increment($stats['currencies'], $match[1]);
    } else {
        increment($stats['currencies'], 'missing_or_invalid');
    }

    foreach ((array)($record['target_countries'] ?? ['missing']) as $country) {
        increment($stats['target_countries'], (string)$country);
    }

    increment($stats['store_countries'], (string)($record['store_country'] ?? 'missing'));

    if (!empty($record['product_category'])) {
        increment($stats['categories'], (string)$record['product_category']);
    }

    if ($max_records > 0 && $stats['records'] >= $max_records) {
        break;
    }
}

if ($stats['records'] === 0) {
    $stats['errors'][] = 'No JSONL records found.';
}

ksort($stats['optional_coverage']);

echo "OpenAI/ChatGPT merchant readiness\n";
echo "=================================\n\n";
echo "Source: {$source}\n";
echo "Records checked: " . $stats['records'] . "\n";
echo "Unique item_id count: " . count($stats['unique_item_ids']) . "\n";
echo "Likely OpenAI feed size bucket: " . getFeedSizeBucket(count($stats['unique_item_ids'])) . "\n\n";

echo "Merchant application copy/paste\n";
echo "-------------------------------\n";
echo "Merchant website: https://www.chipoteka.hr\n";
echo "Headquarter country: Croatia\n";
echo "Primary Product Categories: Electronics & Appliances\n";
echo "Interest: Integrating my product feed so my products show up in search results on ChatGPT\n";
echo "Feed URL for validation: https://www.chipoteka.hr/openai-products.jsonl\n";
echo "Delivery note: HTTPS JSONL is ready for QA; once approved, publish the same full snapshot as stable jsonl.gz via OpenAI SFTP.\n\n";

echo "Required fields\n";
echo "---------------\n";

if ($stats['missing_required']) {
    foreach ($stats['missing_required'] as $field => $count) {
        echo "MISSING {$field}: {$count}\n";
    }
} else {
    echo "OK: all required fields present in checked records.\n";
}

echo "\nEligibility\n";
echo "-----------\n";
printCounts('is_eligible_search', $stats['eligible_search']);
printCounts('is_eligible_checkout', $stats['eligible_checkout']);

echo "\nCatalog signals\n";
echo "---------------\n";
printCounts('availability', $stats['availability']);
printCounts('currencies', $stats['currencies']);
printCounts('target_countries', $stats['target_countries']);
printCounts('store_countries', $stats['store_countries']);

echo "\nOptional field coverage\n";
echo "-----------------------\n";
foreach ($stats['optional_coverage'] as $field => $count) {
    $percent = $stats['records'] > 0 ? number_format(($count / $stats['records']) * 100, 1, '.', '') : '0.0';
    echo "{$field}: {$count}/{$stats['records']} ({$percent}%)\n";
}

echo "\nTop categories\n";
echo "--------------\n";
arsort($stats['categories']);
$top_categories = array_slice($stats['categories'], 0, 10, true);

if ($top_categories) {
    foreach ($top_categories as $category => $count) {
        echo "{$category}: {$count}\n";
    }
} else {
    echo "No product_category values found.\n";
}

echo "\nDelivery gaps before OpenAI production ingestion\n";
echo "------------------------------------------------\n";
echo "- OpenAI file-upload delivery expects a full snapshot pushed through supported channels such as SFTP.\n";
echo "- Prepare a stable compressed file name, for example chipoteka-products.jsonl.gz.\n";
echo "- Run this readiness script and scripts/openai_product_feed_audit.php on the same file before upload.\n";
echo "- Keep a predictable refresh cadence, at least daily.\n";

if ($stats['errors']) {
    echo "\nErrors\n";
    echo "------\n";
    foreach ($stats['errors'] as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "\nREADY: merchant prep checks completed for checked records.\n";
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
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'Chipoteka-OpenAIMerchantReadiness/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        return false;
    }

    return $body;
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

function getFeedSizeBucket(int $count): string
{
    if ($count <= 1000000) {
        return '0-1M';
    }

    if ($count <= 10000000) {
        return '1-10M';
    }

    if ($count <= 50000000) {
        return '10-50M';
    }

    if ($count <= 100000000) {
        return '50-100M';
    }

    return '100M+';
}
