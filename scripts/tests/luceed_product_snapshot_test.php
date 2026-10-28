<?php

/**
 * Database-free regression tests for accepted Luceed product snapshots.
 * Run: php scripts/tests/luceed_product_snapshot_test.php
 * The Database double below prevents all real database and network access.
 */

namespace Agmedia\Helpers {
    class Log
    {
        public static $entries = [];

        public static function store($value, $name = null)
        {
            self::$entries[] = [$name, $value];
        }
    }

    class Database
    {
        public static $tables = [];
        public static $engines = [];
        public static $columns = [];
        public static $indexes = [];
        public static $queries = [];
        public static $fail_on;
        private $temporary = [];
        private $backup;

        public function __construct($name) {}

        public function escape($value)
        {
            return addslashes((string)$value);
        }

        private function result(array $rows)
        {
            return (object)['rows' => $rows, 'row' => $rows[0] ?? [], 'num_rows' => count($rows)];
        }

        public function query($sql)
        {
            $sql = preg_replace('/\s+/', ' ', trim($sql));
            self::$queries[] = $sql;

            if (self::$fail_on !== null && strpos($sql, self::$fail_on) !== false) {
                throw new \RuntimeException('Injected query failure: ' . self::$fail_on);
            }

            if ($sql === 'START TRANSACTION') {
                $this->backup = self::$tables;
                return true;
            }
            if ($sql === 'ROLLBACK') {
                self::$tables = $this->backup;
                $this->backup = null;
                return true;
            }
            if ($sql === 'COMMIT') {
                $this->backup = null;
                return true;
            }
            if ($sql === 'SELECT NOW() AS synced_at') {
                return $this->result([['synced_at' => '2026-10-28 11:00:00']]);
            }
            if (strpos($sql, 'SELECT DISTINCT luceed_uid FROM') === 0) {
                $uids = [];
                foreach (self::$tables['oc_product'] as $product) {
                    if (!empty($product['luceed_uid'])) {
                        $uids[$product['luceed_uid']] = ['luceed_uid' => $product['luceed_uid']];
                    }
                }
                return $this->result(array_values($uids));
            }
            if (strpos($sql, 'SELECT COUNT(DISTINCT luceed_uid) AS total FROM') === 0) {
                $uids = [];
                foreach (self::$tables['oc_product'] as $product) {
                    if (!empty($product['luceed_uid']) && $product['status'] === 1) {
                        $uids[$product['luceed_uid']] = true;
                    }
                }
                return $this->result([['total' => count($uids)]]);
            }
            if (preg_match('/^SHOW COLUMNS FROM `([^`]+)`$/', $sql, $match)) {
                return $this->result(array_map(function ($field) { return ['Field' => $field]; }, self::$columns[$match[1]]));
            }
            if (preg_match('/^SHOW INDEX FROM `([^`]+)`$/', $sql, $match)) {
                return $this->result(self::$indexes[$match[1]] ?? []);
            }
            if (strpos($sql, 'SELECT ENGINE FROM information_schema.TABLES') === 0 &&
                preg_match("/TABLE_NAME = '([^']+)'/", $sql, $match)) {
                return $this->result([['ENGINE' => self::$engines[$match[1]] ?? '']]);
            }
            if (preg_match('/^ALTER TABLE `([^`]+)` (.+)$/', $sql, $match)) {
                if ($match[2] === 'ENGINE=InnoDB') {
                    self::$engines[$match[1]] = 'InnoDB';
                } elseif ($match[2] === 'ADD INDEX `idx_luceed_uid` (`uid`)') {
                    self::$indexes[$match[1]][] = ['Key_name' => 'idx_luceed_uid', 'Seq_in_index' => 1, 'Column_name' => 'uid'];
                } else {
                    preg_match_all('/ADD COLUMN `([^`]+)`/', $match[2], $columns);
                    self::$columns[$match[1]] = array_merge(self::$columns[$match[1]], $columns[1]);
                }
                return true;
            }
            if (preg_match('/^DROP TEMPORARY TABLE IF EXISTS `([^`]+)`$/', $sql, $match)) {
                unset($this->temporary[$match[1]]);
                return true;
            }
            if (preg_match('/^CREATE TEMPORARY TABLE `([^`]+)` LIKE `[^`]+`$/', $sql, $match)) {
                $this->temporary[$match[1]] = [];
                return true;
            }
            if (strpos($sql, 'INSERT INTO `oc_product_luceed_sync`') === 0) {
                preg_match_all("/\('((?:\\\\.|[^'])*)', '((?:\\\\.|[^'])*)', '([A-Za-z0-9+\/=]+)', '([a-f0-9]+)', ([01]), (\d+), '([^']+)'\)/", $sql, $matches, PREG_SET_ORDER);
                if (!$matches) {
                    throw new \RuntimeException('Cannot parse staged snapshot rows.');
                }
                foreach ($matches as $row) {
                    $this->temporary['oc_product_luceed_sync'][] = [
                        'uid' => stripslashes($row[1]), 'sifra' => stripslashes($row[2]),
                        'data' => $row[3], 'hash' => $row[4], 'eligible' => (int)$row[5],
                        'min_quantity' => (int)$row[6], 'synced_at' => $row[7],
                    ];
                }
                return true;
            }
            if (strpos($sql, 'INSERT INTO `oc_product_luceed_queue_sync`') === 0) {
                $queued = array_column(self::$tables['oc_product_luceed_for_update'], 'uid');
                if (preg_match('/WHERE q.uid IN \((.+)\)$/', $sql, $match)) {
                    preg_match_all("/'((?:\\\\.|[^'])*)'/", $match[1], $matches);
                    $skipped = array_map('stripslashes', $matches[1]);
                    $known = array_column(self::$tables['oc_product'], 'luceed_uid');
                    $pending = array_column($this->temporary['oc_product_luceed_queue_sync'], null, 'uid');
                    foreach ($queued as $uid) {
                        if (in_array($uid, $skipped, true) && in_array($uid, $known, true)) {
                            $pending[$uid] = ['uid' => $uid];
                        }
                    }
                    $this->temporary['oc_product_luceed_queue_sync'] = array_values($pending);
                    return true;
                }
                $pending = [];
                foreach ($this->temporary['oc_product_luceed_sync'] as $record) {
                    foreach (self::$tables['oc_product'] as $product) {
                        if ($record['uid'] === $product['luceed_uid'] &&
                            (in_array($record['uid'], $queued, true) || $product['hash'] !== $record['hash'])) {
                            $pending[$record['uid']] = ['uid' => $record['uid']];
                        }
                    }
                }
                $this->temporary['oc_product_luceed_queue_sync'] = array_values($pending);
                return true;
            }
            if (preg_match('/^SELECT COUNT\(\*\) AS total FROM `([^`]+)`(.*)$/', $sql, $match)) {
                $rows = $this->temporary[$match[1]] ?? self::$tables[$match[1]];
                if ($match[2] !== '') {
                    if (strpos($match[2], 'WHERE p.product_id IS NULL') === false) {
                        throw new \RuntimeException('Unexpected count query.');
                    }
                    $existing = array_column(self::$tables['oc_product'], 'luceed_uid');
                    $rows = array_filter($rows, function ($row) use ($existing) { return !in_array($row['uid'], $existing, true); });
                }
                return $this->result([['total' => count($rows)]]);
            }
            if (preg_match('/^DELETE FROM `(oc_product_luceed(?:_for_update)?)`$/', $sql, $match)) {
                self::$tables[$match[1]] = [];
                return true;
            }
            if (preg_match('/^INSERT INTO `(oc_product_luceed(?:_for_update)?)` .* FROM `([^`]+)`$/', $sql, $match)) {
                self::$tables[$match[1]] = $this->temporary[$match[2]];
                return true;
            }

            throw new \RuntimeException('Unexpected SQL in test: ' . $sql);
        }
    }
}

