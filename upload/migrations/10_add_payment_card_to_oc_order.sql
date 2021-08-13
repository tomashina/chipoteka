ALTER TABLE `oc_order`
ADD COLUMN `payment_card` VARCHAR(32)  NULL AFTER `payment_code`;