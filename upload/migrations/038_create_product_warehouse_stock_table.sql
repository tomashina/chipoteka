CREATE TABLE IF NOT EXISTS `oc_product_warehouse_stock` (
    `product_id` INT(11) NOT NULL,
    `luceed_uid` VARCHAR(255) NOT NULL,
    `warehouse_code` VARCHAR(32) NOT NULL,
    `warehouse_uid` VARCHAR(64) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`warehouse_code`, `product_id`),
    KEY `idx_product_quantity` (`product_id`, `quantity`),
    KEY `idx_luceed_uid` (`luceed_uid`),
    KEY `idx_warehouse_quantity_product` (`warehouse_code`, `quantity`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
