<?php

namespace Agmedia\LuceedOpencartWrapper\Models;

use Agmedia\Helpers\Database;

/** Apply fresh Luceed eligibility without losing explicit administrator disables. */
class LOC_ProductStatus
{
    private $db;
    private $prefix;
    private $languageId;
    private $imageDirectory;
    private $overrideTableReady = false;

    public function __construct($db = null, ?string $prefix = null, ?int $languageId = null, ?string $imageDirectory = null)
    {
        $this->db = $db ?: new Database(DB_DATABASE);
        $this->prefix = $prefix === null ? DB_PREFIX : $prefix;

        if (!preg_match('/^[a-zA-Z0-9_]*$/', $this->prefix)) {
            throw new \InvalidArgumentException('Invalid product status table prefix.');
        }

        $this->languageId = $languageId === null
            ? (function_exists('agconf') ? (int)agconf('import.default_language') : 2)
            : $languageId;
        $this->imageDirectory = $imageDirectory === null ? (defined('DIR_IMAGE') ? DIR_IMAGE : '') : $imageDirectory;
    }

    public function resolveUpdateStatus(int $productId, int $currentStatus, int $proposedStatus, int $quantity, int $minimumQuantity = 0): int
    {
        if (!$proposedStatus || ($minimumQuantity > 0 && $quantity < $minimumQuantity) || (!$currentStatus && $quantity <= 0)) {
            return 0;
        }

        $this->ensureOverrideTable();
        $override = $this->db->query("SELECT manual_disabled FROM `" . $this->prefix . "product_luceed_status_override` WHERE product_id = " . $productId . " LIMIT 1");

        return !empty($override->row['manual_disabled']) ? 0 : 1;
    }

