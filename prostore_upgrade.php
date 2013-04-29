<?php
/**
 * Pro Store 0.8 -> 0.9
 * 
 * Upgrade script.
 *  
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jazzza001.com
 *  
 * Please do not redistribute or sell this plugin.
 */

define("IN_MYBB", 1);
require_once "global.php";

//******************************************************[ UPGRADE ]
if ($mybb->input['action'] == 'upgrade') {
	if ($mybb->input['from']) {
		switch ($mybb->input['from']) {
			case '0.8':
				//Insert new transaction columns...
				//NOTE: Reverse it!
				$db->query("ALTER TABLE `".TABLE_PREFIX."prostore_transactions`
					ADD `paypal_email` varchar (1024) NOT NULL default '' AFTER `postcost`,
					ADD `postal_details` varchar(1024) NOT NULL default '' AFTER `postcost`,
					ADD `paid_postcost` varchar(256) NOT NULL default 0 AFTER `postcost`,
					ADD `paid_prodcost` varchar(256) NOT NULL default 0 AFTER `postcost`
				");
				
				$db->query("ALTER TABLE `".TABLE_PREFIX."prostore_transactions` 
					ADD `delivery_status` INT NOT NULL default 0 AFTER `status`");
				
				//Use current amounts for the paid amounts...
				$db->query("UPDATE `".TABLE_PREFIX."prostore_transactions` SET `paid_prodcost` = `prodcost`");
				$db->query("UPDATE `".TABLE_PREFIX."prostore_transactions` SET `paid_postcost` = `postcost`");
				
				//Rename status column...
				$db->query("ALTER TABLE `".TABLE_PREFIX."prostore_transactions` CHANGE `status` `payment_status` INT NOT NULL default 0");
				
				//Make all transactions "arrived"...
				$db->query("UPDATE `".TABLE_PREFIX."prostore_transactions` SET `delivery_status` = '2'");
				
				//Templates to insert...
				if ($mybb->input['replace_templates'] == '1') {
					//Delete all current ones...
					$db->delete_query("templates", "`title` LIKE 'prostore%'");

					//Insert all new ones...
					$newtemplates = array();
				} else {
					$newtemplates = array(
						'prostore_trans_view_delivery_update',
						'prostore_user_controls_trans_view',
						'prostore_no_trans_row',

						'prostore_trans_payment_pending',
						'prostore_trans_payment_pending',
						'prostore_trans_payment_paid',
						'prostore_trans_payment_failed',
						'prostore_trans_payment_refunded',
						'prostore_trans_payment_other',

						'prostore_trans_delivery_notsent',
						'prostore_trans_delivery_sent',
						'prostore_trans_delivery_arrived',
						'prostore_trans_delivery_notarrived'
					);
				}
				
				//Insert new templates...
				prostore_insert_templates($newtemplates);
				
				//Templates to insert...
				if ($mybb->input['replace_settings'] == '1') {
					//Delete all current ones...
					$db->delete_query("settings", "name LIKE 'prostore%'");

					//Insert all new ones...
					$newsettings = array();
				} else {
					$newsettings = array(
						'prostore_enable_notify',
						'prostore_notify_sender',
						'prostore_notify_message',
						'prostore_closed_reason'
					);
				}
				
				//Insert new settings...
				prostore_insert_settings($newsettings);
				
				$success = true;
			break;
			default:
				die('Invalid version specified!');
		}
		
		if ($success) {
			die('<b>Upgrade from '.$mybb->input['form'].' complete!</b> Delete this file!');
		}
	} else {
		die('No version specified!');
	}
}

//******************************************************[ LANDING ]
if ($mybb->input['action'] == '') {
	$newversion = 0.9;
	
//	if (function_exists('prostore_info')) {
//		$currentplugin = prostore_info();
//		$currentversion = $currentplugin['version'];
//	} else {
//		$currentversion = 'unknown';
//	}
	
	$html  = '';
	$html .= '<h1>Pro Suite Upgrade Script 1.0</h1>';
	$html .= '<h2>Plugin: Pro Store '.$newversion.'</h2>';
//	$html .= '<strong>Current version:</strong> '.$currentversion;
//	
//	//Check if they actually need to upgrade...
//	if ($currentversion >= $newversion) {
//		$html .= ' <span style="color:red">You do not need to upgrade!</span>';
//	}
	
	$html .= '<h3>How to upgrade</h3>';
	$html .= '<ul>';
	$html .= '	<li>Do not deactivate the plugin. This script requires functions within the plugin. Disable access using .htaccess to prevent access.';
	$html .= '	<li></li>';
	$html .= '	<li>Perform a database backup.</li>';
	$html .= '	<li>Run this script. It will update your settings, database, and add new templates. Note: Some templates will need manual updating and removal.</li>';
	$html .= '</ul>';
	$html .= '<form action="" method="post">';
	$html .= '	<label>Select your current plugin version:</label>';
	$html .= '	<select name="from">';
	$html .= '		<option value="0.8">0.8</option>';
	$html .= '	</select>';
	$html .= '	<br /><br />';
	$html .= '	Replace all templates? <input type="checkbox" name="replace_templates" value="1" />';
	$html .= '	<br /><br />';
	$html .= '	Replace all settings? <input type="checkbox" name="replace_settings" value="1" />';
	$html .= '	<br /><br />';
	$html .= '	<strong>Remember to delete this file after the upgrade!</strong>';
	$html .= '	<br /><br />';
	$html .= '	<input type="hidden" name="action" value="upgrade" />';
	$html .= '	<input type="submit" value="Upgrade" style="margin-left: 100px" />';
	$html .= '</form>';

	echo $html;
}
?>