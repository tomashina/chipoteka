<?php

/**
 * Runs the AI/search regression suite against a live Chipoteka site.
 *
 * Usage:
 *   php scripts/ai_regression_monitor.php https://www.chipoteka.hr
 *   php scripts/ai_regression_monitor.php https://www.chipoteka.hr --quick
 *   php scripts/ai_regression_monitor.php https://www.chipoteka.hr --include-crawler
 *   php scripts/ai_regression_monitor.php --dry-run
 */

$options = parseArguments($argv);

if ($options['help']) {
    printUsage();
    exit(0);
}

$base_url = rtrim($options['base_url'], '/');
$checks = buildChecks($base_url, $options);

if ($options['dry_run']) {
    printDryRun($checks, $base_url);
    exit(0);
}

$started_at = gmdate('c');
$results = [];

foreach ($checks as $check) {
    $results[] = runCheck($check, $options);
}

$summary = summarizeResults($results, $options);

if ($options['json']) {
    echo json_encode([
        'started_at' => $started_at,
        'base_url' => $base_url,
        'strict' => $options['strict'],
        'quick' => $options['quick'],
        'include_crawler' => $options['include_crawler'],
        'summary' => $summary,
        'results' => $results
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    printResults($results, $summary);
}

exit($summary['failed'] > 0 ? 1 : 0);

function parseArguments(array $argv): array
{
    $options = [
        'base_url' => 'https://www.chipoteka.hr',
        'quick' => false,
        'strict' => false,
        'include_crawler' => false,
        'json' => false,
        'dry_run' => false,
        'help' => false,
    ];

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--quick') {
            $options['quick'] = true;
        } elseif ($argument === '--strict') {
            $options['strict'] = true;
        } elseif ($argument === '--include-crawler') {
            $options['include_crawler'] = true;
        } elseif ($argument === '--json') {
            $options['json'] = true;
        } elseif ($argument === '--dry-run') {
            $options['dry_run'] = true;
        } elseif ($argument === '--help' || $argument === '-h') {
            $options['help'] = true;
        } elseif (strpos($argument, '--') === 0) {
            fwrite(STDERR, "Unknown option: {$argument}\n");
            $options['help'] = true;
        } else {
            $options['base_url'] = $argument;
        }
    }

    return $options;
}