    /** An explicit enable removes the disable lock; it does not bypass ERP eligibility. */
    public function setManualStatus(array $productIds, int $status): void
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), function ($id) {
            return $id > 0;
        })));

        if (!$productIds) {
            return;
        }

        $this->ensureOverrideTable();

        foreach (array_chunk($productIds, 500) as $ids) {
            if ($status) {
                $this->db->query("DELETE FROM `" . $this->prefix . "product_luceed_status_override` WHERE product_id IN (" . implode(',', $ids) . ")");
            } else {
                $rows = array_map(function ($id) { return '(' . $id . ',1,NOW())'; }, $ids);
                $this->db->query("INSERT INTO `" . $this->prefix . "product_luceed_status_override` (product_id,manual_disabled,date_modified) VALUES " . implode(',', $rows) . " ON DUPLICATE KEY UPDATE manual_disabled=1,date_modified=NOW()");
            }
        }
    }

    /** Check the manual lock again in the product write, after any slow import work. */
    public function guardedStatusExpression(int $productId, int $proposedStatus): string
    {
        $this->ensureOverrideTable();

        return 'IF(EXISTS (SELECT 1 FROM `' . $this->prefix . 'product_luceed_status_override` WHERE product_id=' . $productId . ' AND manual_disabled=1),0,' . ($proposedStatus ? 1 : 0) . ')';
    }

    /** Reconcile only rows represented by a successfully refreshed eligibility snapshot. */
    public function reconcile(): array
    {
        $this->ensureOverrideTable();
        $this->db->query("UPDATE `" . $this->prefix . "product` p SET p.status=0,p.date_modified=NOW() WHERE p.status=1 AND EXISTS (SELECT 1 FROM `" . $this->prefix . "product_luceed_status_override` o WHERE o.product_id=p.product_id AND o.manual_disabled=1)");
        $manualDeactivated = $this->affectedRows();

        if (!$this->hasSnapshotColumns()) {
            return ['activated' => 0, 'deactivated' => $manualDeactivated, 'skipped' => true, 'reason' => 'Luceed eligibility snapshot is not installed.'];
        }

        $freshSnapshot = $this->db->query("SELECT 1 FROM `" . $this->prefix . "product_luceed` WHERE synced_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) AND synced_at <= NOW() LIMIT 1");

        if (!$freshSnapshot->num_rows) {
            return ['activated' => 0, 'deactivated' => $manualDeactivated, 'skipped' => true, 'reason' => 'No fresh Luceed eligibility snapshot is available.'];
        }

        $fresh = "lp.uid = p.luceed_uid AND lp.synced_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) AND lp.synced_at <= NOW()";
        $placeholder = function_exists('agconf') ? (string)agconf('import.image_placeholder') : 'catalog/products/no-image.jpg';
        $usableImage = "TRIM(COALESCE(p.image,'')) <> '' AND p.image <> 'not_valid_image' AND p.image <> '" . $this->db->escape($placeholder) . "' AND p.image REGEXP '[.](png|jpe?g|gif|webp)$' AND p.image NOT REGEXP '(^|/)(no[_-]?image|placeholder)[.](png|jpe?g|gif|webp)$'";

        $this->db->query("UPDATE `" . $this->prefix . "product` p SET p.status=0,p.date_modified=NOW() WHERE p.status=1 AND EXISTS (SELECT 1 FROM `" . $this->prefix . "product_luceed` lp WHERE " . $fresh . " AND (lp.eligible=0 OR (lp.min_quantity>0 AND p.quantity<lp.min_quantity)))");
        $deactivated = $manualDeactivated + $this->affectedRows();

        $eligible = "p.status=0 AND p.quantity>0 AND p.price>0 AND " . $usableImage . " AND EXISTS (SELECT 1 FROM `" . $this->prefix . "product_description` pd WHERE pd.product_id=p.product_id AND pd.language_id=" . $this->languageId . " AND TRIM(COALESCE(pd.description,'')) <> '') AND EXISTS (SELECT 1 FROM `" . $this->prefix . "product_luceed` lp WHERE " . $fresh . " AND lp.eligible=1 AND p.quantity>=GREATEST(lp.min_quantity,1)) AND NOT EXISTS (SELECT 1 FROM `" . $this->prefix . "product_luceed_status_override` o WHERE o.product_id=p.product_id AND o.manual_disabled=1)";
        $candidates = $this->db->query("SELECT p.product_id,p.image FROM `" . $this->prefix . "product` p WHERE " . $eligible);
        $validated = [];
        foreach ($candidates->rows as $candidate) {
            if ($this->hasLocalImage($candidate['image'])) {
                $validated[] = "(p.product_id=" . (int)$candidate['product_id'] . " AND p.image='" . $this->db->escape($candidate['image']) . "')";
            }
        }

        $activated = 0;
        foreach (array_chunk($validated, 500) as $batch) {
            // Recheck locks and stock after candidate inspection to preserve concurrent admin edits.
            $this->db->query("UPDATE `" . $this->prefix . "product` p SET p.status=1,p.date_modified=NOW() WHERE (" . implode(' OR ', $batch) . ") AND " . $eligible);
            $activated += $this->affectedRows();
        }

        return ['activated' => $activated, 'deactivated' => $deactivated, 'skipped' => false];
    }

    private function hasLocalImage(string $image): bool
    {
        $directory = realpath($this->imageDirectory);
        if ($directory === false || $image === '' || strpos($image, "\0") !== false) {
            return false;
        }

        $path = realpath($directory . DIRECTORY_SEPARATOR . $image);

        return $path !== false && strpos($path, $directory . DIRECTORY_SEPARATOR) === 0 && is_file($path);
    }

    /** Refresh daily; retain the last accepted snapshot for up to 48 hours on API failure. */
    public function needsSnapshotRefresh(): bool
    {
        if (!$this->hasSnapshotColumns()) {
            return true;
        }

        $latest = $this->db->query("SELECT MAX(synced_at) >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AS recent FROM `" . $this->prefix . "product_luceed` WHERE synced_at <= NOW()");

        return empty($latest->row['recent']);
    }

    private function hasSnapshotColumns(): bool
    {
        $columns = $this->db->query("SHOW COLUMNS FROM `" . $this->prefix . "product_luceed`");
        $fields = array_column($columns->rows, 'Field');

        return in_array('eligible', $fields, true) && in_array('synced_at', $fields, true) && in_array('min_quantity', $fields, true);
    }

    private function affectedRows(): int
    {
        $result = $this->db->query('SELECT ROW_COUNT() AS affected');

        return (int)$result->row['affected'];
    }

    private function ensureOverrideTable(): void
    {
        if ($this->overrideTableReady) {
            return;
        }

        $table = $this->prefix . 'product_luceed_status_override';
        $existing = $this->db->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $this->db->escape($table) . "' LIMIT 1");

        if (!$existing->num_rows) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (product_id INT(11) NOT NULL,manual_disabled TINYINT(1) NOT NULL DEFAULT 0,date_modified DATETIME NOT NULL,PRIMARY KEY (product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        $this->overrideTableReady = true;
    }
}
