<?php
class ModelExtensionInformationParent extends Model {
	public function install() { 
$this->db->query("ALTER TABLE  `".DB_PREFIX."information` ADD  `parent_id` INT NOT NULL AFTER `information_id`");
$this->db->query("ALTER TABLE  `".DB_PREFIX."information` ADD  `image` VARCHAR( 255 ) NOT NULL AFTER `parent_id`");
	}
	public function uninstall() {
	$this->db->query("ALTER TABLE `".DB_PREFIX."information` DROP `parent_id`");
	$this->db->query("ALTER TABLE `".DB_PREFIX."information` DROP `image`");
	}
}