namespace {
    error_reporting(E_ALL & ~E_DEPRECATED);
    define('DB_DATABASE', 'forbidden_real_database');
    define('DB_PREFIX', 'oc_');
    define('OC_ENV', ['import' => ['default_category' => 1, 'default_language' => 2, 'image_path' => 'catalog/', 'image_placeholder' => 'catalog/products/no-image.jpg']]);
    require dirname(__DIR__, 2) . '/storage/vendor/autoload.php';

    use Agmedia\Helpers\Database;
    use Agmedia\Helpers\Log;
    use Agmedia\LuceedOpencartWrapper\Helpers\ProductHelper;
    use Agmedia\LuceedOpencartWrapper\Models\LOC_Product;

    function check($condition, $message)
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function rawProduct($number, $webshop = 'D')
    {
        return (object)[
            'artikl_uid' => 'u' . $number, 'artikl' => 'sku' . $number, 'naziv' => 'Product ' . $number,
            'barcode' => '', 'jm' => 'kom', 'pakiranje' => 1, 'licenca' => '', 'opis' => 'Description',
            'vpc' => 8, 'mpc' => 10, 'enabled' => 'D', 'webshop' => $webshop, 'specifikacija' => '',
            'kataloski_broj' => '', 'stopa_pdv' => 25, 'nadgrupa_artikla' => '1',
            'nadgrupa_artikla_naziv' => 'Group', 'grupa_artikla' => '1', 'grupa_artikla_naziv' => 'Group',
            'robna_marka' => '1', 'robna_marka_naziv' => 'Brand', 'jamstvo_naziv' => '', 'stanje_kol' => 10,
            'atributi' => [(object)['atribut_uid' => 'a1', 'naziv' => 'Min_kol_web', 'aktivan' => 'D', 'vidljiv' => 'D', 'vrijednost' => '2']],
            'dokumenti' => [(object)['file_uid' => 'image1', 'filename' => 'image.jpg', 'naziv' => 'Image', 'md5' => 'abc']],
        ];
    }

