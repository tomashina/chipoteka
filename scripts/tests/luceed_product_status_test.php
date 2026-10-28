<?php

// Runs against a disposable schema on the configured LOCAL MySQL server only.
// No application tables or Luceed services are touched.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/upload/config.php';
define('OC_ENV', ['import' => ['default_language' => 2, 'image_placeholder' => 'catalog/products/no-image.jpg']]);
require DIR_STORAGE . 'vendor/autoload.php';

use Agmedia\LuceedOpencartWrapper\Models\LOC_ProductStatus;

if (!in_array(DB_HOSTNAME, ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('This regression test only runs against a local database.');
}

class ProductStatusTestDatabase
{
    public $connection;
    public $afterCandidateRead;

    public function __construct(mysqli $connection) { $this->connection = $connection; }
    public function escape($value) { return $this->connection->real_escape_string($value); }
    public function query($sql)
    {
        $result = $this->connection->query($sql);
        if (!$result instanceof mysqli_result) {
            return $result;
        }
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        if ($this->afterCandidateRead && strpos($sql, 'SELECT p.product_id,p.image') === 0) {
            $callback = $this->afterCandidateRead;
            $this->afterCandidateRead = null;
            $callback();
        }
        return (object)['rows' => $rows, 'row' => $rows[0] ?? [], 'num_rows' => count($rows)];
    }
}

// Model adapters below record admin writes in memory, never in application tables.
class ProductStatusAdminTestDatabase
{
    public $status = 1;
    public $overrides = [];
    public $failUpdate = false;
    public $queries = [];
    public $beforeProductUpdate;

    public function escape($value) { return addslashes((string)$value); }
    public function query($sql)
    {
        $this->queries[] = $sql;
        $rows = [];
        if (strpos($sql, 'information_schema.TABLES') !== false) {
            $rows = [['1' => 1]];
        } elseif (preg_match('/^SELECT status FROM oc_product /', $sql)) {
            $rows = [['status' => $this->status]];
        } elseif (strpos($sql, 'INSERT INTO `oc_product_luceed_status_override`') === 0) {
            preg_match_all('/\((\d+),1,NOW\(\)\)/', $sql, $ids);
            foreach ($ids[1] as $id) { $this->overrides[(int)$id] = 1; }
        } elseif (strpos($sql, 'DELETE FROM `oc_product_luceed_status_override`') === 0) {
            preg_match('/IN \(([\d,]+)\)/', $sql, $ids);
            foreach (explode(',', $ids[1]) as $id) { unset($this->overrides[(int)$id]); }
        } elseif (strpos($sql, 'UPDATE oc_product SET ') === 0) {
            if ($this->beforeProductUpdate) {
                $callback = $this->beforeProductUpdate;
                $this->beforeProductUpdate = null;
                $callback();
            }
            if ($this->failUpdate) { throw new RuntimeException('Simulated product update failure'); }
            if (preg_match('/status = IF\(EXISTS \(SELECT 1 FROM `oc_product_luceed_status_override` WHERE product_id=(\d+) AND manual_disabled=1\),0,([01])\)/', $sql, $status)) {
                $this->status = empty($this->overrides[(int)$status[1]]) ? (int)$status[2] : 0;
            } elseif (preg_match("/status = '([01])'/", $sql, $status)) {
                $this->status = (int)$status[1];
            }
        }
        return (object)['rows' => $rows, 'row' => $rows[0] ?? [], 'num_rows' => count($rows)];
    }
}

class ProductStatusAdminTestServices
{
    public $data = [];
    public function get($key) { return 0; }
    public function delete($key) {}
    public function library($key) {}
    public function queueEntity($type, $id, $action) {}
    public function write($message) {}
    public static function store($message, $filename = 'store') {}
}

class Model
{
    private $testServices;
    public function __construct($services) { $this->testServices = $services; }
    public function __get($key) { return $this->testServices->$key; }
}

function checkAdminStatusHooks(): void
{
    // Keep the expected error-path logger entirely in memory as well.
    class_alias(ProductStatusAdminTestServices::class, 'Agmedia\\Helpers\\Log');
    require dirname(__DIR__, 2) . '/upload/admin/model/catalog/product.php';
    require dirname(__DIR__, 2) . '/upload/admin/model/extension/module/product_quick_edit.php';
    $db = new ProductStatusAdminTestDatabase();
    $support = new ProductStatusAdminTestServices();
    $registry = (object)['db'=>$db,'cache'=>$support,'load'=>$support,'indexnow'=>$support,'log'=>$support,'config'=>$support,'session'=>$support];
    $native = new ModelCatalogProduct($registry);
    $quickEdit = new ModelExtensionModuleProductQuickEdit($registry);
    $fields = ['model','sku','upc','ean','jan','isbn','mpn','location','quantity','minimum','subtract','stock_status_id','date_available','manufacturer_id','shipping','price','price_2','jm','pakiranje','vpc','points','weight','weight_class_id','length','width','height','length_class_id','status','tax_class_id','sort_order'];
    $data = array_fill_keys($fields, 0);
    $data['product_description'] = [];

    $native->editProduct(42, $data);
    checkStatusTest($db->overrides, [42=>1], 'Native manual disable records override');
    $data['status'] = 1;
    $native->editProduct(42, $data);
    checkStatusTest($db->overrides, [], 'Native manual enable clears override');
    $db->status = 0;
    $data['status'] = 0;
    $native->editProduct(42, $data);
    checkStatusTest($db->overrides, [], 'Ordinary save of an unchanged disabled status creates no lock');
    $db->status = 1;
    $native->editProduct(42, $data, true);
    checkStatusTest($db->overrides, [], 'Native Luceed update creates no manual lock');
    $db->status = 1;
    $data['_luceed_sync'] = true;
    $native->editProduct(42, $data);
    checkStatusTest($db->overrides, [], 'Marked Luceed update creates no manual lock');

    $quickEdit->quickEditProduct(42, 'status', '0');
    checkStatusTest($db->overrides, [42=>1], 'Quick Edit explicit disable records lock even when already disabled');
    $quickEdit->quickEditProduct(42, 'status', '1');
    checkStatusTest($db->overrides, [], 'Quick Edit enable clears manual lock');
    $quickEdit->quickEditProduct(42, 'status', '0', ['_luceed_sync'=>true]);
    checkStatusTest($db->overrides, [], 'Marked Quick Edit sync does not create manual lock');

    $data['status'] = 1;
    $db->beforeProductUpdate = function () use ($db) { $db->overrides[42] = 1; };
    $native->editProduct(42, $data);
    checkStatusTest($db->status, 0, 'Manual disable arriving during full import wins at the native UPDATE');
    checkStatusTest($db->overrides, [42=>1], 'Concurrent administrator lock survives full import');

    $db->failUpdate = true;
    foreach ([true, false] as $legacySyncFlag) {
        $threw = false;
        $before = count($db->queries);
        try { $native->editProduct(42, $data, $legacySyncFlag); }
        catch (RuntimeException $exception) { $threw = true; }
        checkStatusTest($threw, true, 'Failed Luceed product UPDATE propagates failure');
        checkStatusTest(count($db->queries) - $before, 2, 'Failed Luceed update stops after schema check and product UPDATE');
    }
}

$assertions = 0;
function checkStatusTest($actual, $expected, string $message): void
{
    global $assertions;
    $assertions++;
    if ($actual !== $expected) {
        throw new RuntimeException($message . ': expected ' . json_encode($expected) . ', got ' . json_encode($actual));
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, '', DB_PORT);
$schema = 'chipoteka_status_test_' . getmypid() . '_' . bin2hex(random_bytes(4));
$connection->query('CREATE DATABASE `' . $schema . '` CHARACTER SET utf8');
$imageDirectory = sys_get_temp_dir() . '/chipoteka_status_images_' . bin2hex(random_bytes(4));

try {
    mkdir($imageDirectory . '/catalog', 0700, true);
    mkdir($imageDirectory . '/catalog/directory.jpg', 0700);
    foreach (['camera.jpg', 'item.jpg', 'item.pdf'] as $image) {
        file_put_contents($imageDirectory . '/catalog/' . $image, 'Local image fixture');
    }
    file_put_contents($imageDirectory . '_outside.jpg', 'Outside image fixture');
    $connection->select_db($schema);
    $db = new ProductStatusTestDatabase($connection);
    $db->query('CREATE TABLE test_product (product_id INT PRIMARY KEY,luceed_uid VARCHAR(64),status TINYINT NOT NULL,quantity INT NOT NULL,price DECIMAL(15,4),image VARCHAR(255),date_modified DATETIME) ENGINE=InnoDB');
    $db->query('CREATE TABLE test_product_description (product_id INT,language_id INT,description TEXT) ENGINE=InnoDB');
    $db->query('CREATE TABLE test_product_luceed (uid VARCHAR(64) PRIMARY KEY) ENGINE=InnoDB');
    $service = new LOC_ProductStatus($db, 'test_', 2, $imageDirectory);

    checkStatusTest($service->reconcile()['skipped'], true, 'Older deployment without eligibility columns is skipped');
    checkStatusTest($service->needsSnapshotRefresh(), true, 'Older deployment requests full eligibility snapshot');
    $db->query('ALTER TABLE test_product_luceed ADD eligible TINYINT NOT NULL DEFAULT 0, ADD synced_at DATETIME NULL');
    checkStatusTest($service->reconcile()['skipped'], true, 'Missing minimum metadata is skipped');
    checkStatusTest($service->needsSnapshotRefresh(), true, 'Missing minimum metadata requests full eligibility snapshot');
    $db->query('ALTER TABLE test_product_luceed ADD min_quantity INT UNSIGNED NOT NULL DEFAULT 0');
    checkStatusTest($service->reconcile()['skipped'], true, 'Empty eligibility cache does not change status');
    checkStatusTest($service->needsSnapshotRefresh(), true, 'Empty eligibility cache requests refresh');

    // [id, status, quantity, price, image, description language, description, eligibility, age, minimum]
    $fixtures = [
        [1, 0, 10, 279, 'catalog/camera.jpg', 2, 'Camera', 1, 'fresh', 1],
        [2, 0, 0, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 1],
        [3, 0, 10, 10, '', 2, 'Item', 1, 'fresh', 1],
        [4, 0, 10, 10, 'catalog/item.jpg', 2, '  ', 1, 'fresh', 1],
        [5, 0, 10, 0, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 1],
        [6, 0, 10, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 1],
        [7, 0, 10, 10, 'catalog/item.jpg', 2, 'Item', 1, 'stale', 1],
        [8, 1, 10, 10, 'catalog/item.jpg', 2, 'Item', 0, 'fresh', 1],
        [9, 1, 10, 10, 'catalog/item.jpg', 2, 'Item', 0, 'stale', 1],
        [10, 1, 10, 10, 'catalog/item.jpg', 2, 'Item', 0, 'missing', 1],
        [11, 1, 0, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 1],
        [12, 1, 10, 10, 'catalog/item.jpg', 2, 'Item', 0, 'legacy', 1],
        [13, 0, 10, 10, 'catalog/item.jpg', 2, 'Item', 0, 'missing', 1],
        [14, 0, 10, 10, 'catalog/products/no-image.jpg', 2, 'Item', 1, 'fresh', 1],
        [15, 0, 10, 10, 'no_image.png', 2, 'Item', 1, 'fresh', 1],
        [16, 0, 10, 10, 'catalog/item.jpg', 1, 'Other language', 1, 'fresh', 1],
        [17, 0, 1, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 2],
        [18, 0, 2, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 2],
        [19, 1, 1, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 2],
        [20, 1, 10, 10, 'catalog/item.jpg', 2, 'Item', 0, 'future', 1],
        [21, 0, 10, 10, 'not_valid_image', 2, 'Item', 1, 'fresh', 0],
        [22, 0, 10, 10, 'catalog/item.pdf', 2, 'Item', 1, 'fresh', 0],
        [23, 0, 10, 10, 'catalog/missing.jpg', 2, 'Item', 1, 'fresh', 0],
        [24, 0, 10, 10, 'catalog/directory.jpg', 2, 'Item', 1, 'fresh', 0],
        [25, 0, 10, 10, '../' . basename($imageDirectory) . '_outside.jpg', 2, 'Item', 1, 'fresh', 0],
        [26, 1, 10, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 0],
        [27, 1, 0, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 0],
        [28, 0, 0, 10, 'catalog/item.jpg', 2, 'Item', 1, 'fresh', 0],
    ];

    foreach ($fixtures as $fixture) {
        [$id, $status, $quantity, $price, $image, $language, $description, $eligible, $age, $minimum] = $fixture;
        $db->query("INSERT INTO test_product VALUES ($id,'uid-$id',$status,$quantity,$price,'" . $db->escape($image) . "',NOW())");
        $db->query("INSERT INTO test_product_description VALUES ($id,$language,'" . $db->escape($description) . "')");
        if ($age !== 'missing') {
            $dates = ['fresh' => 'NOW()', 'stale' => 'DATE_SUB(NOW(), INTERVAL 49 HOUR)', 'legacy' => 'NULL', 'future' => 'DATE_ADD(NOW(), INTERVAL 1 DAY)'];
            $db->query("INSERT INTO test_product_luceed VALUES ('uid-$id',$eligible," . $dates[$age] . ",$minimum)");
        }
    }

    $beforeManualDisable = $service->guardedStatusExpression(6, 1);
    $service->setManualStatus([6, 6, 0, -1], 0);
    $db->query('UPDATE test_product SET status=' . $beforeManualDisable . ' WHERE product_id=6');
    checkStatusTest((int)$db->query('SELECT status FROM test_product WHERE product_id=6')->row['status'], 0, 'Database write expression observes manual lock inserted after status was resolved');
    $service->setManualStatus([26], 0);
    checkStatusTest($service->needsSnapshotRefresh(), false, 'Snapshot newer than a day does not request repeated API fetch');
    checkStatusTest($service->resolveUpdateStatus(6, 0, 1, 10), 0, 'Administrator disable blocks ERP reactivation');
    checkStatusTest($service->resolveUpdateStatus(1, 0, 1, 10), 1, 'Eligible disabled product can reactivate');
    checkStatusTest($service->resolveUpdateStatus(1, 1, 0, 10), 0, 'ERP disable wins');
    checkStatusTest($service->resolveUpdateStatus(1, 1, 1, 0), 1, 'Active zero-stock product without configured minimum keeps historical behavior');
    checkStatusTest($service->resolveUpdateStatus(1, 1, 1, 0, 1), 0, 'An active product below an explicit minimum deactivates');
    checkStatusTest($service->resolveUpdateStatus(1, 0, 1, 1, 2), 0, 'Quantity below explicit minimum stays disabled');
    checkStatusTest($service->resolveUpdateStatus(1, 0, 1, 2, 2), 1, 'Quantity at explicit minimum activates');
    checkStatusTest($service->resolveUpdateStatus(1, 0, 1, 0, 0), 0, 'Minimum zero still requires positive stock');
    checkStatusTest($service->resolveUpdateStatus(1, 0, 1, 1, 0), 1, 'Minimum zero normalizes to one');

    checkStatusTest($service->reconcile(), ['activated' => 2, 'deactivated' => 4, 'skipped' => false], 'Reconciliation changes only eligible fresh candidates and enforces manual locks');
    $expected = [1=>1,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>1,10=>1,11=>0,12=>1,13=>0,14=>0,15=>0,16=>0,17=>0,18=>1,19=>0,20=>1,21=>0,22=>0,23=>0,24=>0,25=>0,26=>0,27=>1,28=>0];
    foreach ($db->query('SELECT product_id,status FROM test_product ORDER BY product_id')->rows as $product) {
        checkStatusTest((int)$product['status'], $expected[(int)$product['product_id']], 'Expected fixture status ' . $product['product_id']);
    }
    checkStatusTest($service->reconcile(), ['activated' => 0, 'deactivated' => 0, 'skipped' => false], 'Reconciliation is idempotent');

    $service->setManualStatus([6], 1);
    checkStatusTest($service->resolveUpdateStatus(6, 0, 1, 10), 1, 'Explicit administrator enable clears lock');
    $db->afterCandidateRead = function () use ($service) { $service->setManualStatus([6], 0); };
    checkStatusTest($service->reconcile()['activated'], 0, 'Manual lock inserted after candidate read blocks activation at write time');
    checkStatusTest((int)$db->query('SELECT status FROM test_product WHERE product_id=6')->row['status'], 0, 'Concurrent manual disable remains disabled');
    $service->setManualStatus([6], 1);
    $db->afterCandidateRead = function () use ($db) { $db->query("UPDATE test_product SET image='catalog/missing.jpg' WHERE product_id=6"); };
    checkStatusTest($service->reconcile()['activated'], 0, 'Changed image after candidate read cannot reuse an earlier validated path');
    $db->query("UPDATE test_product SET image='catalog/item.jpg' WHERE product_id=6");
    checkStatusTest($service->reconcile()['activated'], 1, 'Cleared manual disable can activate on reconciliation');
    $db->query('UPDATE test_product SET quantity=1 WHERE product_id=18');
    checkStatusTest($service->reconcile()['deactivated'], 1, 'Stock drop below per-product minimum deactivates');
    $db->query('UPDATE test_product SET quantity=2 WHERE product_id=18');
    checkStatusTest($service->reconcile()['activated'], 1, 'Stock recovery to minimum reactivates');
    $service->setManualStatus([18], 0);
    $db->query('UPDATE test_product SET status=0 WHERE product_id=18');
    checkStatusTest($service->reconcile()['activated'], 0, 'Manual disable remains protected after stock recovery');

    // Once installed, resolving status must not issue DDL that commits a caller transaction.
    $db->query('START TRANSACTION');
    $db->query('UPDATE test_product SET quantity=999 WHERE product_id=1');
    (new LOC_ProductStatus($db, 'test_', 2))->resolveUpdateStatus(1, 1, 1, 999);
    $db->query('ROLLBACK');
    checkStatusTest((int)$db->query('SELECT quantity FROM test_product WHERE product_id=1')->row['quantity'], 10, 'Existing override table does not implicitly commit caller transaction');

    $db->query('UPDATE test_product_luceed SET synced_at=DATE_SUB(NOW(), INTERVAL 25 HOUR)');
    checkStatusTest($service->needsSnapshotRefresh(), true, 'Snapshot older than a day requests refresh');
    checkStatusTest($service->reconcile()['skipped'], false, 'A day-old accepted snapshot remains usable after temporary API failure');
    $db->query('UPDATE test_product_luceed SET synced_at=DATE_SUB(NOW(), INTERVAL 49 HOUR)');
    $beforeStaleReconcile = $db->query('SELECT product_id,status FROM test_product ORDER BY product_id')->rows;
    checkStatusTest($service->reconcile()['skipped'], true, 'Snapshot older than two days safely skips all status reconciliation');
    checkStatusTest($db->query('SELECT product_id,status FROM test_product ORDER BY product_id')->rows, $beforeStaleReconcile, 'Stale reconciliation leaves statuses unchanged');

    checkAdminStatusHooks();
    echo 'PASS: ' . $assertions . " product status assertions (disposable local schema and in-memory admin adapters).\n";
} finally {
    $connection->query('DROP DATABASE `' . $schema . '`');
    $connection->close();
    foreach (['camera.jpg', 'item.jpg', 'item.pdf'] as $image) {
        @unlink($imageDirectory . '/catalog/' . $image);
    }
    @unlink($imageDirectory . '_outside.jpg');
    @rmdir($imageDirectory . '/catalog/directory.jpg');
    @rmdir($imageDirectory . '/catalog');
    @rmdir($imageDirectory);
}
