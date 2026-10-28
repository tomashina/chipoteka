<?php

/**
 * Exercise the real Luceed controller with in-memory service boundaries.
 * No application configuration, database connections, HTTP calls, or mail are used.
 * Run: php scripts/tests/luceed_sync_controller_test.php
 */

namespace {
    if (PHP_SAPI !== 'cli') {
        http_response_code(404);
        exit;
    }

    error_reporting(E_ALL & ~E_DEPRECATED);

    class LuceedControllerTestState
    {
        public static $events = [];
        public static $needsRefresh = true;
        public static $snapshotFails = false;
        public static $stockFails = false;
        public static $stockValid = true;
        public static $reconcileFails = false;
        public static $snapshotUsable = true;
        public static $lockAvailable = true;
        public static $queue = [1, 2, 3];
        public static $failProductId = null;

        public static function reset(): void
        {
            self::$events = [];
            self::$needsRefresh = true;
            self::$snapshotFails = false;
            self::$stockFails = false;
            self::$stockValid = true;
            self::$reconcileFails = false;
            self::$snapshotUsable = true;
            self::$lockAvailable = true;
            self::$queue = [1, 2, 3];
            self::$failProductId = null;
        }

        public static function record(string $event): void
        {
            self::$events[] = $event;
        }
    }
}

namespace Agmedia\Helpers {
    class Log
    {
        public static function store($message, $name = null) {}
    }
}

namespace Agmedia\Luceed\Connection {
    class LuceedService
    {
        public function probeAvailability(): array
        {
            \LuceedControllerTestState::record('availability');
            return ['status' => 'up'];
        }
    }
}

namespace Agmedia\Luceed\Facade {
    class LuceedProduct
    {
        public static function all(): string
        {
            \LuceedControllerTestState::record('product_feed');
            if (\LuceedControllerTestState::$snapshotFails) {
                throw new \RuntimeException('Injected product feed failure');
            }
            return 'in-memory feed';
        }

        public static function getWarehouseStock($warehouse): string
        {
            \LuceedControllerTestState::record('warehouse_feed');
            return 'in-memory warehouse';
        }

        public static function getSuplierStock(): string
        {
            \LuceedControllerTestState::record('supplier_feed');
            return 'in-memory suppliers';
        }
    }
}

namespace Agmedia\Luceed\Models {
    class LuceedProductForUpdate
    {
        public static function all()
        {
            return collect(array_map(function ($id) { return (object)['uid' => 'u' . $id]; }, \LuceedControllerTestState::$queue));
        }

        public static function where($column, $uid)
        {
            return new class($uid) {
                private $uid;
                public function __construct($uid) { $this->uid = $uid; }
                public function delete(): void
                {
                    $id = (int)substr($this->uid, 1);
                    \LuceedControllerTestState::record('queue_delete_' . $id);
                    \LuceedControllerTestState::$queue = array_values(array_diff(\LuceedControllerTestState::$queue, [$id]));
                }
            };
        }

        public static function truncate(): void
        {
            throw new \RuntimeException('Controller must not truncate pending product work.');
        }
    }
}

namespace Agmedia\LuceedOpencartWrapper\Models {
    class LOC_Product
    {
        public function __construct($feed) {}
        public function populateLuceedData(): array
        {
            \LuceedControllerTestState::record('snapshot_commit');
            \LuceedControllerTestState::$needsRefresh = false;
            \LuceedControllerTestState::$snapshotUsable = true;
            return ['status' => 200, 'total' => 100, 'inserting' => 0, 'updating' => 3];
        }
    }

    class LOC_ProductStatus
    {
        public function needsSnapshotRefresh(): bool
        {
            \LuceedControllerTestState::record('needs_snapshot_refresh');
            return \LuceedControllerTestState::$needsRefresh;
        }

        public function reconcile(): array
        {
            \LuceedControllerTestState::record('reconcile');
            if (\LuceedControllerTestState::$reconcileFails) {
                throw new \RuntimeException('Injected reconciliation failure');
            }
            return \LuceedControllerTestState::$snapshotUsable
                ? ['activated' => 7, 'deactivated' => 2, 'skipped' => false]
                : ['activated' => 0, 'deactivated' => 0, 'skipped' => true];
        }
    }

