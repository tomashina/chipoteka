CREATE TABLE IF NOT EXISTS `oc_contract_withdrawal` (
    `contract_withdrawal_id` INT(11) NOT NULL AUTO_INCREMENT,
    `store_id` INT(11) NOT NULL DEFAULT 0,
    `order_id` INT(11) NOT NULL DEFAULT 0,
    `customer_id` INT(11) NOT NULL DEFAULT 0,
    `firstname` VARCHAR(64) NOT NULL,
    `lastname` VARCHAR(64) NOT NULL,
    `email` VARCHAR(96) NOT NULL,
    `telephone` VARCHAR(32) NOT NULL DEFAULT '',
    `address` TEXT NULL,
    `withdrawal_scope` VARCHAR(16) NOT NULL DEFAULT 'full',
    `products` TEXT NULL,
    `statement` TEXT NOT NULL,
    `comment` TEXT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'new',
    `token` VARCHAR(64) NOT NULL,
    `ip` VARCHAR(40) NOT NULL DEFAULT '',
    `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
    `date_ordered` DATE NULL,
    `date_submitted` DATETIME NOT NULL,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`contract_withdrawal_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_email` (`email`),
    KEY `idx_status` (`status`),
    KEY `idx_date_submitted` (`date_submitted`),
    UNIQUE KEY `uk_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `oc_contract_withdrawal_history` (
    `contract_withdrawal_history_id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_withdrawal_id` INT(11) NOT NULL,
    `status` VARCHAR(32) NOT NULL,
    `notify` TINYINT(1) NOT NULL DEFAULT 0,
    `comment` TEXT NULL,
    `user_id` INT(11) NOT NULL DEFAULT 0,
    `date_added` DATETIME NOT NULL,
    PRIMARY KEY (`contract_withdrawal_history_id`),
    KEY `idx_contract_withdrawal_id` (`contract_withdrawal_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DELETE FROM `oc_seo_url`
WHERE `query` = 'account/contract_withdrawal'
   OR `keyword` = 'raskid-ugovora';

INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, `language_id`, 'account/contract_withdrawal', 'raskid-ugovora'
FROM `oc_language`;

UPDATE `oc_user_group`
SET `permission` = REPLACE(`permission`, '"sale/return"', '"sale/return","sale/contract_withdrawal"')
WHERE `permission` LIKE '%"sale/return"%'
  AND `permission` NOT LIKE '%"sale/contract_withdrawal"%';