    function feed(array $products)
    {
        return json_encode(['result' => [['artikli' => $products]]]);
    }

    function productHash($product)
    {
        return ProductHelper::hashLuceedData(ProductHelper::collectLuceedData($product));
    }

    function seed()
    {
        $products = [];
        $cache = [];
        for ($number = 1; $number <= 5; $number++) {
            $product = rawProduct($number);
            $products[] = ['product_id' => $number, 'luceed_uid' => 'u' . $number,
                'hash' => $number === 2 ? null : productHash($product), 'status' => 0, 'quantity' => 10];
            $cache[] = ['uid' => 'u' . $number, 'old_snapshot' => true];
        }
        Database::$tables = ['oc_product' => $products, 'oc_product_luceed' => $cache,
            'oc_product_luceed_for_update' => [['uid' => 'u1'], ['uid' => 'u1'], ['uid' => 'missing']]];
        Database::$engines = ['oc_product_luceed' => 'MyISAM', 'oc_product_luceed_for_update' => 'MyISAM'];
        Database::$columns = ['oc_product_luceed' => ['uid', 'sifra', 'data', 'hash']];
        Database::$indexes = ['oc_product_luceed' => [['Key_name' => 'existing_uid', 'Seq_in_index' => 1, 'Column_name' => 'uid']],
            'oc_product_luceed_for_update' => []];
        Database::$queries = [];
        Database::$fail_on = null;
        Log::$entries = [];
    }

    function expectFailure(array $products, $reason)
    {
        $old = Database::$tables;
        $failed = false;
        try {
            (new LOC_Product(feed($products)))->populateLuceedData();
        } catch (\Throwable $exception) {
            $failed = true;
            check(strpos($exception->getMessage(), $reason) !== false,
                'Unexpected failure: ' . $exception->getMessage());
        }
        check($failed, 'Expected snapshot rejection.');
        check(Database::$tables === $old, 'Rejected/failed snapshot changed persistent data.');
    }

    seed();
    $accepted = [rawProduct(1), rawProduct(2), rawProduct(3, 'N'), rawProduct(4), rawProduct(6, 'N')];
    $old_products = Database::$tables['oc_product'];
    $result = (new LOC_Product(feed($accepted)))->populateLuceedData();
    check($result['total'] === 4 && $result['inserting'] === 0 && $result['updating'] === 2, 'Incorrect accepted snapshot counts: ' . json_encode($result));
    check(Database::$tables['oc_product'] === $old_products, 'Snapshot import changed product status or stock.');
    check(array_column(Database::$tables['oc_product_luceed'], 'uid') === ['u1', 'u2', 'u3', 'u4'], 'Incorrect handling of non-web and missing products.');
    check(array_column(Database::$tables['oc_product_luceed_for_update'], 'uid') === ['u1', 'u2'], 'Pending queue was lost, duplicated, or did not include a null hash.');
    check(Database::$tables['oc_product_luceed'][0]['min_quantity'] === 2, 'Min_kol_web was not retained.');
    check(Database::$tables['oc_product_luceed'][0]['eligible'] === 1 && Database::$tables['oc_product_luceed'][2]['eligible'] === 0, 'Explicit webshop flag was not reflected in eligibility.');
    check(Database::$tables['oc_product_luceed'][0]['synced_at'] === '2026-10-28 11:00:00', 'Accepted timestamp was not saved.');
    check(Database::$engines['oc_product_luceed'] === 'InnoDB' && Database::$engines['oc_product_luceed_for_update'] === 'InnoDB', 'Legacy cache engines were not converted.');
    check(count(Database::$indexes['oc_product_luceed']) === 1, 'An existing leading uid index was duplicated.');
    check(Database::$indexes['oc_product_luceed_for_update'][0]['Column_name'] === 'uid', 'Missing queue uid index was not created.');
    check((bool)preg_grep('/ADD COLUMN `min_quantity` INT UNSIGNED NOT NULL DEFAULT 0/', Database::$queries), 'Missing minimum must default to zero.');
    check(!preg_grep('/TRUNCATE|UPDATE `?oc_product\b/', Database::$queries), 'Snapshot changed product rows or used a nontransactional truncate.');
    $start = array_search('START TRANSACTION', Database::$queries, true);
    foreach (Database::$queries as $index => $query) {
        check(strpos($query, 'ALTER TABLE') !== 0 || $index < $start, 'DDL occurred during the transaction.');
    }
    check((new LOC_Product())->checkProductsToDeactivate() === 0, 'Legacy absence deactivation is still active.');

