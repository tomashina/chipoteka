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

INSERT IGNORE INTO `oc_order_duration_notification_state`
    (`state_key`, `state_value`, `date_modified`)
VALUES
    ('activated_at', '2026-07-17 13:35:10', '2026-07-17 13:35:10');
