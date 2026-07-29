-- Chipoteka: kategorije čestih pitanja i odgovori
-- Idempotentno za live bazu s OpenCart prefiksom oc_.

CREATE TABLE IF NOT EXISTS `oc_faq_category` (
  `faq_category_id` int(11) NOT NULL AUTO_INCREMENT,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`faq_category_id`),
  KEY `faq_category_status_sort` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_faq_category_description` (
  `faq_category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`faq_category_id`, `language_id`),
  KEY `faq_category_description_language` (`language_id`, `name`),
  CONSTRAINT `fk_faq_category_description_category`
    FOREIGN KEY (`faq_category_id`) REFERENCES `oc_faq_category` (`faq_category_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `oc_faq_item` (
  `faq_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `faq_category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` mediumtext NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`faq_item_id`),
  KEY `faq_item_category_language_status_sort`
    (`faq_category_id`, `language_id`, `status`, `sort_order`),
  CONSTRAINT `fk_faq_item_category`
    FOREIGN KEY (`faq_category_id`) REFERENCES `oc_faq_category` (`faq_category_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEO URL /cesta-pitanja za sve aktivne jezike zadane trgovine.
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`)
SELECT 0, l.language_id, 'information/faq', 'cesta-pitanja'
FROM `oc_language` l
WHERE l.status = 1
  AND NOT EXISTS (
    SELECT 1
    FROM `oc_seo_url` su
    WHERE su.store_id = 0
      AND su.language_id = l.language_id
      AND su.query = 'information/faq'
  );

-- Dodijeli FAQ ovlasti grupama koje već smiju uređivati Informacije.
UPDATE `oc_user_group`
SET `permission` = JSON_SET(
  `permission`,
  '$.access',
  JSON_ARRAY_APPEND(
    COALESCE(JSON_EXTRACT(`permission`, '$.access'), JSON_ARRAY()),
    '$',
    'catalog/faq'
  )
)
WHERE JSON_VALID(`permission`)
  AND JSON_CONTAINS(
    COALESCE(JSON_EXTRACT(`permission`, '$.access'), JSON_ARRAY()),
    JSON_QUOTE('catalog/information')
  ) = 1
  AND JSON_CONTAINS(
    COALESCE(JSON_EXTRACT(`permission`, '$.access'), JSON_ARRAY()),
    JSON_QUOTE('catalog/faq')
  ) = 0;

UPDATE `oc_user_group`
SET `permission` = JSON_SET(
  `permission`,
  '$.modify',
  JSON_ARRAY_APPEND(
    COALESCE(JSON_EXTRACT(`permission`, '$.modify'), JSON_ARRAY()),
    '$',
    'catalog/faq'
  )
)
WHERE JSON_VALID(`permission`)
  AND JSON_CONTAINS(
    COALESCE(JSON_EXTRACT(`permission`, '$.modify'), JSON_ARRAY()),
    JSON_QUOTE('catalog/information')
  ) = 1
  AND JSON_CONTAINS(
    COALESCE(JSON_EXTRACT(`permission`, '$.modify'), JSON_ARRAY()),
    JSON_QUOTE('catalog/faq')
  ) = 0;
