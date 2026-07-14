<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/collection_jsonld_audit.php <url> [category|brand]\n");
    exit(2);
}

$url = $argv[1];
$mode = $argv[2] ?? 'category';

if (!in_array($mode, ['category', 'brand'], true)) {
    fwrite(STDERR, "Mode must be category or brand.\n");
    exit(2);
}

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

$collection_pages = [];
$item_lists = [];
$breadcrumb_lists = [];
$brands = [];
$category_nodes = [];

foreach ($decoded_blocks as $decoded) {
    $collection_pages = array_merge($collection_pages, findSchemaNodes($decoded, 'CollectionPage'));
    $item_lists = array_merge($item_lists, findSchemaNodes($decoded, 'ItemList'));
    $breadcrumb_lists = array_merge($breadcrumb_lists, findSchemaNodes($decoded, 'BreadcrumbList'));
    $brands = array_merge($brands, findSchemaNodes($decoded, 'Brand'));
    $category_nodes = array_merge($category_nodes, findSchemaNodes($decoded, 'Thing'));
}

if (!$collection_pages) {
    $errors[] = 'No CollectionPage schema found.';
} else {
    foreach ($collection_pages as $index => $page) {
        $prefix = 'CollectionPage #' . ($index + 1);
        foreach (['name', 'url', 'mainEntity'] as $field) {
            if (empty($page[$field])) {
                $errors[] = "{$prefix}: missing {$field}";
            }
        }
    }
}

if (!$item_lists) {
    $errors[] = 'No ItemList schema found.';
} else {
    $valid_item_list = false;

    foreach ($item_lists as $index => $list) {
        $prefix = 'ItemList #' . ($index + 1);

        if (empty($list['itemListElement']) || !is_array($list['itemListElement'])) {
            $errors[] = "{$prefix}: missing itemListElement";
            continue;
        }

        $valid_item_list = true;
        foreach (array_slice($list['itemListElement'], 0, 5) as $item_index => $list_item) {
            $item_prefix = "{$prefix} item #" . ($item_index + 1);

            if (empty($list_item['position'])) {
                $errors[] = "{$item_prefix}: missing position";
            }

            if (empty($list_item['item']) || !is_array($list_item['item'])) {
                $errors[] = "{$item_prefix}: missing item";
                continue;
            }

            foreach (['name', 'url', 'offers'] as $field) {
                if (empty($list_item['item'][$field])) {
                    $errors[] = "{$item_prefix}: Product missing {$field}";
                }
            }

            if (empty($list_item['item']['offers']['price'])) {
                $errors[] = "{$item_prefix}: Offer missing price";
            }

            if (empty($list_item['item']['offers']['availability'])) {
                $errors[] = "{$item_prefix}: Offer missing availability";
            }
        }
    }

    if (!$valid_item_list) {
        $errors[] = 'No populated ItemList found.';
    }
}

if (!$breadcrumb_lists) {
    $errors[] = 'No BreadcrumbList schema found.';
} else {
    foreach ($breadcrumb_lists as $index => $breadcrumbs) {
        if (empty($breadcrumbs['itemListElement']) || !is_array($breadcrumbs['itemListElement'])) {
            $errors[] = 'BreadcrumbList #' . ($index + 1) . ': missing itemListElement';
        }
    }
}

if ($mode === 'brand') {
    $page_brands = array_values(array_filter($brands, function (array $brand): bool {
        return !empty($brand['@id']) || !empty($brand['url']) || !empty($brand['logo']) || !empty($brand['image']);
    }));

    if (!$page_brands) {
        $errors[] = 'No Brand schema found.';
    } else {
        foreach ($page_brands as $index => $brand) {
            if (empty($brand['name'])) {
                $errors[] = 'Brand #' . ($index + 1) . ': missing name';
            }
        }
    }
} elseif (!$category_nodes) {
    $errors[] = 'No category Thing schema found.';
}

if ($errors) {
    echo "FAIL\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

$type = $mode === 'brand' ? 'Brand' : 'Category';
echo "OK: {$type} collection JSON-LD found and basic fields are valid.\n";
exit(0);

function fetchUrl(string $url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Chipoteka-CollectionJsonLdAudit/1.0',
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
