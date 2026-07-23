<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/policy_schema_audit.php <url-or-file> [policy|faq|locations|service|website]\n");
    exit(2);
}

$source = $argv[1];
$mode = $argv[2] ?? 'policy';

if (!in_array($mode, ['policy', 'faq', 'locations', 'service', 'website'], true)) {
    fwrite(STDERR, "Mode must be policy, faq, locations, service or website.\n");
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
$websites = [];
$search_actions = [];
$visible_text = normalizeText(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));

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
    $websites = array_merge($websites, findSchemaNodes($decoded, 'WebSite'));
    $search_actions = array_merge($search_actions, findSchemaNodes($decoded, 'SearchAction'));
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

            $question_text = normalizeText((string)($question['name'] ?? ''));
            $answer_text = normalizeText((string)($question['acceptedAnswer']['text'] ?? ''));

            if ($question_text !== '' && strpos($visible_text, $question_text) === false) {
                $errors[] = "{$prefix}: question is not visible in page HTML";
            }

            if ($answer_text !== '' && strpos($visible_text, $answer_text) === false) {
                $errors[] = "{$prefix}: answer is not visible in page HTML";
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
} elseif ($mode === 'website') {
    requireNodes($errors, $websites, 'WebSite');
    requireNodes($errors, $search_actions, 'SearchAction');
}

foreach ($search_actions as $index => $search_action) {
    validateSearchAction($errors, $search_action, $index + 1);
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

function validateSearchAction(array &$errors, array $search_action, int $index): void
{
    $prefix = 'SearchAction #' . $index;

    if (empty($search_action['query-input'])) {
        $errors[] = "{$prefix}: missing query-input";
    }

    if (empty($search_action['target'])) {
        $errors[] = "{$prefix}: missing target";
        return;
    }

    $target = $search_action['target'];
    $url_template = '';

    if (is_string($target)) {
        $url_template = $target;
    } elseif (is_array($target)) {
        $url_template = (string)($target['urlTemplate'] ?? '');
    }

    if ($url_template === '') {
        $errors[] = "{$prefix}: missing target urlTemplate";
    } elseif (strpos($url_template, '{search_term_string}') === false) {
        $errors[] = "{$prefix}: urlTemplate must include {search_term_string}";
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

function normalizeText(string $text): string
{
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text ?? '');
}
