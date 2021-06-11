CREATE TABLE IF NOT EXISTS `oc_manufacturer_description` (
    `manufacturer_id` int(11) NOT NULL,
    `language_id` int(11) NOT NULL,
    `description` text NOT NULL,
    `meta_title` varchar(255) NOT NULL,
    `meta_description` varchar(255) NOT NULL,
    `meta_keyword` varchar(255) NOT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;