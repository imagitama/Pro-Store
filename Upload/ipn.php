<?php
/**
 * Pro Store 0.9
 * An advanced eCommerce store that integrates with your forum.
 * 
 * Page: PayPal Instant Notification.
 *  
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jazzza001.com
 *  
 * Please do not redistribute or sell this plugin.
 */

//Note: Must be accessible to guests!

define("IN_MYBB", 1);
require_once "global.php";

define('PROSTORE_URL_PRODUCT',	'product.php');
define('PROSTORE_URL_TRANS',		'transaction.php');
define('PROSTORE_URL_CART',			'cart.php');
define('PROSTORE_URL_STORE',		'store.php');


//If the plugin is ready...
if (!function_exists('prostore_activate')) {
	die('Plugin has not been activated! Please contact your administrator!');
}


$lang->load("prostore");


//Get the store info...
$store = prostore_store_getinfo();


//NOTE: For testing purposes.
//$valid = true;
//$_POST['payment_status'] = 'Completed';


//Get the transaction info...
$trans = prostore_trans_getinfo($mybb->input['custom']);


//*****************************************************[ VALIDATE ]
if ($mybb->input['action'] == 'validate') {
	if ($trans) {
		$data = $_POST;
		
		$valid = prostore_ipn_validate($data);

		//If PayPal sent us a real IPN...
		if ($valid) {
			$newstatus = $data['payment_status'];
			
			//New transaction status...
			switch ($newstatus) {
				case 'Pending':
					//Pending
					$newstatus_id = 0;
				break;
				case 'Completed':
				case 'Created':
				case 'Processed':
					//Paid
					$newstatus_id = 1;
				break;
				case 'Failed':
				case 'Denied':
				case 'Voided':
				case 'Expired':
				case 'Canceled_Reversal':
					//Failed
					$newstatus_id = 2;
				break;
				case 'Refunded':
				case 'Reversal':
					$newstatus_id = 3;
				break;
				default:
					//Failed?
					$newstatus_id = 2;
			}
			
			//Postal details (need new lines)...
			$postal  = ''.$data['address_name'].' 
'.$data['address_street'].'
'.$data['address_city'].' '.$data['address_state'].' '.$data['address_zip'].'
'.$data['address_country'];
	
			//Update the transaction with the new status...
			$db->query("UPDATE ".TABLE_PREFIX."prostore_transactions SET 
				`paid_prodcost` = '".$db->escape_string($data['mc_gross'])."',
				`paid_postcost` = '".$db->escape_string($data['shipping'])."',
				`postal_details` = '".$db->escape_string($postal)."',
				`paypal_email` = '".$db->escape_string($data['payer_email'])."',
				`dateupdated` = ".time().",
				`payment_status` = '".$newstatus_id."'
				WHERE `tid` = ".$trans['tid']."");
			
			//Clear the user's cart...
			$db->query("UPDATE ".TABLE_PREFIX."prostore_carts SET `pids` = '' WHERE `uid` = ".$trans['uid']."");
			
			//If a successful payment, update stock levels...
			if ($newstatus_id == 1) {
				//Get the product IDs...
				$pidarray = explode(',', $trans['pids']);
				$numofeach = array_count_values($pidarray);
				
				//Array of product IDs that are out of stock...
				$nostock = array();

				//Get the products...
				$query = $db->simple_select("prostore_products","*","`pid` IN ({$trans['pids']})");
				if ($db->num_rows($query) > 0) {
					while ($product = $db->fetch_array($query)) {
						//Calculate new values...
						$new_numleft = $product['numleft'] - $numofeach[$product['pid']];
						$new_numsold = $product['numsold'] + $numofeach[$product['pid']];
						
						//If no stock left...
						if ($new_numleft <= 0) {
							$nostock[] = $product['pid'];
						}

						//Update stock levels...
						$db->query("UPDATE ".TABLE_PREFIX."prostore_products SET 
							`numleft` = '".$new_numleft."',
							`numsold` = '".$new_numsold."'
							WHERE `pid` = ".$product['pid']."
						");
					}
				}
				
				//Remove out of stock items from carts...
				$allcarts = $db->query("SELECT * FROM `".TABLE_PREFIX."prostore_carts`");
				
				while ($cart = $db->fetch_array($allcarts)) {
					$pids = explode(',', $cart['pids']);
					
					foreach ($pids as $pid) {
						//If this product is still in stock...
						if (in_array($pid, $nostock)) {
							//Re-add it...
							$newpids[] = $pid;
						} else {
							//Out of stock, so do not re-add...
						}
					}
					$newpids = implode(',', $newpids);

					//TODO: Notify customers!
					$db->query("
						UPDATE ".TABLE_PREFIX."prostore_carts SET
							`pids` = '".$db->escape_string($newpids)."'
						WHERE `cid` = '".$db->escape_string($cart['cid'])."'
					");
				}
			}
			
			//If we are to notify managers...
			if ($mybb->settings['prostore_enable_notify'] == 1) {
				//Get the user details...
				$user = $db->query("SELECT * FROM ".TABLE_PREFIX."users WHERE `uid` = '".$trans['uid']."'");
				$user = $db->fetch_array($user);
				
				//Get the message...
				$message = $mybb->settings['prostore_notify_message'];

				//Build a link to the user...
				$username = '[url='.$mybb->settings['bburl'].'/'.get_profile_link($trans['uid']).']'.$user['username'].'[/url]';
				
				//Build a link to the transaction...
				$transurl = $mybb->settings['bburl'].'/'.PROSTORE_URL_TRANS.'?tid='.$trans['tid'].'';
				
				//Replace variables...
				$message = str_replace('{customer}', $username, $message);
				$message = str_replace('{transurl}', $transurl, $message);

				//Notify our managers!
				prostore_notify_managers($message);
			}
			
			die('Success');
		} else {
			//Invalid PayPal transaction...
			//TODO: Log this.
			die('Invalid');
		}
	} else {
		//Invalid store transaction...
		//TODO: Log this.
		die('Invalid');
	}
}
die('Failure');
?>
