<?php
/**
 * Pro Store 0.9
 * An advanced eCommerce store that integrates with your forum.
 *  
 * Page: Cart management.
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

define('PROSTORE_URL_PRODUCT',	'product.php');
define('PROSTORE_URL_TRANS',		'transaction.php');
define('PROSTORE_URL_CART',			'cart.php');
define('PROSTORE_URL_STORE',		'store.php');


//If the plugin is ready...
if (!function_exists('prostore_activate')) {
	die('Plugin has not been activated! Please contact your administrator!');
}


$lang->load("prostore");


//Check if they are allowed to access the store...
$isallowed = prostore_is_allowed();

//If not in allowed usergroup don't allow them...
if (!$isallowed) {
	error_no_permission();
}


//Get the store info...
$store = prostore_store_getinfo();

//Check if they are a manager...
$ismanager = prostore_is_manager();

//Get the cart info...
$cart = prostore_cart_getinfo($mybb->input['cid']);


add_breadcrumb($store['name'], PROSTORE_URL_STORE);


//If not in allowed usergroup don't allow them...
if ($store['frontstatus'] == 0 && !$ismanager) {
	error($lang->frontend_closed.' '.$mybb->settings['prostore_closed_reason']);
}


if (!$cart) {
	redirect(PROSTORE_URL_STORE, $lang->error_invalid_cart);
}


//Get if the cart is frozen...
$isfrozen = prostore_cart_getfrozen($mybb->input['cid']);

//If cart frozen, show error (unless manager)...
if ($isfrozen && !$ismanager) {
	error($lang->error_cart_frozen);
}


//*****************************************************[ DO EMPTY ]
if ($mybb->input['action'] == 'do_empty') {
	if ($cart['uid'] == $mybb->user['uid'] || $ismanager) {
		$db->query("
			UPDATE ".TABLE_PREFIX."prostore_carts SET 
				`pids` = '',
				`dateupdated` = '".time()."',
				`whoupdated` = '".$mybb->user['uid']."'
			WHERE `cid` = '{$cart['cid']}'");

		redirect(PROSTORE_URL_CART,$lang->cart_empty_success);
	} else {
		error($lang->error_not_manager);
	}
}

//***********************************************[ DO ADD PRODUCT ]
if ($mybb->input['action'] == 'do_add') {
	if ($cart['uid'] == $mybb->user['uid'] || $ismanager) {
		add_breadcrumb($lang->title_trans_addprod, PROSTORE_URL_TRANS);
		
		//Get the product...
		$product = prostore_product_getinfo($mybb->input['pid']);

		if ($product) {
			//If we have some in stock...
			if ($product['numleft'] > 0) {
				if ($cart['pids']) {
					$newpids = $cart['pids'].','.$mybb->input['pid'];
				} else {
					$newpids = $mybb->input['pid'];
				}

				$db->query("
					UPDATE ".TABLE_PREFIX."prostore_carts SET
						`pids` = '".$db->escape_string($newpids)."',
						`dateupdated` = '".time()."',
						`whoupdated` = '".$mybb->user['uid']."'
					WHERE `cid` = '".$db->escape_string($cart['cid'])."'
				");

				redirect(PROSTORE_URL_PRODUCT.'?pid='.$mybb->input['pid'], $lang->cart_add_prod_success);
			} else {
				error($lang->error_no_stock);
			}
		} else {
			error($lang->error_invalid_product);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//********************************************[ DO REMOVE PRODUCT ]
if ($mybb->input['action'] == 'do_remove') {
	if ($cart['uid'] == $mybb->user['uid'] || $ismanager) {
		add_breadcrumb($lang->title_cart_remove_prod, PROSTORE_URL_CART);

		if (intval($mybb->input['pid'])) {
			$pids = explode(',', $cart['pids']);

			foreach ($pids as $pid) {
				//If we did not find the product, keep it in the query, otherwise leave it out...
				if ($mybb->input['pid'] == $pid && $found != true) {
					$found = true;
				} else {
					$newpids[] = $pid;
				}
			}
			if (count($newpids) > 0) {
				$newpids = implode(',', $newpids);
			} else {
				$newpids = '';
			}

			$db->query("
				UPDATE ".TABLE_PREFIX."prostore_carts SET
					`pids` = '".$db->escape_string($newpids)."',
					`dateupdated` = '".time()."',
					`whoupdated` = '".$mybb->user['uid']."'
				WHERE `cid` = '".$db->escape_string($cart['cid'])."'
			");

			if ($cart['uid'] == $mybb->user['uid']) {
				redirect(PROSTORE_URL_CART, $lang->cart_remove_prod_success);
			} else {
				redirect(PROSTORE_URL_CART.'?cid='.$cart['cid'], $lang->cart_remove_prod_success);
			}
		} else {
			error($lang->error_invalid_product);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//*******************************************************[ FREEZE ]
if ($mybb->input['action'] == 'freeze') {
	if ($ismanager) {
			$db->query("
				UPDATE ".TABLE_PREFIX."prostore_carts SET
					`status` = '0'
				WHERE `cid` = '".$db->escape_string($cart['cid'])."'
			");

			redirect(PROSTORE_URL_CART.'?cid='.$cart['cid'],$lang->cart_freeze_success);
	} else {
		error($lang->error_not_manager);
	}
}
	
//*****************************************************[ UNFREEZE ]
if ($mybb->input['action'] == 'unfreeze') {
	if ($ismanager) {
			$db->query("
				UPDATE ".TABLE_PREFIX."prostore_carts SET
					`status` = '1'
				WHERE `cid` = '".$db->escape_string($cart['cid'])."'
			");

			redirect(PROSTORE_URL_CART.'?cid='.$cart['cid'],$lang->cart_unfreeze_success);
	} else {
		error($lang->error_not_manager);
	}
}

//******************************************************[ PROCESS ]
if ($mybb->input['action'] == 'process') {
	if ($cart['uid'] == $mybb->user['uid'] || $ismanager) {
		//Get the products in cart...
		if ($cart['pids']) {
			add_breadcrumb($lang->title_cart_process, PROSTORE_URL_CART);
			
			$pidarray = explode(',', $cart['pids']);
			$numofeach = array_count_values($pidarray);
			$totalquantity = count($pidarray);
			$highest_postcost = 0;

			//Get all product details...
			$query = $db->simple_select("prostore_products","*","`pid` IN ({$cart['pids']})");

			//Add them to the product list...
			while ($product = $db->fetch_array($query)) {
				if ($ismanager) {
					eval("\$managercontrols = \"".$templates->get('prostore_manager_controls_trans_view_productbit')."\";");
				}
				
				$category_query = $db->simple_select("prostore_categories","*","`cid` = '".$product['cid']."'");
				$category = $db->fetch_array($category_query);

				if (!$product['prodcost'] && $product['postcost'] != 0 && $category['prodcost']) {
					$product['prodcost'] = $category['prodcost'];
				}

				if (!$product['postcost'] && $product['postcost'] != 0 && $category['postcost']) {
					$product['postcost'] = $category['postcost'];
				}

				$prodquantity = $numofeach[$product['pid']];

				//If we have more than one of the same type, multiple by them...
				$total_prodcost += $product['prodcost'] * $prodquantity;
				//$total_postcost += $product['postcost'];
				
				//If this product has the biggest postage cost, use it...
//				if ($product['postcost'] > $highest_postcost) {
//					$highest_postcost = $product['postcost'];
//				}
				
				//If we have more than one of the same type, multiple by them...
				$total_postcost += $product['postcost'] * $prodquantity;

				eval("\$paypalproducts .= \"".$templates->get('prostore_cart_process_productbit')."\";");
			}
			
			//The postage cost is the highest cost...
			//$total_postcost = $highest_postcost;
			
			//Clean up any pending transactions...
			$query = $db->query("DELETE FROM ".TABLE_PREFIX."prostore_transactions WHERE `uid` = '".$mybb->user['uid']."' AND `payment_status` = '0'");
			
			//Create a new unprocessed transaction for this cart...
			$inserttrans = array(
				'tid' => '',
				'uid' => $mybb->user['uid'],
				'pids' => $db->escape_string($cart['pids']),
				'paid_prodcost' => '',
				'paid_postcost' => '',

				'dateadded' => time(),
				'dateupdated' => time(),

				'whoadded' => $mybb->user['uid'],
				'whoupdated' => $mybb->user['uid'],

				'payment_status' => 0,						//Pending transaction...
				'delivery_status' => 0		//Not sent...
			);
			$transid = $db->insert_query("prostore_transactions", $inserttrans);

			eval("\$prostore = \"".$templates->get('prostore_cart_process')."\";");

			output_page($prostore);
		} else {
			error($lang->error_cart_empty);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//********************************************************[ EMPTY ]
if ($mybb->input['action'] == 'empty') {
	if ($cart['uid'] == $mybb->user['uid'] || $ismanager) {
		if ($cart['pids']) {
			eval("\$prostore = \"".$templates->get('prostore_cart_empty')."\";");

			output_page($prostore);
		} else {
			error($lang->error_already_empty);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//*********************************************************[ VIEW ]
if ($mybb->input['action'] == 'view' || $mybb->input['action'] == '' || (intval($mybb->input['cid']) && $mybb->request_method == 'get')) {
	if ($cart['uid'] == $mybb->user['uid'] || $ismanager) {
		add_breadcrumb($lang->title_cart_view, PROSTORE_URL_CART);

		//Display store closed reminder...
		if ($mybb->settings['prostore_enable_frontend'] == 0) {
			$closednotice = $lang->frontend_closed_manager;

			eval("\$closednotice = \"".$templates->get('prostore_frontend_closed_warning')."\";");
		}

		//Display frozen cart reminder...
		if ($isfrozen) {
			eval("\$frozennotice = \"".$templates->get('prostore_cart_frozen_warning')."\";");
		}

		//Get the products in cart...
		if ($cart['pids']) {
			$pidarray = explode(',', $cart['pids']);
			$numofeach = array_count_values($pidarray);
			$totalquantity = count($pidarray);
			$total_prodcost = 0;
			$total_postcost = 0;

			//Get all product details...
			$query = $db->simple_select("prostore_products","*","`pid` IN (".$cart['pids'].")");
			
			//'".$cart['pids']."

			//Add them to the product list...
			while ($product = $db->fetch_array($query)) {
				if ($ismanager) {
					eval("\$managercontrols = \"".$templates->get('prostore_manager_controls_trans_view_productbit')."\";");
				} 
				
				$category_query = $db->simple_select("prostore_categories","*","`cid` = '".$product['cid']."'");
				$category = $db->fetch_array($category_query);

				if (!$product['prodcost'] && $product['prodcost'] != 0 && $category['prodcost']) {
					$product['prodcost'] = $category['prodcost'];
				}

				if (!$product['postcost'] && $product['postcost'] != 0  && $category['postcost']) {
					$product['postcost'] = $category['postcost'];
				}

				$prodquantity = $numofeach[$product['pid']];

				$total_prodcost += $product['prodcost'] * $prodquantity;
				
				//If this product has the biggest postage cost, use it...
//				if ($product['postcost'] > $highest_postcost) {
//					$highest_postcost = $product['postcost'];
//				}
				
				//If we have more than one of the same type, multiple by them...
				$total_postcost += $product['postcost'];

				eval("\$productlist .= \"".$templates->get('prostore_cart_view_productbit')."\";");
			}
		} else {
			eval("\$productlist = \"".$templates->get('prostore_no_products_row')."\";");
		}
		
		//The postage cost is the highest cost...
		//$total_postcost = $highest_postcost;

		//Get user who owns the cart...
		$query = $db->simple_select("users","*","`uid` = '{$cart['uid']}'");
		$user = $db->fetch_array($query);
		$userfor = "<a href=\"".get_profile_link($user['uid'])."\">".htmlspecialchars_uni($user['username'])."</a>";
		
		//Get user controls...
		if ($mybb->user['uid']) {
			eval("\$controls = \"".$templates->get('prostore_user_controls_frontend')."\";");
			
			eval("\$usercontrols = \"".$templates->get('prostore_user_controls')."\";");
		}

		//Get manager controls...
		if ($ismanager) {
			if ($cart['status'] == 1) {
				eval("\$togglefreeze = \"".$templates->get('prostore_manager_controls_cart_freeze')."\";");
			} else {
				eval("\$togglefreeze = \"".$templates->get('prostore_manager_controls_cart_unfreeze')."\";");
			}

			eval("\$controls = \"".$templates->get('prostore_manager_controls_cart_view')."\";");

			eval("\$managercontrols = \"".$templates->get('prostore_manager_controls')."\";");
		}

		eval("\$prostore = \"".$templates->get('prostore_cart_view')."\";");

		output_page($prostore);
	} else {
		error($lang->error_not_manager);
	}
}
?>