function buildChecks(string $base_url, array $options): array
{
    $checks = [
        httpCheck('Homepage HTTP', $base_url . '/', ['content_type' => 'text/html']),
        httpCheck('robots.txt HTTP', $base_url . '/robots.txt', [
            'content_type' => 'text/plain',
            'contains' => ['Sitemap:', 'sitemap-index.xml']
        ]),
        httpCheck('Sitemap index HTTP', $base_url . '/sitemap-index.xml', [
            'content_type' => 'xml',
            'contains' => ['<sitemapindex']
        ]),
        httpCheck('OpenAI feed HTTP', $base_url . '/openai-products.jsonl?limit=1', [
            'content_type' => 'ndjson',
            'contains' => ['"item_id"', '"url"']
        ]),
        httpCheck('llms.txt HTTP', $base_url . '/llms.txt', [
            'content_type' => 'text/plain',
            'contains' => ['# Chipoteka', '/openai-products.jsonl']
        ]),
        httpCheck('AI context HTTP', $base_url . '/ai-context', ['content_type' => 'text/html']),
        httpCheck('Purchase terms HTTP', $base_url . '/uvjeti-kupovine', ['content_type' => 'text/html']),
        httpCheck('Stores HTTP', $base_url . '/poslovnice', ['content_type' => 'text/html']),
        httpCheck('Service HTTP', $base_url . '/servis', ['content_type' => 'text/html']),
    ];

    $checks[] = phpScriptCheck('OpenAI product feed schema', 'openai_product_feed_audit.php', [$base_url . '/openai-products.jsonl?limit=50', '50']);
    $checks[] = phpScriptCheck('Generic merchant feed spine', 'generic_merchant_feed.php', [$base_url . '/openai-products.jsonl?limit=50']);
    $checks[] = phpScriptCheck('llms.txt schema', 'llms_txt_audit.php', [$base_url . '/llms.txt']);

    $product_url = $options['dry_run'] ? '<first-product-url-from-feed>' : findFirstProductUrl($base_url);

    if ($product_url) {
        $checks[] = phpScriptCheck('Product JSON-LD sample', 'product_jsonld_audit.php', [$product_url]);
    } else {
        $checks[] = staticFailure('Product JSON-LD sample', 'Could not resolve a product URL from /openai-products.jsonl?limit=1');
    }

    $checks[] = phpScriptCheck('Category JSON-LD sample', 'collection_jsonld_audit.php', [$base_url . '/fotoaparati?page=2', 'category']);
    $checks[] = phpScriptCheck('Brand JSON-LD sample', 'collection_jsonld_audit.php', [$base_url . '/nikon', 'brand']);
    $checks[] = phpScriptCheck('Policy WebPage schema', 'policy_schema_audit.php', [$base_url . '/uvjeti-kupovine', 'policy']);
    $checks[] = phpScriptCheck('Policy FAQ schema', 'policy_schema_audit.php', [$base_url . '/uvjeti-kupovine', 'faq']);
    $checks[] = phpScriptCheck('Stores schema', 'policy_schema_audit.php', [$base_url . '/poslovnice', 'locations']);
    $checks[] = phpScriptCheck('Service schema', 'policy_schema_audit.php', [$base_url . '/servis', 'service']);

    if (!$options['quick']) {
        $sitemap_url = $options['dry_run'] ? '<first-product-sitemap-url>' : findFirstProductSitemapUrl($base_url);

        if ($sitemap_url) {
            $checks[] = phpScriptCheck('Product sitemap URL sample', 'sitemap_url_audit.php', [$sitemap_url, '10']);
        } else {
            $checks[] = staticFailure('Product sitemap URL sample', 'Could not resolve a product sitemap URL from sitemap-index.xml');
        }
    }

    if ($options['include_crawler']) {
        $checks[] = phpScriptCheck(
            'AI crawler/WAF audit',
            'ai_crawler_audit.php',
            [$base_url, '/', '/robots.txt', '/sitemap-index.xml', '/openai-products.jsonl?limit=1', '/llms.txt'],
            'warning'
        );
    }

    return $checks;
}

function httpCheck(string $name, string $url, array $expectations = []): array
{
    return [
        'type' => 'http',
        'name' => $name,
        'severity' => 'critical',
        'url' => $url,
        'expectations' => $expectations,
    ];
}

function phpScriptCheck(string $name, string $script, array $args, string $severity = 'critical'): array
{
    return [
        'type' => 'php_script',
        'name' => $name,
        'severity' => $severity,
        'script' => $script,
        'args' => $args,
    ];
}

function staticFailure(string $name, string $message): array
{
    return [
        'type' => 'static',
        'name' => $name,
        'severity' => 'critical',
        'message' => $message,
    ];
}

function runCheck(array $check, array $options): array
{
    $start = microtime(true);

    if ($check['type'] === 'http') {
        $result = runHttpCheck($check);
    } elseif ($check['type'] === 'php_script') {
        $result = runPhpScriptCheck($check);
    } else {
        $result = [
            'ok' => false,
            'output' => $check['message'] ?? 'Static failure',
        ];
    }

    $duration_ms = (int)round((microtime(true) - $start) * 1000);
    $severity = $check['severity'] ?? 'critical';
    $ok = (bool)($result['ok'] ?? false);
    $status = $ok ? 'PASS' : (($severity === 'warning' && !$options['strict']) ? 'WARN' : 'FAIL');

    return [
        'name' => $check['name'],
        'type' => $check['type'],
        'severity' => $severity,
        'status' => $status,
        'ok' => $ok,
        'duration_ms' => $duration_ms,
        'target' => $check['url'] ?? ($check['script'] ?? ''),
        'output' => trim((string)($result['output'] ?? '')),
    ];
}

