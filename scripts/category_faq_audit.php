<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/category_faq_audit.php <category-url>\n");
    exit(2);
}

$url = $argv[1];
$html = fetchUrl($url);

if ($html === false || $html === '') {
    fwrite(STDERR, "Unable to fetch category URL: {$url}\n");
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
$faq_pages = [];

foreach ($matches[1] as $index => $json) {
    $json = html_entity_decode(trim($json), ENT_QUOTES, 'UTF-8');
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        $errors[] = 'Block #' . ($index + 1) . ' is invalid JSON: ' . json_last_error_msg();
        continue;
    }

    $faq_pages = array_merge($faq_pages, findSchemaNodes($decoded, 'FAQPage'));
}

if (!$faq_pages) {
    $errors[] = 'No FAQPage schema found.';
}

$visible_text = normalizeText(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
$question_count = 0;

foreach ($faq_pages as $page_index => $faq_page) {
    if (empty($faq_page['mainEntity']) || !is_array($faq_page['mainEntity'])) {
        $errors[] = 'FAQPage #' . ($page_index + 1) . ': missing mainEntity';
        continue;
    }

    foreach ($faq_page['mainEntity'] as $question_index => $question) {
        $prefix = 'FAQPage #' . ($page_index + 1) . ' question #' . ($question_index + 1);
        $question_text = normalizeText((string)($question['name'] ?? ''));
        $answer_text = normalizeText((string)($question['acceptedAnswer']['text'] ?? ''));

        if ($question_text === '') {
            $errors[] = "{$prefix}: missing question name";
        }

        if ($answer_text === '') {
            $errors[] = "{$prefix}: missing acceptedAnswer text";
        }

        if ($question_text !== '' && strpos($visible_text, $question_text) === false) {
            $errors[] = "{$prefix}: question is not visible in page HTML";
        }

        if ($answer_text !== '' && strpos($visible_text, $answer_text) === false) {
            $errors[] = "{$prefix}: answer is not visible in page HTML";
        }

        $question_count++;
    }
}

if ($errors) {
    echo "FAIL\n";
    foreach (array_unique($errors) as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "OK: Category FAQ is visible and FAQPage JSON-LD is valid for {$question_count} questions.\n";
exit(0);

function fetchUrl(string $url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-CategoryFaqAudit/1.0',
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
