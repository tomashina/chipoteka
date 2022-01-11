ALTER TABLE `oc_order`
    ADD COLUMN  `grupa_partnera` VARCHAR (11) NOT NULL;

ALTER TABLE `oc_order` ADD INDEX(`grupa_partnera`);

