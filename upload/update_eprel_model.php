<?php
// update_eprel_model.php

require_once __DIR__ . '/config.php';

$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, defined('DB_PORT') ? DB_PORT : 3306);
if ($mysqli->connect_errno) {
    die("DB connect error: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'oc_';

// 1) Dohvati JSON s Chipoteka API-ja
$url = 'https://www.chipoteka.hr/api-test.php';
$json = @file_get_contents($url);
if ($json === false) {
    die("Ne mogu dohvatiti JSON s $url\n");
}

$data = json_decode($json, true);
if ($data === null) {
    die("Ne mogu parsirati JSON\n");
}

// Ako API vraća jedan objekt umjesto liste, normaliziramo ga
$items = isset($data[0]) ? $data : [$data];

// 2) Pripremi SQL upit
$sql = "UPDATE `{$prefix}product` SET `eprel_model` = ? WHERE `model` = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

$broj_uspjesnih = 0;

foreach ($items as $item) {
    // Preskoči ako nema artikla (ključ za spajanje)
    $artikl = isset($item['artikl']) ? trim($item['artikl']) : '';
    if ($artikl === '') {
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
        continue;
    }

    // 3) Upis u bazu
    $stmt->bind_param('ss', $eprel_model, $artikl);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $broj_uspjesnih++;
        echo "Ažurirano: model={$artikl} -> eprel_model={$eprel_model}\n";
    } else {
        // Ako želiš log i za one koje nije našao, otkomentiraj:
        // echo "Nije pronađen artikl u bazi: {$artikl}\n";
    }
}

$stmt->close();
$mysqli->close();

echo "Gotovo. Uspješno ažurirano: {$broj_uspjesnih} proizvoda.\n";
