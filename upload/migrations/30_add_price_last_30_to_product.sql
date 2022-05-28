ALTER TABLE `oc_product`
    ADD COLUMN `price_last_30` DECIMAL(15,4) NOT NULL DEFAULT '0.0000' AFTER `price_2`;