function runHttpCheck(array $check): array
{
    $body_required = !empty($check['expectations']['contains']);
    $response = fetchUrl($check['url'], $body_required);
    $errors = [];

    if ($response['error'] !== '') {
        $errors[] = $response['error'];
    }

    if ($response['status'] < 200 || $response['status'] >= 400) {
        $errors[] = 'HTTP status ' . $response['status'];
    }

    if (!empty($check['expectations']['content_type'])) {
        $expected = strtolower($check['expectations']['content_type']);
        $content_type = strtolower($response['content_type']);

        if ($expected === 'xml') {
            $type_ok = strpos($content_type, 'xml') !== false;
        } elseif ($expected === 'ndjson') {
            $type_ok = strpos($content_type, 'ndjson') !== false || strpos($content_type, 'json') !== false;
        } else {
            $type_ok = strpos($content_type, $expected) !== false;
        }

        if (!$type_ok) {
            $errors[] = 'Unexpected Content-Type: ' . ($response['content_type'] ?: 'missing');
        }
    }

    foreach (($check['expectations']['contains'] ?? []) as $needle) {
        if (strpos($response['body'], $needle) === false) {
            $errors[] = 'Missing expected text: ' . $needle;
        }
    }

    return [
        'ok' => !$errors,
        'output' => $errors ? implode("\n", $errors) : 'HTTP ' . $response['status'] . ' ' . $response['content_type'],
    ];
}

