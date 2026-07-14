<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/policy_schema_audit.php <url-or-file> [policy|faq|locations|service]\n");
    exit(2);
}

$source = $argv[1];
$mode = $argv[2] ?? 'policy';

if (!in_array($mode, ['policy', 'faq', 'locations', 'service'], true)) {
    fwrite(STDERR, "Mode must be policy, faq, locations or service.\n");
    exit(2);
}

$html = readSource($source);

if ($html === false || $html === '') {
    fwrite(STDERR, "Unable to read source: {$source}\n");
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
$decoded_blocks = [];

foreach ($matches[1] as $index => $json) {
    $json = html_entity_decode(trim($json), ENT_QUOTES, 'UTF-8');
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        $errors[] = 'Block #' . ($index + 1) . ' is invalid JSON: ' . json_last_error_msg();
        continue;
    }

    $decoded_blocks[] = $decoded;
}

$web_pages = [];
$contact_pages = [];
$faq_pages = [];
$organizations = [];
$return_policies = [];
$breadcrumbs = [];
$item_lists = [];
$stores = [];
$services = [];

foreach ($decoded_blocks as $decoded) {
    $web_pages = array_merge($web_pages, findSchemaNodes($decoded, 'WebPage'));
    $contact_pages = array_merge($contact_pages, findSchemaNodes($decoded, 'ContactPage'));
    $faq_pages = array_merge($faq_pages, findSchemaNodes($decoded, 'FAQPage'));
    $organizations = array_merge($organizations, findSchemaNodes($decoded, 'Organization'));
    $organizations = array_merge($organizations, findSchemaNodes($decoded, 'Store'));
    $return_policies = array_merge($return_policies, findSchemaNodes($decoded, 'MerchantReturnPolicy'));
    $breadcrumbs = array_merge($breadcrumbs, findSchemaNodes($decoded, 'BreadcrumbList'));
    $item_lists = array_merge($item_lists, findSchemaNodes($decoded, 'ItemList'));
    $stores = array_merge($stores, findSchemaNodes($decoded, 'ElectronicsStore'));
    $services = array_merge($services, findSchemaNodes($decoded, 'Service'));
}

if ($mode === 'policy') {
    requireNodes($errors, $web_pages, 'WebPage');
    requireNodes($errors, $organizations, 'Organization/Store');
    requireNodes($errors, $breadcrumbs, 'BreadcrumbList');
} elseif ($mode === 'faq') {
    requireNodes($errors, $faq_pages, 'FAQPage');

    foreach ($faq_pages as $index => $faq_page) {
        if (empty($faq_page['mainEntity']) || !is_array($faq_page['mainEntity'])) {
            $errors[] = 'FAQPage #' . ($index + 1) . ': missing mainEntity';
            continue;
        }

        foreach ($faq_page['mainEntity'] as $question_index => $question) {
            $prefix = 'FAQPage #' . ($index + 1) . ' question #' . ($question_index + 1);

            if (empty($question['name'])) {
                $errors[] = "{$prefix}: missing question name";
            }

            if (empty($question['acceptedAnswer']['text'])) {
                $errors[] = "{$prefix}: missing acceptedAnswer text";
            }
        }
    }
} elseif ($mode === 'locations') {
    requireNodes($errors, $contact_pages, 'ContactPage');
    requireNodes($errors, $item_lists, 'ItemList');
    requireNodes($errors, $stores, 'ElectronicsStore');
} elseif ($mode === 'service') {
    requireNodes($errors, $contact_pages, 'ContactPage');
    requireNodes($errors, $services, 'Service');
    requireNodes($errors, $faq_pages, 'FAQPage');
}

if ($return_policies) {
    foreach ($return_policies as $index => $policy) {
        if (empty($policy['merchantReturnDays'])) {
            $errors[] = 'MerchantReturnPolicy #' . ($index + 1) . ': missing merchantReturnDays';
        }
    }
}

if ($errors) {
    echo "FAIL\n";
    foreach (array_unique($errors) as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "OK: {$mode} schema JSON-LD basic fields are valid.\n";
exit(0);

function requireNodes(array &$errors, array $nodes, string $label): void
{
    if (!$nodes) {
        $errors[] = "No {$label} schema found.";
    }
}

function readSource(string $source)
{
    if (is_readable($source) && !preg_match('#^https?://#i', $source)) {
        return file_get_contents($source);
    }

    $ch = curl_init($source);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-PolicySchemaAudit/1.0',
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
