ALTER TABLE `oc_product`
    ADD COLUMN `quantityws` int(4) NOT NULL DEFAULT 0 AFTER `quantity`;