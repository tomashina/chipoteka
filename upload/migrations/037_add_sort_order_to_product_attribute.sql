ALTER TABLE `oc_product_attribute`
    ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 9 AFTER `language_id`;

UPDATE `oc_product_attribute` pa
LEFT JOIN `oc_attribute` a ON a.`attribute_id` = pa.`attribute_id`
SET pa.`sort_order` = COALESCE(a.`sort_order`, 9);

ALTER TABLE `oc_product_attribute`
    ADD INDEX `product_id_language_id_sort_order` (`product_id`, `language_id`, `sort_order`);
