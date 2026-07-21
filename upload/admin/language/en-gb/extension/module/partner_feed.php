<?php
$_['heading_title'] = 'Luceed Partners and XML Feeds';

$_['text_extension'] = 'Extensions';
$_['text_home'] = 'Home';
$_['text_list'] = 'Luceed partners and XML feeds';
$_['text_add'] = 'Add partner';
$_['text_edit'] = 'Edit partner';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';
$_['text_missing_group'] = 'Missing group';
$_['text_never'] = 'Never';
$_['text_copy'] = 'Copy XML URL';
$_['text_create_group'] = '-- Create a new customer group --';
$_['text_success'] = 'The partner has been saved.';
$_['text_success_delete'] = 'The partner was removed from the module. Its customer group and prices were not deleted.';
$_['text_success_key'] = 'A new access key was generated. The previous XML URL is no longer valid.';
$_['text_success_sync'] = 'Synchronized %s prices for %s.';
$_['text_success_sync_all'] = 'Synchronized %s partner prices in total.';
$_['text_partial_sync'] = 'Synchronized %s prices. Partners with errors kept their previous prices: %s';
$_['text_confirm_delete'] = 'Remove this partner from the module? Its customer group will not be deleted.';
$_['text_confirm_key'] = 'Generate a new key? The current XML URL will immediately stop working.';
$_['text_confirm_sync'] = 'Start Luceed synchronization?';

$_['column_name'] = 'Partner';
$_['column_luceed_id'] = 'Luceed customer ID';
$_['column_customer_group'] = 'OpenCart group';
$_['column_prices'] = 'Prices';
$_['column_sync'] = 'Luceed sync';
$_['column_feed'] = 'XML feed';
$_['column_last_sync'] = 'Last sync';
$_['column_action'] = 'Action';

$_['entry_name'] = 'Partner name';
$_['entry_code'] = 'Feed code';
$_['entry_luceed_partner_id'] = 'Luceed customer ID';
$_['entry_customer_group'] = 'Customer group';
$_['entry_new_customer_group'] = 'New group name';
$_['entry_status'] = 'Luceed synchronization';
$_['entry_feed_status'] = 'XML feed';
$_['entry_sort_order'] = 'Sort order';

$_['help_code'] = 'Used in the XML URL. It is generated from the name when left empty.';
$_['help_luceed_partner_id'] = 'The ID is stored as text, for example 00983204 or 14956-N1.';
$_['help_customer_group'] = 'Select an existing group or create a new one. Luceed prices are stored in product_discount for this group.';
$_['help_feed_status'] = 'The XML contains products with a price for this group and quantityws greater than zero.';

$_['button_sync_all'] = 'Synchronize all';
$_['button_sync'] = 'Synchronize partner';
$_['button_regenerate'] = 'Generate new key';

$_['error_permission'] = 'You do not have permission to modify Luceed partners and XML feeds.';
$_['error_name'] = 'The name must contain between 2 and 128 characters.';
$_['error_code'] = 'The feed code is invalid.';
$_['error_code_exists'] = 'That feed code already exists.';
$_['error_luceed_partner_id'] = 'The Luceed customer ID is required.';
$_['error_luceed_partner_exists'] = 'That Luceed customer ID is already configured.';
$_['error_customer_group'] = 'Select a customer group or enter a new group name.';
$_['error_new_customer_group'] = 'The new customer group name must contain between 2 and 32 characters.';
$_['error_customer_group_missing'] = 'The selected customer group no longer exists.';
$_['error_customer_group_exists'] = 'That customer group is already linked to another Luceed partner.';
$_['error_not_found'] = 'The partner was not found.';
$_['error_sync'] = 'Luceed synchronization failed: %s';
