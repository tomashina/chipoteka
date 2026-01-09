ALTER TABLE `oc_banner_image`
    ADD COLUMN `date_from` DATE NULL DEFAULT NULL AFTER `image_responsive`,
  ADD COLUMN `date_to`   DATE NULL DEFAULT NULL AFTER `date_from`;