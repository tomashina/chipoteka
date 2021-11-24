ALTER TABLE `oc_category`
    ADD COLUMN  `group_id` int(1) DEFAULT NULL, AFTER `parent_id`;

