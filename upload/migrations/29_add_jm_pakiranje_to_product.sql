ALTER TABLE `oc_product`
    ADD COLUMN `jm` VARCHAR(191) NULL AFTER `vpc`,
    ADD COLUMN `pakiranje` INT(11) NULL AFTER `jm`;