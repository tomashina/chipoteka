-- Chipoteka: Luceed partneri i XML feedovi
-- Idempotentno za live bazu s OpenCart prefiksom oc_.
-- Nakon deploya koda u Admin > Proširenja > Moduli instalirati
-- "Luceed partneri i XML feedovi" kako bi se dodijelile admin ovlasti.

CREATE TABLE IF NOT EXISTS `oc_partner_feed` (
  `partner_feed_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `code` varchar(64) NOT NULL,
  `luceed_partner_id` varchar(128) NOT NULL,
  `customer_group_id` int(11) NOT NULL,
  `access_key` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `feed_status` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `last_sync_at` datetime DEFAULT NULL,
  `last_sync_status` varchar(20) NOT NULL DEFAULT '',
  `last_sync_message` varchar(255) NOT NULL DEFAULT '',
  `last_sync_count` int(11) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`partner_feed_id`),
  UNIQUE KEY `partner_feed_code` (`code`),
  UNIQUE KEY `partner_feed_luceed_partner_id` (`luceed_partner_id`),
  UNIQUE KEY `partner_feed_customer_group_id` (`customer_group_id`),
  KEY `partner_feed_status` (`status`,`feed_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `oc_partner_feed`
  (`name`, `code`, `luceed_partner_id`, `customer_group_id`, `access_key`, `status`, `feed_status`, `sort_order`, `date_added`, `date_modified`)
VALUES
  ('N1 B2B', 'n1-b2b', '14956-N1', 3, '', 1, 0, 10, NOW(), NOW()),
  ('N2 B2B', 'n2-b2b', '14956-N2', 4, '', 1, 0, 20, NOW(), NOW()),
  ('Bazzar', 'bazzar', '80045', 5, 'gNG_p]x7V[+I=uaIgjo=hTMQX~s', 1, 1, 30, NOW(), NOW()),
  ('Shoppster', 'shoppster', '00983204', 6, 'd15onhQrRpq?^e@Fz*]g', 1, 1, 40, NOW(), NOW());