    foreach (['INSERT INTO `oc_product_luceed`', 'INSERT INTO `oc_product_luceed_for_update`'] as $failure) {
        seed();
        Database::$fail_on = $failure;
        expectFailure($accepted, 'Injected query failure');
        check(in_array('ROLLBACK', Database::$queries, true), 'Interrupted snapshot did not roll back.');
    }

    seed();
    expectFailure([rawProduct(1), rawProduct(2), rawProduct(3)], '20% smaller');
    check(!preg_grep('/^(ALTER|DELETE|INSERT|START)/', Database::$queries), 'Small feed performed a persistent write.');

    foreach (['duplicate_uid', 'duplicate_sku', 'missing_name', 'missing_flag'] as $case) {
        seed();
        $products = [rawProduct(1), rawProduct(2), rawProduct(3), rawProduct(4), rawProduct(5)];
        if ($case === 'duplicate_uid') { $products[1]->artikl_uid = $products[0]->artikl_uid; }
        if ($case === 'duplicate_sku') { $products[1]->artikl = $products[0]->artikl; }
        if ($case === 'missing_name') { $products[0]->naziv = ' '; }
        if ($case === 'missing_flag') { unset($products[0]->webshop); }
        expectFailure($products, 'Luceed product response');
        check(!preg_grep('/^(ALTER|DELETE|INSERT|START)/', Database::$queries), 'Invalid feed performed a persistent write: ' . $case);
    }

    foreach (['missing_opis', 'missing_mpc', 'missing_documents', 'missing_attributes', 'partial_document', 'partial_attribute', 'bad_price', 'negative_minimum', 'malformed_minimum'] as $case) {
        seed();
        $products = [rawProduct(1), rawProduct(2), rawProduct(3), rawProduct(4), rawProduct(5)];
        // Production-style SKU from the cron error must not block other rows.
        $products[0]->artikl = '0730222201';
        if ($case === 'missing_opis') { unset($products[0]->opis); }
        if ($case === 'missing_mpc') { unset($products[0]->mpc); }
        if ($case === 'missing_documents') { unset($products[0]->dokumenti); }
        if ($case === 'missing_attributes') { unset($products[0]->atributi); }
        if ($case === 'partial_document') { unset($products[0]->dokumenti[0]->filename); }
        if ($case === 'partial_attribute') { unset($products[0]->atributi[0]->vrijednost); }
        if ($case === 'bad_price') { $products[0]->mpc = 'not a price'; }
        if ($case === 'negative_minimum') { $products[0]->atributi[0]->vrijednost = '-1'; }
        if ($case === 'malformed_minimum') { $products[0]->atributi[0]->vrijednost = 'abc'; }
        $old_products = Database::$tables['oc_product'];
        $result = (new LOC_Product(feed($products)))->populateLuceedData();
        check($result['total'] === 4 && $result['skipped_products'] === 1, 'One invalid article blocked the snapshot: ' . $case);
        check(Database::$tables['oc_product'] === $old_products, 'Excluded article status/stock changed: ' . $case);
        check(!in_array('u1', array_column(Database::$tables['oc_product_luceed'], 'uid'), true), 'Invalid article was included in the new snapshot: ' . $case);
        check(in_array('u1', array_column(Database::$tables['oc_product_luceed_for_update'], 'uid'), true), 'Skipped article lost its explicit pending work: ' . $case);
        $warnings = array_values(array_filter(Log::$entries, function ($entry) { return $entry[0] === 'luceed-product-snapshot-skipped'; }));
        check(count($warnings) === 1 && $warnings[0][1]['products'][0]['sku'] === '0730222201' && $warnings[0][1]['products'][0]['reason'] !== '', 'Skipped article had no specific warning: ' . $case);
    }

