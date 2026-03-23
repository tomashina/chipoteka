ALTER TABLE `oc_product`
    ADD COLUMN `eprel_model` VARCHAR(64) NULL DEFAULT NULL AFTER `model`;