<?php
// update_eprel_model.php

require_once __DIR__ . '/config.php';

if (is_file(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, defined('DB_PORT') ? DB_PORT : 3306);
if ($mysqli->connect_errno) {
    die("DB connect error: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'oc_';

function fetchJson(
    string $url,
    ?string $username = null,
    ?string $password = null,
    int $connect_timeout = 15,
    int $timeout = 300
): string
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_ENCODING, '');

    if ($username !== null && $password !== null) {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
    }

    $json = curl_exec($ch);

    if ($json === false) {
        $error = curl_error($ch);
        curl_close($ch);

        throw new RuntimeException("Ne mogu dohvatiti JSON s {$url}: {$error}");
    }

    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        throw new RuntimeException("Dohvat JSON-a nije uspio. HTTP status: {$http_code} ({$url})");
    }

    return $json;
}

function getSources(): array
{
    $sources = [];

    $sources[] = [
        'label' => 'Pluschip EPREL proxy',
        'url' => 'https://chipoteka.pluschip.lin29.host25.com/eprel-test.php',
        'username' => null,
        'password' => null,
        'connect_timeout' => 15,
        'timeout' => 300,
    ];

    if (
        defined('OC_ENV')
        && isset(OC_ENV['service']['base_url'])
        && OC_ENV['service']['base_url']
    ) {
        $sources[] = [
            'label' => 'Luceed',
            'url' => rtrim((string) OC_ENV['service']['base_url'], '/') . '/artikli/naziv//',
            'username' => isset(OC_ENV['service']['username']) ? (string) OC_ENV['service']['username'] : null,
            'password' => isset(OC_ENV['service']['password']) ? (string) OC_ENV['service']['password'] : null,
            'connect_timeout' => 10,
            'timeout' => 120,
        ];
    }

    return $sources;
}

function fetchFirstAvailableJson(array $sources): array
{
    $errors = [];

    foreach ($sources as $source) {
        try {
            $json = fetchJson(
                $source['url'],
                $source['username'],
                $source['password'],
                isset($source['connect_timeout']) ? (int) $source['connect_timeout'] : 15,
                isset($source['timeout']) ? (int) $source['timeout'] : 300
            );

            return [$json, $source];
        } catch (RuntimeException $exception) {
            $errors[] = $source['label'] . ': ' . $exception->getMessage();
        }
    }

    die("Ne mogu dohvatiti JSON ni iz jednog izvora:\n- " . implode("\n- ", $errors) . "\n");
}

function normalizeItems(array $data): array
{
    if (isset($data[0]) && is_array($data[0])) {
        return $data;
    }

    if (isset($data['result'][0]['artikli']) && is_array($data['result'][0]['artikli'])) {
        return $data['result'][0]['artikli'];
    }

    if (isset($data['result']['artikli']) && is_array($data['result']['artikli'])) {
        return $data['result']['artikli'];
    }

    if (isset($data['artikli']) && is_array($data['artikli'])) {
        return $data['artikli'];
    }

    if (isset($data['artikl'])) {
        return [$data];
    }

    return [];
}

// 1) Dohvati JSON, prioritetno direktno iz Luceeda
list($json, $source) = fetchFirstAvailableJson(getSources());
echo "Izvor podataka: {$source['label']} ({$source['url']})\n";

$data = json_decode($json, true);
if ($data === null) {
    die("Ne mogu parsirati JSON\n");
}

$items = normalizeItems($data);
if (!$items) {
    die("JSON je dohvaćen, ali nije pronađena lista artikala za obradu.\n");
}

// 2) Pripremi SQL upit
$select_sql = "SELECT `product_id`, `model`, `sku`, `eprel_model` FROM `{$prefix}product` WHERE `model` = ? OR `sku` = ? LIMIT 1";
$select_stmt = $mysqli->prepare($select_sql);
if (!$select_stmt) {
    die("Prepare SELECT failed: " . $mysqli->error);
}

$sql = "UPDATE `{$prefix}product` SET `eprel_model` = ? WHERE `product_id` = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

$broj_uspjesnih = 0;
$broj_preskocenih = 0;
$broj_nepronadenih = 0;
$broj_istih = 0;

foreach ($items as $item) {
    // Preskoči ako nema artikla (ključ za spajanje)
    $artikl = isset($item['artikl']) ? trim($item['artikl']) : '';
    if ($artikl === '') {
        $broj_preskocenih++;
        continue;
    }

    // Izvuci bilo koji broj iz 'redoslijed'
    $eprel_model = null;
    if (isset($item['redoslijed']) && $item['redoslijed'] !== null) {
        if (preg_match('/\d+/', (string)$item['redoslijed'], $matches)) {
            $eprel_model = $matches[0]; // prvi broj
        }
    }

    // Ako nema pronađenog broja, preskoči
    if ($eprel_model === null) {
        $broj_preskocenih++;
        echo "Preskočeno: artikl={$artikl} nema valjan `redoslijed`.\n";
        continue;
    }

    $select_stmt->bind_param('ss', $artikl, $artikl);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    $product = $result ? $result->fetch_assoc() : null;

    if (!$product) {
        $broj_nepronadenih++;
        echo "Nije pronađen artikl u bazi: {$artikl}\n";
        continue;
    }

    if ((string) $product['eprel_model'] === (string) $eprel_model) {
        $broj_istih++;
        echo "Bez promjene: artikl={$artikl} već ima eprel_model={$eprel_model}\n";
        continue;
    }

    // 3) Upis u bazu
    $product_id = (int) $product['product_id'];
    $stmt->bind_param('si', $eprel_model, $product_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $broj_uspjesnih++;
        echo "Ažurirano: model={$artikl} -> eprel_model={$eprel_model}\n";
    } else {
        echo "Nije ažurirano: artikl={$artikl}, pronađen product_id={$product_id}, ali MySQL nije prijavio promjenu.\n";
    }
}

$select_stmt->close();
$stmt->close();
$mysqli->close();

echo "Gotovo. Uspješno ažurirano: {$broj_uspjesnih} proizvoda. ";
echo "Preskočeno bez valjanog redoslijeda: {$broj_preskocenih}. ";
echo "Nije pronađeno u bazi: {$broj_nepronadenih}. ";
echo "Već isto u bazi: {$broj_istih}.\n";
