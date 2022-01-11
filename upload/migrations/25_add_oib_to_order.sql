ALTER TABLE `oc_order`
    ADD COLUMN  `oib` VARCHAR (11) NOT NULL;

ALTER TABLE `oc_order` ADD INDEX(`oib`);