    seed();
    $products = [rawProduct(1), rawProduct(2), rawProduct(3), rawProduct(4), rawProduct(5)];
    unset($products[0]->mpc, $products[1]->atributi);
    expectFailure($products, '20% smaller');
    check(!preg_grep('/^(ALTER|DELETE|INSERT|START)/', Database::$queries), 'Mass invalid article data replaced the accepted snapshot.');

    seed();
    $products = [rawProduct(1), rawProduct(2), rawProduct(3), rawProduct(4), rawProduct(5)];
    unset($products[0]->opis);
    (new LOC_Product(feed($products)))->populateLuceedData();
    check(!in_array('u1', array_column(Database::$tables['oc_product_luceed'], 'uid'), true), 'Skipped article retained trusted snapshot data.');
    check(in_array('u1', array_column(Database::$tables['oc_product_luceed_for_update'], 'uid'), true), 'Skipped same-hash pending article was lost.');
    check(!in_array('missing', array_column(Database::$tables['oc_product_luceed_for_update'], 'uid'), true), 'An unrelated orphan queue entry was preserved.');
    $restored = [rawProduct(1), rawProduct(2), rawProduct(3), rawProduct(4), rawProduct(5)];
    check(productHash($restored[0]) === Database::$tables['oc_product'][0]['hash'], 'Retry test requires an unchanged product hash.');
    (new LOC_Product(feed($restored)))->populateLuceedData();
    check(in_array('u1', array_column(Database::$tables['oc_product_luceed'], 'uid'), true) &&
        in_array('u1', array_column(Database::$tables['oc_product_luceed_for_update'], 'uid'), true), 'Complete later feed did not restore same-hash pending work.');

    seed();
    $products = [rawProduct(1), rawProduct(2), rawProduct(3), rawProduct(4), rawProduct(5), rawProduct(6, 'N')];
    $products[0]->artikl = '0730222201';
    $products[0]->mpc = null;
    $products[1]->webshop = 'N';
    $products[1]->mpc = null;
    $products[5]->mpc = null;
    unset($products[5]->opis, $products[5]->atributi, $products[5]->dokumenti);
    $result = (new LOC_Product(feed($products)))->populateLuceedData();
    check($result['total'] === 5 && $result['skipped_products'] === 0, 'Explicit nullable price was rejected or an irrelevant new non-web article was validated.');
    $cache = Database::$tables['oc_product_luceed'];
    check($cache[0]['eligible'] === 0 && $cache[1]['eligible'] === 0 && !in_array('u6', array_column($cache, 'uid'), true), 'Nullable price was eligible or a new non-web article was retained.');
    $serialized = unserialize(base64_decode($cache[0]['data']));
    check(array_key_exists('mpc', $serialized) && $serialized['mpc'] === null, 'Nullable price was replaced with an invented price.');

    // Real bundled Luceed rows include legitimate null-priced non-web articles.
    $fixture = json_decode(file_get_contents(dirname(__DIR__, 2) . '/storage/upload/luceed_json/artikli_atribut_uid.json'))->result[0]->artikli;
    $identity_method = new \ReflectionMethod(LOC_Product::class, 'validateSnapshotProducts');
    $identity_method->setAccessible(true);
    $activation_method = new \ReflectionMethod(LOC_Product::class, 'validateSnapshotActivationData');
    $activation_method->setAccessible(true);
    $fixture_loc = new LOC_Product();
    check(count($identity_method->invoke($fixture_loc, $fixture)) === 1933, 'Bundled Luceed fixture failed feed integrity validation.');
    foreach ($fixture as $record) {
        $activation_method->invoke($fixture_loc, $record);
    }

    seed();
    Database::$tables['oc_product_luceed'] = [];
    foreach (Database::$tables['oc_product'] as &$product) {
        $product['status'] = 1;
    }
    unset($product);
    expectFailure([rawProduct(1)], '20% smaller');

