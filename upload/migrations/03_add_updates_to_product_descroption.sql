ALTER TABLE `oc_product_description`
ADD COLUMN `update_name` TINYINT(1) NOT NULL AFTER `name`,
ADD COLUMN `update_description` TINYINT(1) NOT NULL AFTER `description`;