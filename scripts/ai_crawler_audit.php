<?php

/**
 * Checks whether important search and AI discovery crawlers can reach key URLs.
 *
 * Usage:
 *   php scripts/ai_crawler_audit.php https://www.chipoteka.hr
 *   php scripts/ai_crawler_audit.php https://www.chipoteka.hr / /robots.txt /sitemap-index.xml
 */

$base_url = isset($argv[1]) ? rtrim($argv[1], '/') : 'https://www.chipoteka.hr';
$paths = array_slice($argv, 2);

if (!$paths) {
    $paths = [
        '/',
        '/robots.txt',
        '/sitemap-index.xml',
        '/sitemap.xml',
    ];
}

$user_agents = [
    'Browser' => 'Mozilla/5.0 (compatible; ChipotekaAIAudit/1.0; +https://www.chipoteka.hr/)',
    'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Bingbot' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'OAI-SearchBot' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot',
    'ChatGPT-User' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot',
    'GPTBot' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.1; +https://openai.com/gptbot',
    'Claude-SearchBot' => 'Claude-SearchBot/1.0',
    'Claude-User' => 'Claude-User/1.0',
    'ClaudeBot' => 'ClaudeBot/1.0',
    'PerplexityBot' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot',
    'Perplexity-User' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user',
];

$expected_allowed = [
    'Browser',
    'Googlebot',
    'Bingbot',
    'OAI-SearchBot',
    'ChatGPT-User',
    'Claude-SearchBot',
    'Claude-User',
    'PerplexityBot',
    'Perplexity-User',
];

$rows = [];
$has_failure = false;

foreach ($paths as $path) {
    $url = absoluteUrl($base_url, $path);

    foreach ($user_agents as $label => $user_agent) {
        $result = fetchStatus($url, $user_agent);
        $expected = in_array($label, $expected_allowed, true) ? 'allow' : 'policy';
        $ok = $expected === 'policy' || ($result['status'] >= 200 && $result['status'] < 400);

        if (!$ok) {
            $has_failure = true;
        }

        $rows[] = [
            'url' => $url,
            'agent' => $label,
            'expected' => $expected,
            'status' => $result['status'] ?: 'ERR',
            'time_ms' => $result['time_ms'],
            'final_url' => $result['final_url'],
            'error' => $result['error'],
            'ok' => $ok ? 'yes' : 'no',
        ];
    }
}

printTable($rows);

if ($has_failure) {
    fwrite(STDERR, PHP_EOL . 'One or more expected discovery crawlers were blocked or failed.' . PHP_EOL);
    exit(1);
}

exit(0);

function absoluteUrl($base_url, $path) {
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return $base_url . '/' . ltrim($path, '/');
}

function fetchStatus($url, $user_agent) {
    if (!function_exists('curl_init')) {
        return [
            'status' => 0,
            'time_ms' => 0,
            'final_url' => $url,
            'error' => 'PHP cURL extension is not available',
        ];
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => $user_agent,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: hr-HR,hr;q=0.9,en;q=0.8',
        ],
    ]);

    curl_exec($ch);

    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $time_ms = (int)round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
    $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $error = curl_error($ch);

    curl_close($ch);

    return [
        'status' => $status,
        'time_ms' => $time_ms,
        'final_url' => $final_url ?: $url,
        'error' => $error,
    ];
}

function printTable(array $rows) {
    $columns = ['ok', 'expected', 'status', 'time_ms', 'agent', 'url'];
    $widths = [];

    foreach ($columns as $column) {
        $widths[$column] = strlen($column);
    }

    foreach ($rows as $row) {
        foreach ($columns as $column) {
            $widths[$column] = max($widths[$column], strlen((string)$row[$column]));
        }
    }

    echo implode('  ', array_map(function ($column) use ($widths) {
        return str_pad($column, $widths[$column]);
    }, $columns)) . PHP_EOL;

    echo implode('  ', array_map(function ($column) use ($widths) {
        return str_repeat('-', $widths[$column]);
    }, $columns)) . PHP_EOL;

    foreach ($rows as $row) {
        echo implode('  ', array_map(function ($column) use ($widths, $row) {
            return str_pad((string)$row[$column], $widths[$column]);
        }, $columns)) . PHP_EOL;
    }

    $errors = array_filter($rows, function ($row) {
        return $row['error'];
    });

    if ($errors) {
        echo PHP_EOL . 'Errors:' . PHP_EOL;

        foreach ($errors as $row) {
            echo '- ' . $row['agent'] . ' ' . $row['url'] . ': ' . $row['error'] . PHP_EOL;
        }
    }
}