function runPhpScriptCheck(array $check): array
{
    $script_path = __DIR__ . '/' . $check['script'];

    if (!is_file($script_path)) {
        return [
            'ok' => false,
            'output' => 'Missing script: ' . $script_path,
        ];
    }

    $command = array_merge([PHP_BINARY, $script_path], $check['args']);
    $command_line = implode(' ', array_map('escapeshellarg', $command));
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command_line, $descriptors, $pipes, dirname(__DIR__));

    if (!is_resource($process)) {
        return [
            'ok' => false,
            'output' => 'Could not start command: ' . $command_line,
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exit_code = proc_close($process);
    $output = trim($stdout . ($stderr !== '' ? "\n" . $stderr : ''));

    return [
        'ok' => $exit_code === 0,
        'output' => truncateText($output, 2500),
    ];
}

function fetchUrl(string $url, bool $with_body = true): array
{
    if (!function_exists('curl_init')) {
        return [
            'status' => 0,
            'content_type' => '',
            'body' => '',
            'error' => 'PHP cURL extension is not available',
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-AIRegressionMonitor/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml,text/plain,application/json,*/*;q=0.8',
            'Accept-Language: hr-HR,hr;q=0.9,en;q=0.8',
        ],
    ]);

    if (!$with_body) {
        curl_setopt($ch, CURLOPT_NOBODY, true);
    }

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $content_type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'content_type' => $content_type,
        'body' => is_string($body) ? $body : '',
        'error' => $error,
    ];
}

function findFirstProductUrl(string $base_url): string
{
    $response = fetchUrl($base_url . '/openai-products.jsonl?limit=1', true);

    if ($response['status'] >= 400 || trim($response['body']) === '') {
        return '';
    }

    foreach (preg_split('/\R/', $response['body']) as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $record = json_decode($line, true);

        if (is_array($record) && !empty($record['url']) && preg_match('#^https?://#i', $record['url'])) {
            return $record['url'];
        }
    }

    return '';
}

function findFirstProductSitemapUrl(string $base_url): string
{
    $response = fetchUrl($base_url . '/sitemap-index.xml', true);

    if ($response['status'] >= 400 || trim($response['body']) === '') {
        return '';
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response['body']);

    if (!$xml) {
        return '';
    }

    $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $locations = $xml->xpath('//sm:sitemap/sm:loc') ?: [];
    $fallback = '';

    foreach ($locations as $location) {
        $url = trim((string)$location);

        if ($fallback === '') {
            $fallback = $url;
        }

        if (stripos($url, 'product') !== false) {
            return $url;
        }
    }

    return $fallback;
}

function summarizeResults(array $results, array $options): array
{
    $summary = [
        'passed' => 0,
        'warned' => 0,
        'failed' => 0,
        'total' => count($results),
    ];

    foreach ($results as $result) {
        if ($result['status'] === 'PASS') {
            $summary['passed']++;
        } elseif ($result['status'] === 'WARN') {
            $summary['warned']++;
        } else {
            $summary['failed']++;
        }
    }

    if ($options['strict'] && $summary['warned'] > 0) {
        $summary['failed'] += $summary['warned'];
        $summary['warned'] = 0;
    }

    return $summary;
}

function printResults(array $results, array $summary): void
{
    $columns = ['status', 'severity', 'time_ms', 'check'];
    $widths = [
        'status' => strlen('status'),
        'severity' => strlen('severity'),
        'time_ms' => strlen('time_ms'),
        'check' => strlen('check'),
    ];

    foreach ($results as $result) {
        $widths['status'] = max($widths['status'], strlen($result['status']));
        $widths['severity'] = max($widths['severity'], strlen($result['severity']));
        $widths['time_ms'] = max($widths['time_ms'], strlen((string)$result['duration_ms']));
        $widths['check'] = max($widths['check'], strlen($result['name']));
    }

    echo implode('  ', array_map(function ($column) use ($widths) {
        return str_pad($column, $widths[$column]);
    }, $columns)) . PHP_EOL;

    echo implode('  ', array_map(function ($column) use ($widths) {
        return str_repeat('-', $widths[$column]);
    }, $columns)) . PHP_EOL;

    foreach ($results as $result) {
        echo str_pad($result['status'], $widths['status']) . '  ';
        echo str_pad($result['severity'], $widths['severity']) . '  ';
        echo str_pad((string)$result['duration_ms'], $widths['time_ms']) . '  ';
        echo $result['name'] . PHP_EOL;
    }

    $problem_results = array_filter($results, function (array $result): bool {
        return $result['status'] !== 'PASS';
    });

    if ($problem_results) {
        echo PHP_EOL . 'Details:' . PHP_EOL;

        foreach ($problem_results as $result) {
            echo '- ' . $result['status'] . ' ' . $result['name'] . ': ' . ($result['output'] ?: 'No output') . PHP_EOL;
        }
    }

    echo PHP_EOL;
    echo 'Summary: ' . $summary['passed'] . ' passed, ' . $summary['warned'] . ' warnings, ' . $summary['failed'] . ' failed, ' . $summary['total'] . ' total.' . PHP_EOL;
}

function printDryRun(array $checks, string $base_url): void
{
    echo "AI regression monitor dry run for {$base_url}" . PHP_EOL;

    foreach ($checks as $index => $check) {
        $target = $check['url'] ?? ($check['script'] ?? '');
        echo str_pad((string)($index + 1), 2, ' ', STR_PAD_LEFT) . '. ';
        echo '[' . $check['severity'] . '] ' . $check['name'] . ' -> ' . $target . PHP_EOL;
    }
}

function printUsage(): void
{
    echo "Usage: php scripts/ai_regression_monitor.php [base-url] [--quick] [--include-crawler] [--strict] [--json] [--dry-run]\n";
    echo "\n";
    echo "Options:\n";
    echo "  --quick            Skip slower sitemap URL sampling.\n";
    echo "  --include-crawler  Include AI crawler/WAF audit as warning unless --strict is set.\n";
    echo "  --strict           Treat warning checks as failures.\n";
    echo "  --json             Print machine-readable JSON result.\n";
    echo "  --dry-run          Print planned checks without network calls.\n";
}

function truncateText(string $text, int $limit): string
{
    if (strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, $limit - 3)) . '...';
}
