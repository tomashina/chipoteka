CREATE TABLE IF NOT EXISTS `oc_luceed_store` (
    `store_id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(16) NOT NULL,
    `warehouse_uid` varchar(191) NOT NULL DEFAULT '',
    `name` varchar(255) NOT NULL DEFAULT '',
    `address` varchar(255) NOT NULL DEFAULT '',
    `postal_code` varchar(32) NOT NULL DEFAULT '',
    `city` varchar(128) NOT NULL DEFAULT '',
    `contact` varchar(255) NOT NULL DEFAULT '',
    `pj_uid` varchar(191) NOT NULL DEFAULT '',
    `pj_code` varchar(32) NOT NULL DEFAULT '',
    `pj_name` varchar(255) NOT NULL DEFAULT '',
    `working_hours` text NULL,
    `partner_id` varchar(64) NOT NULL DEFAULT '',
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `is_included` tinyint(1) NOT NULL DEFAULT 0,
    `is_default` tinyint(1) NOT NULL DEFAULT 0,
    `is_availability` tinyint(1) NOT NULL DEFAULT 0,
    `is_store` tinyint(1) NOT NULL DEFAULT 0,
    `is_pickup` tinyint(1) NOT NULL DEFAULT 0,
    `date_added` datetime NOT NULL,
    `date_modified` datetime NOT NULL,
    PRIMARY KEY (`store_id`),
    UNIQUE KEY `uq_luceed_store_code` (`code`),
    UNIQUE KEY `uq_luceed_store_warehouse_uid` (`warehouse_uid`),
    KEY `idx_luceed_store_status_sort` (`status`, `sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `oc_luceed_store`
    (`code`, `warehouse_uid`, `name`, `address`, `postal_code`, `city`, `contact`, `pj_uid`, `pj_code`, `pj_name`, `sort_order`, `status`, `is_included`, `is_default`, `is_availability`, `is_store`, `is_pickup`, `date_added`, `date_modified`)
VALUES
    ('001', '200-1063', 'CHIPOTEKA ZAGREB', 'Ulica grada Vukovara 269 E', '10000', '10000', '', '4342-1', '01', 'PJ ZG Vukovarska', 10, 1, 1, 1, 1, 0, 0, NOW(), NOW()),
    ('002', '201-1063', 'CHIPOTEKA SPLIT', 'Matice Hrvatske 1', '21000', '21000', 'chipoteka@st.t-com.hr', '4343-1', '02', 'PJ Split', 20, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('003', '202-1063', 'CHIPOTEKA RIJEKA', 'Laginjina 4A', '51000', '51000', 'chipoteka-rijeka@ri.t-com.hr', '4344-1', '03', 'PJ Rijeka', 30, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('004', '203-1063', 'CHIPOTEKA OSIJEK', 'Prolaz Julija Benešića 2', '31000', '31000', 'chipoteka@os.t-com.hr', '4345-1', '04', 'PJ Osijek', 40, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('005', '207-1063', 'CHIPOTEKA ZADAR', 'Dr. Franje Tuđmana 19 (bivši TIZ)', '23000', '23000', 'z-el.ababic@chipoteka.hr', '4346-1', '05', 'PJ Zadar', 50, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('006', '204-1063', 'CHIPOTEKA ARENA CENTAR', 'Lanište 32', '10000', '10000', '', '4347-1', '06', 'PJ ZG Arena', 60, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('007', '237-1063', 'CHIPOTEKA PULA', 'Ciscuttijeva 2', '52100', '52100', '', '4348-1', '07', 'PJ Pula', 70, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('011', '208-1063', 'CHIPOTEKA ŠIBENIK', 'Bože Peričića 26', '22000', '22000', '', '10088-1', '11', 'PJ Šibenik', 110, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('012', '209-1063', 'CHIPOTEKA DUBROVNIK', 'Nikole Tesle 2', '20000', '20000', '', '11437-1', '12', 'PJ Dubrovnik', 120, 1, 1, 1, 1, 1, 0, NOW(), NOW()),
    ('101', '210-1063', 'Chipoteka Sesvete - Centralno', 'Industrijska cesta 28', '10360', '10360', '+385 1 2022 740', '4320-1', '00', 'Centrala', 1010, 1, 1, 1, 0, 0, 0, NOW(), NOW());