    class LOC_Stock
    {
        public function setSkladista($feed) { return $this; }
        public function setDobavljaci($feed) { return $this; }
        public function sort() { return $this; }
        public function isValid(): bool { return \LuceedControllerTestState::$stockValid; }
        public function createQuery() { return $this; }
        public function update(): int
        {
            \LuceedControllerTestState::record('stock_update');
            if (\LuceedControllerTestState::$stockFails) {
                throw new \RuntimeException('Injected stock commit failure');
            }
            \LuceedControllerTestState::record('stock_commit');
            return 123;
        }
        public function updateWithoutSupliers(): int { return $this->update(); }
    }

    class LOC_ProductSingle
    {
        public $product;
        public $product_to_update;

        public function hasForUpdate($uid = null): bool
        {
            \LuceedControllerTestState::record('read_product_queue');
            $id = $uid === null ? (\LuceedControllerTestState::$queue[0] ?? null) : (int)substr($uid, 1);
            if (!$id) {
                return false;
            }
            $this->product = ['naziv' => 'Article ' . $id];
            $this->product_to_update = ['product_id' => $id, 'luceed_uid' => 'u' . $id, 'quantity' => 10, 'stock_status_id' => 1];
            return true;
        }
        public function makeForUpdate(array $old): array { return ['status' => 1, '_luceed_sync' => true]; }
        public function finishUpdate(): array
        {
            \LuceedControllerTestState::record('mark_checked_' . $this->product_to_update['product_id']);
            return ['status' => 200, 'message' => 'updated'];
        }
        public function hasForInsert(): bool { return false; }
        public function hasForDelete(): bool { return false; }
        public function finish(): array { return ['status' => 200, 'message' => 'finish']; }
    }
}

namespace {
    define('OC_ENV', ['import' => ['warehouse' => ['default' => 1]]]);
    require dirname(__DIR__, 2) . '/storage/vendor/autoload.php';
    require dirname(__DIR__, 2) . '/upload/system/engine/controller.php';
    require dirname(__DIR__, 2) . '/upload/admin/controller/extension/module/luceed_sync.php';

    class LuceedControllerTestRegistry
    {
        private $values = [];
        public function get($key) { return $this->values[$key] ?? null; }
        public function set($key, $value): void { $this->values[$key] = $value; }
    }

    class LuceedControllerTestDatabase
    {
        public function query(string $query)
        {
            if (preg_match("/GET_LOCK\('([^']+)'/", $query, $matches)) {
                LuceedControllerTestState::record('lock_acquire_' . $matches[1]);
                return (object)['row' => ['acquired' => LuceedControllerTestState::$lockAvailable ? 1 : 0]];
            }
            if (preg_match("/RELEASE_LOCK\('([^']+)'/", $query, $matches)) {
                LuceedControllerTestState::record('lock_release_' . $matches[1]);
                return (object)['row' => ['released' => 1]];
            }
            throw new RuntimeException('Unexpected database access: ' . $query);
        }
    }

    class LuceedControllerTestResponse
    {
        public $headers = [];
        public $body;
        public function addHeader($header): void { $this->headers[] = $header; }
        public function setOutput($output): void { $this->body = json_decode($output, true, 512, JSON_THROW_ON_ERROR); }
    }

    class LuceedControllerTestServices
    {
        public function model($route): void {}
        public function language($route): void {}
        public function delete($key): void {}
        public function get($key): string
        {
            return $key === 'text_success_update_stock' ? 'Zalihe su ažurirane.' : 'Zalihe nisu ažurirane.';
        }
    }

    class LuceedControllerTestProductModel
    {
        public function __call($name, $arguments)
        {
            if (strpos($name, 'getProduct') === 0) {
                return [];
            }
            throw new RuntimeException('Unexpected model method: ' . $name);
        }
        public function editProduct($id, $data): void
        {
            LuceedControllerTestState::record('edit_product_' . $id);
            if ($id === LuceedControllerTestState::$failProductId) {
                throw new RuntimeException('Injected native product edit failure');
            }
        }
    }

