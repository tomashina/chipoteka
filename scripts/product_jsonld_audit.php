<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/product_jsonld_audit.php <product-url>\n");
    exit(2);
}

$url = $argv[1];
$html = fetchUrl($url);

if ($html === false || $html === '') {
    fwrite(STDERR, "Unable to fetch URL: {$url}\n");
    exit(1);
}

preg_match_all(
    '#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is',
    $html,
    $matches
);

if (empty($matches[1])) {
    fwrite(STDERR, "No application/ld+json blocks found.\n");
    exit(1);
}

$errors = [];
$product_found = false;

foreach ($matches[1] as $index => $json) {
    $json = html_entity_decode(trim($json), ENT_QUOTES, 'UTF-8');
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        $errors[] = 'Block #' . ($index + 1) . ' is invalid JSON: ' . json_last_error_msg();
        continue;
    }

    $products = findSchemaNodes($decoded, 'Product');

    foreach ($products as $product) {
        $product_found = true;
        $prefix = 'Product ' . ($product['@id'] ?? $product['name'] ?? '#' . ($index + 1));

        foreach (['name', 'url', 'description', 'sku', 'offers'] as $field) {
            if (empty($product[$field])) {
                $errors[] = "{$prefix}: missing {$field}";
            }
        }

        if (empty($product['brand']['name'])) {
            $errors[] = "{$prefix}: missing brand.name";
        }

        foreach (['gtin', 'gtin8', 'gtin12', 'gtin13', 'gtin14'] as $gtin_field) {
            if (!empty($product[$gtin_field]) && !isValidGtin((string)$product[$gtin_field], $gtin_field)) {
                $errors[] = "{$prefix}: invalid {$gtin_field}";
            }
        }

        $offers = isset($product['offers'][0]) ? $product['offers'] : [$product['offers'] ?? []];

        foreach ($offers as $offer_index => $offer) {
            foreach (['price', 'priceCurrency', 'availability', 'url', 'validFrom', 'shippingDetails', 'hasMerchantReturnPolicy'] as $field) {
                if (empty($offer[$field])) {
                    $errors[] = "{$prefix}: offer #" . ($offer_index + 1) . " missing {$field}";
                }
            }

            if (!empty($offer['price']) && !is_numeric((string)$offer['price'])) {
                $errors[] = "{$prefix}: offer #" . ($offer_index + 1) . " price is not numeric";
            }
        }
    }
}

if (!$product_found) {
    $errors[] = 'No Product schema found.';
}

if ($errors) {
    echo "FAIL\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "OK: Product JSON-LD found and basic fields are valid.\n";
exit(0);

function fetchUrl(string $url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-ProductJsonLdAudit/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        return false;
    }

    return $body;
}

function findSchemaNodes(array $node, string $type): array
{
    $found = [];

    if (isset($node['@type'])) {
        $types = is_array($node['@type']) ? $node['@type'] : [$node['@type']];

        if (in_array($type, $types, true)) {
            $found[] = $node;
        }
    }

    foreach ($node as $value) {
        if (is_array($value)) {
            $found = array_merge($found, findSchemaNodes($value, $type));
        }
    }

    return $found;
}

function isValidGtin(string $gtin, string $field): bool
{
    $gtin = preg_replace('/\D+/', '', $gtin);
    $length = strlen($gtin);

    if ($field !== 'gtin' && $field !== 'gtin' . $length) {
        return false;
    }

    if (!in_array($length, [8, 12, 13, 14], true)) {
        return false;
    }

    $check_digit = (int)substr($gtin, -1);
    $payload = strrev(substr($gtin, 0, -1));
    $sum = 0;

    for ($index = 0, $payload_length = strlen($payload); $index < $payload_length; $index++) {
        $sum += (int)$payload[$index] * ($index % 2 === 0 ? 3 : 1);
    }

    return (10 - ($sum % 10)) % 10 === $check_digit;
}