    // Retired mapped UIDs must not make an unchanged accepted feed look partial.
    // This mirrors 10,724 cached rows with 13,679 total mapped / 10,623 active.
    seed();
    Database::$tables['oc_product_luceed'] = array_slice(Database::$tables['oc_product_luceed'], 0, 3);
    Database::$tables['oc_product'][0]['status'] = 1;
    Database::$tables['oc_product'][1]['status'] = 1;
    $unchanged = (new LOC_Product(feed([rawProduct(1), rawProduct(2), rawProduct(3)])))->populateLuceedData();
    check($unchanged['total'] === 3, 'Retired managed UIDs caused an unchanged valid snapshot to be rejected.');

    seed();
    expectFailure([], 'does not contain any products');

    seed();
    Database::$engines['oc_product_luceed_for_update'] = 'MEMORY';
    expectFailure($accepted, 'requires an InnoDB table');
    check(!in_array('START TRANSACTION', Database::$queries, true), 'Snapshot started with a nontransactional queue.');

    seed();
    Database::$tables['oc_product'] = [];
    Database::$tables['oc_product_luceed'] = [];
    Database::$tables['oc_product_luceed_for_update'] = [];
    $without_minimum = rawProduct(1);
    $without_minimum->atributi = [];
    $first = (new LOC_Product(feed([$without_minimum, rawProduct(2, 'N')])))->populateLuceedData();
    check($first['total'] === 1 && $first['inserting'] === 1 && $first['updating'] === 0, 'Initial import queued or retained a new non-web product.');
    check(Database::$tables['oc_product_luceed'][0]['min_quantity'] === 0, 'Absent Min_kol_web introduced a minimum threshold.');

    seed();
    Database::$fail_on = 'INSERT INTO `oc_product_luceed_sync`';
    expectFailure($accepted, 'Injected query failure');
    check(!in_array('START TRANSACTION', Database::$queries, true), 'Persistent transaction started before staging succeeded.');

    // Exercise the pure status decision used by make(), without fetching images
    // or resolving manufacturer/category records from a real database.
    $status_method = new \ReflectionMethod(LOC_Product::class, 'resolveImportStatus');
    $status_method->setAccessible(true);
    $loc = new LOC_Product();
    $valid = ProductHelper::collectLuceedData(rawProduct(1));
    $legacy = $valid;
    unset($legacy['webshop']);
    check(ProductHelper::hashLuceedData($legacy) === ProductHelper::hashLuceedData($valid), 'Adding webshop to legacy snapshots queued an unnecessary full product reimport.');
    check($status_method->invoke($loc, $valid, 'catalog/real-image.jpg') === 1, 'Eligible import did not activate.');
    foreach (['', 'not_valid_image', 'image/placeholder.png', 'catalog/no_image.png', 'catalog/products/no-image.jpg'] as $image) {
        check($status_method->invoke($loc, $valid, $image) === 0, 'Import activated without a real image: ' . $image);
    }
    foreach (['webshop' => 'N', 'enabled' => 'N', 'mpc' => 0, 'opis' => '', 'stanje_kol' => 1] as $field => $value) {
        $invalid = $valid;
        $invalid[$field] = $value;
        check($status_method->invoke($loc, $invalid, 'catalog/real-image.jpg') === 0, 'Import bypassed activation rule: ' . $field);
    }
    $valid['stanje_kol'] = 2;
    check($status_method->invoke($loc, $valid, 'catalog/real-image.jpg') === 1, 'Import did not activate exactly at Min_kol_web.');
    $valid['atributi'] = [];
    $valid['stanje_kol'] = 1;
    check($status_method->invoke($loc, $valid, 'catalog/real-image.jpg') === 1, 'Missing Min_kol_web disabled an eligible quantity-one import.');
    $valid['stanje_kol'] = 0;
    check($status_method->invoke($loc, $valid, 'catalog/real-image.jpg') === 1, 'Missing Min_kol_web changed legacy activation of a new zero-stock import.');
    $valid['atributi'] = [['naziv' => 'Min_kol_web', 'aktivan' => 'D', 'vrijednost' => '0']];
    $valid['stanje_kol'] = 1;
    check($status_method->invoke($loc, $valid, 'catalog/real-image.jpg') === 1, 'Explicit Min_kol_web zero disabled an eligible import.');

    echo "PASS: Luceed snapshot validation, coverage, flags/minimum, pending queue preservation, rollback, and import activation.\n";
}
