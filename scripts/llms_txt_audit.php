<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/llms_txt_audit.php <llms-url-or-file>\n");
    exit(2);
}

$source = $argv[1];
$document = readDocument($source);

if ($document === false || trim($document['body']) === '') {
    fwrite(STDERR, "Unable to read llms.txt: {$source}\n");
    exit(1);
}

$content = normalizeLineEndings($document['body']);
$errors = array();

if (!empty($document['status']) && (int)$document['status'] >= 400) {
    $errors[] = 'HTTP status is ' . (int)$document['status'];
}

if (!empty($document['content_type']) && !preg_match('#^(text/plain|text/markdown)\b#i', $document['content_type'])) {
    $errors[] = 'Content-Type should be text/plain or text/markdown, got ' . $document['content_type'];
}

if (!preg_match('/\A#\s+\S/u', $content)) {
    $errors[] = 'Document must start with a Markdown H1 title.';
}

if (!preg_match('/^>\s+\S/um', $content)) {
    $errors[] = 'Document should include a blockquote summary below the title.';
}

if (preg_match('/<html|<body|<script/i', $content)) {
    $errors[] = 'Document looks like HTML, expected plain Markdown.';
}

if (preg_match_all('/^##\s+\S.+$/um', $content, $section_matches) < 2) {
    $errors[] = 'Document should include at least two H2 sections.';
}

preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/u', $content, $link_matches, PREG_SET_ORDER);

if (count($link_matches) < 5) {
    $errors[] = 'Document should include at least five Markdown links.';
}

foreach ($link_matches as $match) {
    if (!preg_match('#^https://#i', $match[2])) {
        $errors[] = 'Link should be absolute HTTPS URL: ' . $match[2];
    }
}

foreach (array('/robots.txt', '/sitemap-index.xml', '/openai-products.jsonl', '/ai-context') as $required_path) {
    if (strpos($content, $required_path) === false) {
        $errors[] = 'Missing required resource reference: ' . $required_path;
    }
}

if ($errors) {
    echo "FAIL\n";
    foreach (array_unique($errors) as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo 'OK: llms.txt basic Markdown structure is valid with ' . count($link_matches) . " links.\n";
exit(0);

function readDocument(string $source) {
    if (is_readable($source) && !preg_match('#^https?://#i', $source)) {
        return array(
            'body' => file_get_contents($source),
            'status' => 0,
            'content_type' => ''
        );
    }

    $ch = curl_init($source);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-LlmsTxtAudit/1.0',
        CURLOPT_HEADER => true
    ));

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return false;
    }

    $header_size = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $body = substr($response, $header_size);
    curl_close($ch);

    return array(
        'body' => $body,
        'status' => $status,
        'content_type' => $content_type
    );
}

function normalizeLineEndings(string $content): string {
    return str_replace(array("\r\n", "\r"), "\n", $content);
}