    // Prevent unrelated end-of-batch notification behavior from sending mail.
    class LuceedControllerUnderTest extends ControllerExtensionModuleLuceedSync
    {
        public function finishUpdateProduct()
        {
            LuceedControllerTestState::record('finish_product_batch');
        }
    }

    function controllerFixture(): array
    {
        $registry = new LuceedControllerTestRegistry();
        $response = new LuceedControllerTestResponse();
        $services = new LuceedControllerTestServices();
        $registry->set('db', new LuceedControllerTestDatabase());
        $registry->set('response', $response);
        $registry->set('request', (object)['get' => [], 'post' => [], 'server' => ['SERVER_PROTOCOL' => 'HTTP/1.1']]);
        $registry->set('load', $services);
        $registry->set('language', $services);
        $registry->set('cache', $services);
        $registry->set('model_catalog_product', new LuceedControllerTestProductModel());
        return [new LuceedControllerUnderTest($registry), $response];
    }

    $assertions = 0;
    function controllerCheck(bool $condition, string $message): void
    {
        global $assertions;
        $assertions++;
        if (!$condition) {
            throw new RuntimeException($message . '\nEvents: ' . json_encode(LuceedControllerTestState::$events));
        }
    }

    function has503(LuceedControllerTestResponse $response): bool
    {
        return count(array_filter($response->headers, function ($header) { return strpos($header, ' 503 ') !== false; })) > 0;
    }

    function eventCount(string $event): int
    {
        return count(array_keys(LuceedControllerTestState::$events, $event, true));
    }

    foreach (['updateQuantities', 'updateQuantitiesWithoutSupliers'] as $route) {
        LuceedControllerTestState::reset();
        [$controller, $response] = controllerFixture();
        $controller->$route();
        controllerCheck(eventCount('needs_snapshot_refresh') === 1, $route . ': consult daily/schema refresh readiness');
        controllerCheck(eventCount('snapshot_commit') === 1, $route . ': refresh uninstalled/old eligibility snapshot automatically');
        controllerCheck(eventCount('reconcile') === 1, $route . ': reconcile exactly once after the stock update');
        controllerCheck(array_search('reconcile', LuceedControllerTestState::$events, true) > array_search('stock_commit', LuceedControllerTestState::$events, true), $route . ': publication must follow a successful stock commit');
        controllerCheck($response->body['status'] === 200 && !has503($response), $route . ': successful feeds return 200');
        controllerCheck($response->body['activation'] === ['activated' => 7, 'deactivated' => 2, 'skipped' => false], $route . ': response contains changes from post-stock reconciliation');

        LuceedControllerTestState::reset();
        LuceedControllerTestState::$needsRefresh = false;
        [$controller, $response] = controllerFixture();
        $controller->$route();
        controllerCheck(eventCount('product_feed') === 0 && eventCount('snapshot_commit') === 0, $route . ': recent snapshot avoids unnecessary full feed');
        controllerCheck(eventCount('stock_commit') === 1 && eventCount('reconcile') === 1, $route . ': a recent snapshot still reconciles new stock');

        foreach (['stockFails', 'stockValid'] as $failure) {
            LuceedControllerTestState::reset();
            LuceedControllerTestState::$$failure = $failure === 'stockFails';
            [$controller, $response] = controllerFixture();
            $controller->$route();
            controllerCheck($response->body['status'] === 503 && has503($response), $route . ': failed/invalid stock returns HTTP 503');
            controllerCheck(eventCount('stock_commit') === 0 && eventCount('reconcile') === 0, $route . ': failed/invalid stock must not change publication');
            controllerCheck(!isset($response->body['activation']), $route . ': failure must not report completed activation');
            controllerCheck(eventCount('lock_release_chipoteka_luceed_product_sync') === 1, $route . ': failure releases shared lock');
        }

        LuceedControllerTestState::reset();
        LuceedControllerTestState::$snapshotFails = true;
        [$controller, $response] = controllerFixture();
        $controller->$route();
        controllerCheck(eventCount('stock_commit') === 1 && eventCount('reconcile') === 1, $route . ': product-feed failure permits valid independent stock update');
        controllerCheck($response->body['status'] === 503 && has503($response), $route . ': product-feed warning surfaces HTTP 503');
        controllerCheck(strpos($response->body['message'], 'Luceed popis artikala nije obnovljen') !== false, $route . ': response identifies failed snapshot refresh');
        controllerCheck($response->body['activation']['activated'] === 7, $route . ': usable preceding snapshot may reconcile committed stock');

        LuceedControllerTestState::reset();
        LuceedControllerTestState::$snapshotFails = true;
        LuceedControllerTestState::$snapshotUsable = false;
        [$controller, $response] = controllerFixture();
        $controller->$route();
        controllerCheck(eventCount('stock_commit') === 1, $route . ': missing initial metadata does not block stock update when snapshot unavailable');
        controllerCheck($response->body['status'] === 503 && $response->body['activation']['skipped'], $route . ': unusable snapshot explicitly skips activation');
        controllerCheck(strpos($response->body['message'], 'preskočena') !== false, $route . ': response truthfully states skipped activation');

        LuceedControllerTestState::reset();
        LuceedControllerTestState::$reconcileFails = true;
        [$controller, $response] = controllerFixture();
        $controller->$route();
        controllerCheck(eventCount('stock_commit') === 1, $route . ': reconciliation failure occurs after committed stock');
        controllerCheck($response->body['status'] === 503 && has503($response), $route . ': reconciliation failure returns HTTP 503');
        controllerCheck(strpos($response->body['message'], 'Zalihe su ažurirane') !== false && strpos($response->body['message'], 'aktivacije nije uspjela') !== false, $route . ': response distinguishes saved stock from failed activation');
    }

