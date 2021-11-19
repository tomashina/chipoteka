ALTER TABLE `oc_category`
    ADD COLUMN `lc_uid` VARCHAR(255) NULL DEFAULT NULL AFTER `luceed_uid`;

ALTER TABLE `oc_manufacturer`
    ADD COLUMN `lc_uid` VARCHAR(255) NULL DEFAULT NULL AFTER `luceed_uid`;