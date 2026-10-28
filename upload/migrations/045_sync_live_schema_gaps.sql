-- Full schema delta: pluschip_2022 (live) -> amds_chipoteka (test).
-- This migration adds schema only; it does not copy, modify or delete data.

ALTER TABLE `oc_banner_image`
    ADD COLUMN `image_responsive` VARCHAR(255) NOT NULL AFTER `image`,
    ADD COLUMN `date_from` DATE NULL DEFAULT NULL AFTER `image_responsive`,
    ADD COLUMN `date_to` DATE NULL DEFAULT NULL AFTER `date_from`;

ALTER TABLE `oc_order`
    ADD COLUMN `eracun` TINYINT(1) NOT NULL DEFAULT 0 AFTER `oib`,
    ADD COLUMN `boxnow` TEXT NULL DEFAULT NULL AFTER `grupa_partnera`;

ALTER TABLE `oc_product_attribute`
    ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 9 AFTER `language_id`,
    ADD KEY `product_id_language_id_sort_order` (`product_id`, `language_id`, `sort_order`);

ALTER TABLE `oc_product_luceed`
    ADD COLUMN `eligible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `hash`,
    ADD COLUMN `synced_at` DATETIME NULL DEFAULT NULL AFTER `eligible`,
    ADD COLUMN `min_quantity` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `synced_at`;

CREATE TABLE IF NOT EXISTS `oc_category_faq` (
    `category_faq_id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL,
    `language_id` INT(11) NOT NULL,
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`category_faq_id`),
    KEY `category_language` (`category_id`, `language_id`, `sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `oc_information_faq` (
    `information_faq_id` INT(11) NOT NULL AUTO_INCREMENT,
    `information_id` INT(11) NOT NULL,
    `language_id` INT(11) NOT NULL,
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`information_faq_id`),
    KEY `information_language` (`information_id`, `language_id`, `sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `oc_indexnow_queue` (
    `indexnow_queue_id` INT(11) NOT NULL AUTO_INCREMENT,
    `url` VARCHAR(2048) NOT NULL,
    `entity_type` VARCHAR(32) NOT NULL DEFAULT '',
    `entity_id` INT(11) NOT NULL DEFAULT 0,
    `action` VARCHAR(16) NOT NULL DEFAULT 'updated',
    `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
    `attempts` INT(11) NOT NULL DEFAULT 0,
    `last_status_code` INT(11) NULL DEFAULT NULL,
    `last_error` TEXT NULL DEFAULT NULL,
    `available_at` DATETIME NOT NULL,
    `submitted_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`indexnow_queue_id`),
    KEY `indexnow_status_available` (`status`, `available_at`),
    KEY `indexnow_entity` (`entity_type`, `entity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `oc_partner_feed` (
    `partner_feed_id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(128) NOT NULL,
    `code` VARCHAR(64) NOT NULL,
    `luceed_partner_id` VARCHAR(128) NOT NULL,
    `customer_group_id` INT(11) NOT NULL,
    `access_key` VARCHAR(128) NOT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `feed_status` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `last_sync_at` DATETIME NULL DEFAULT NULL,
    `last_sync_status` VARCHAR(20) NOT NULL DEFAULT '',
    `last_sync_message` VARCHAR(255) NOT NULL DEFAULT '',
    `last_sync_count` INT(11) NOT NULL DEFAULT 0,
    `date_added` DATETIME NOT NULL,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`partner_feed_id`),
    UNIQUE KEY `partner_feed_code` (`code`),
    UNIQUE KEY `partner_feed_luceed_partner_id` (`luceed_partner_id`),
    KEY `partner_feed_status` (`status`, `feed_status`),
    KEY `partner_feed_customer_group_id` (`customer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_product_luceed_status_override` (
    `product_id` INT(11) NOT NULL,
    `manual_disabled` TINYINT(1) NOT NULL DEFAULT 0,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE IF NOT EXISTS `oc_product_warehouse_stock` (
    `product_id` INT(11) NOT NULL,
    `luceed_uid` VARCHAR(255) NOT NULL,
    `warehouse_code` VARCHAR(32) NOT NULL,
    `warehouse_uid` VARCHAR(64) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`warehouse_code`, `product_id`),
    KEY `idx_product_quantity` (`product_id`, `quantity`),
    KEY `idx_luceed_uid` (`luceed_uid`),
    KEY `idx_warehouse_quantity_product` (`warehouse_code`, `quantity`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
    `refund_iban` VARCHAR(34) NOT NULL DEFAULT '',
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
    UNIQUE KEY `uk_token` (`token`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_email` (`email`),
    KEY `idx_status` (`status`),
    KEY `idx_date_submitted` (`date_submitted`)
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

CREATE TABLE IF NOT EXISTS `oc_order_duration_notification` (
    `order_duration_notification_id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `order_status_id` INT(11) NOT NULL,
    `order_status_changed` DATETIME NOT NULL,
    `payment_code` VARCHAR(128) NOT NULL,
    `threshold_hours` SMALLINT(5) UNSIGNED NOT NULL,
    `mail_id` VARCHAR(32) NOT NULL,
    `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
    `attempts` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `last_error` TEXT NULL,
    `date_added` DATETIME NOT NULL,
    `date_modified` DATETIME NOT NULL,
    `date_sent` DATETIME NULL,
    PRIMARY KEY (`order_duration_notification_id`),
    UNIQUE KEY `uk_order_duration_notification` (`order_id`, `order_status_id`, `order_status_changed`, `threshold_hours`),
    KEY `idx_duration_notification_status` (`status`, `date_modified`),
    KEY `idx_duration_notification_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `oc_order_duration_notification_state` (
    `state_key` VARCHAR(64) NOT NULL,
    `state_value` VARCHAR(255) NOT NULL,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`state_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `oc_faq_category` (
    `faq_category_id` INT(11) NOT NULL AUTO_INCREMENT,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `date_added` DATETIME NOT NULL,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`faq_category_id`),
    KEY `faq_category_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_faq_category_description` (
    `faq_category_id` INT(11) NOT NULL,
    `language_id` INT(11) NOT NULL,
    `name` VARCHAR(128) NOT NULL,
    PRIMARY KEY (`faq_category_id`, `language_id`),
    KEY `faq_category_description_language` (`language_id`, `name`),
    CONSTRAINT `fk_faq_category_description_category`
        FOREIGN KEY (`faq_category_id`) REFERENCES `oc_faq_category` (`faq_category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_faq_item` (
    `faq_item_id` INT(11) NOT NULL AUTO_INCREMENT,
    `faq_category_id` INT(11) NOT NULL,
    `language_id` INT(11) NOT NULL,
    `question` VARCHAR(500) NOT NULL,
    `answer` MEDIUMTEXT NOT NULL,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `date_added` DATETIME NOT NULL,
    `date_modified` DATETIME NOT NULL,
    PRIMARY KEY (`faq_item_id`),
    KEY `faq_item_category_language_status_sort` (`faq_category_id`, `language_id`, `status`, `sort_order`),
    CONSTRAINT `fk_faq_item_category`
        FOREIGN KEY (`faq_category_id`) REFERENCES `oc_faq_category` (`faq_category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