    LuceedControllerTestState::reset();
    LuceedControllerTestState::$snapshotFails = true;
    LuceedControllerTestState::$snapshotUsable = false;
    [$controller, $response] = controllerFixture();
    $controller->updateProduct();
    controllerCheck($response->body['status'] === 503 && has503($response), 'Stale interactive queue waits for verified snapshot');
    controllerCheck(LuceedControllerTestState::$queue === [1, 2, 3] && eventCount('read_product_queue') === 0, 'Stale interactive queue keeps every pending item without processing it');

    LuceedControllerTestState::reset();
    LuceedControllerTestState::$failProductId = 2;
    [$controller, $response] = controllerFixture();
    $controller->updateProducts();
    controllerCheck($response->body['status'] === 503 && has503($response), 'Failed native edit causes bulk sync failure');
    controllerCheck(LuceedControllerTestState::$queue === [2, 3], 'Failed and remaining native updates remain queued; successful first item is removed');
    controllerCheck(eventCount('mark_checked_1') === 1 && eventCount('mark_checked_2') === 0 && eventCount('queue_delete_2') === 0, 'Failed product edit is neither marked checked nor dequeued');
    controllerCheck(eventCount('edit_product_3') === 0 && eventCount('finish_product_batch') === 0, 'Failed native edit stops batch without claiming completion');

    foreach (['updateQuantities', 'updateQuantitiesWithoutSupliers', 'updateProducts', 'updateProduct', 'importProducts', 'importLuceedProducts', 'checkMinQty'] as $route) {
        LuceedControllerTestState::reset();
        LuceedControllerTestState::$lockAvailable = false;
        [$controller, $response] = controllerFixture();
        $controller->$route();
        controllerCheck(eventCount('lock_acquire_chipoteka_luceed_product_sync') === 1, $route . ': uses the shared stock/product lock');
        controllerCheck(eventCount('stock_update') === 0 && eventCount('product_feed') === 0 && eventCount('read_product_queue') === 0 && eventCount('reconcile') === 0, $route . ': lock conflict prevents competing writes');
    }

    echo 'PASS: ' . $assertions . " controller assertions (in-memory boundaries; no database, remote calls, or mail).\n";
}
