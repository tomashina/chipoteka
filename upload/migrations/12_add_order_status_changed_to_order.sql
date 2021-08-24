ALTER TABLE `oc_order`
ADD COLUMN `order_status_changed` DATETIME NULL AFTER `order_status_id`;