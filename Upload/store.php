<?php
/**
 * Pro Store 0.9
 * An advanced eCommerce store that integrates with your forum.
 * 
 * Page: Store frontend & backend.
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


add_breadcrumb($store['name'], PROSTORE_URL_STORE);


//If not in allowed usergroup don't allow them...
if ($store['frontstatus'] == 0 && !$ismanager) {
	error($lang->frontend_closed.' '.$mybb->settings['prostore_closed_reason']);
}


//******************************************************[ BACKEND ]
if ($mybb->input['action'] == 'backend') {
	if ($ismanager) {
		add_breadcrumb($lang->title_backend, PROSTORE_URL_STORE.'?action=backend');
		
		//Display store closed reminder...
		if ($mybb->settings['prostore_enable_frontend'] == 0) {
			$closednotice = $lang->frontend_closed_manager;
			
			eval("\$closednotice = \"".$templates->get('prostore_frontend_closed_warning')."\";");
		}
		
		//Display manager controls...
		if ($ismanager) {
			if ($store['frontstatus'] == 1) {
				eval("\$togglestore = \"".$templates->get('prostore_manager_controls_closestore')."\";");
			} else {
				eval("\$togglestore = \"".$templates->get('prostore_manager_controls_openstore')."\";");
			}
			
			eval("\$controls = \"".$templates->get('prostore_manager_controls_backend')."\";");

			eval("\$managercontrols = \"".$templates->get('prostore_manager_controls')."\";");
		}
		
		//Get product list...
		$query = $db->simple_select("prostore_products", "*", "");
		if ($db->num_rows($query) > 0) {
			while ($product = $db->fetch_array($query)) {
				if ($product['numleft'] < 0) {
					eval("\$numleft = \"".$templates->get('prostore_backend_num_none')."\";");
				} elseif ($product['numleft'] <= 5 && $product['numleft'] > 0) {
					eval("\$numleft = \"".$templates->get('prostore_backend_num_verylow')."\";");
				} elseif ($product['numleft'] <= 15 && $product['numleft'] > 5) {
					eval("\$numleft = \"".$templates->get('prostore_backend_num_low')."\";");
				} elseif ($product['numleft'] > 0) {
					eval("\$numleft = \"".$templates->get('prostore_backend_num_normal')."\";");
				}
				
				eval("\$productlist .= \"".$templates->get('prostore_backend_productbit')."\";");
			}
		} else {
			eval("\$productlist = \"".$templates->get('prostore_no_products_row')."\";");
		}
		
		//Get transactions list...
		$query = $db->simple_select("prostore_transactions", "*", "");
		if ($db->num_rows($query) > 0) {
			while ($trans = $db->fetch_array($query)) {
				//Get the products in the transaction...
				$products = explode(',', $trans['pids']);
				$productnum = count($products);
				
				//Get user who did the transaction...
				$user_query = $db->simple_select("users","*","`uid` = '{$trans['uid']}'");
				$user = $db->fetch_array($user_query);
				$userlink = get_profile_link($user['uid']);
				eval("\$userfor = \"".$templates->get('prostore_trans_view_userbit')."\";");
				
				//Format...
				$paid_prodcost = number_format(floatval($trans['paid_prodcost']), 2, '.', ',');
				$paid_postcost = number_format(floatval($trans['paid_postcost']), 2, '.', ',');
				
				//Get status...
				switch ($trans['payment_status']) {
					case 0:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_pending')."\";");
					break;
					case 1:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_paid')."\";");
					break;
					case 2:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_failed')."\";");
					break;
					case 3:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_refunded')."\";");
					break;
					case 4:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_other')."\";");
					break;
				}

				//Get status...
				switch ($trans['delivery_status']) {
					case 0:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_notsent')."\";");
					break;
					case 1:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_sent')."\";");
					break;
					case 2:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_arrived')."\";");
					break;
					case 3:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_notarrived')."\";");
					break;
				}
				
				eval("\$translist .= \"".$templates->get('prostore_backend_transbit')."\";");
			}
		} else {
			eval("\$translist = \"".$templates->get('prostore_no_trans_row')."\";");
		}
		
		//Get closed reason for text field...
		$closed_reason = $mybb->settings['prostore_closed_reason'];
		
		eval("\$prostore = \"".$templates->get('prostore_backend')."\";");
		
		output_page($prostore);
	} else {
		error($lang->error_not_manager);
	}
}

//******************************************************[ BACKEND ]
if ($mybb->input['action'] == 'update') {
	if ($ismanager) {
		prostore_update_backend();
		
		redirect(PROSTORE_URL_STORE.'?action=backend', $lang->msg_store_updated);
	} else {
		error($lang->error_not_manager);
	}
}
	
//***************************************************[ OPEN STORE ]
if ($mybb->input['action'] == 'open') {
	if ($ismanager) {
		prostore_open_frontend();
		
		redirect(PROSTORE_URL_STORE, $lang->frontend_opened);
	} else {
		error($lang->error_not_manager);
	}
}
	
//**************************************************[ CLOSE STORE ]
if ($mybb->input['action'] == 'close') {
	if ($ismanager) {
		prostore_close_frontend();

		redirect(PROSTORE_URL_STORE, $lang->frontend_closed);
	} else {
		error($lang->error_not_manager);
	}
}

//***********************************************[ PRODUCT BOUGHT ]
if ($mybb->input['action'] == 'goodpurchase') {
	//PayPal gives us a transaction ID...
	$transid = $mybb->input['txn_id'];
	
	if ($mybb->input['custom'] == '') {
		add_breadcrumb($lang->title_product_buy_success, PROSTORE_URL_STORE);
		
		//Insert a new transaction...
		$inserttrans = array(
			'tid' => '',
			'pids' => $db->escape_string('1'),
			'prodcost' => $db->escape_string('97.33'),
			'postcost' => $db->escape_string('10.00'),
			'dateadded' => time(),
			'dateupdated' => time(),
			'whoadded' => $mybb->user['uid'],
			'whoupdated' => $mybb->user['uid'],
			'status' => '2'	//Paid
		);
		$lastpid = $db->insert_query("prostore_transactions", $inserttrans);

		eval("\$prostore = \"".$templates->get('prostore_product_buy_success')."\";");

		output_page($prostore);
	} else {
		error($lang->error_invalid_product);
	}
}

//******************************************************[ VIEW OWN ]
if ($mybb->input['action'] == 'mytrans') {
	if ($mybb->user['uid']) {
		add_breadcrumb($lang->title_trans_viewown, PROSTORE_URL_STORE);

		//Get transactions list...
		$query = $db->simple_select("prostore_transactions", "*", "`uid` = '{$mybb->user['uid']}'");
		
		if ($db->num_rows($query) > 0) {
			while ($trans = $db->fetch_array($query)) {
				//Get the products in the transaction...
				$products = explode(',', $trans['pids']);
				$productnum = count($products);
				
				//Get user who did the transaction...
				$user_query = $db->simple_select("users","*","`uid` = '{$trans['uid']}'");
				$user = $db->fetch_array($user_query);
				$userlink = get_profile_link($user['uid']);
				eval("\$userfor = \"".$templates->get('prostore_trans_view_userbit')."\";");
				
				//Format...
				$paid_prodcost = number_format(floatval($trans['paid_prodcost']), 2, '.', ',');
				$paid_postcost = number_format(floatval($trans['paid_postcost']), 2, '.', ',');
				
				//Get status...
				switch ($trans['payment_status']) {
					case 0:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_pending')."\";");
					break;
					case 1:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_paid')."\";");
					break;
					case 2:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_failed')."\";");
					break;
					case 3:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_refunded')."\";");
					break;
					case 4:
						eval("\$payment_status = \"".$templates->get('prostore_trans_payment_other')."\";");
					break;
				}

				//Get status...
				switch ($trans['delivery_status']) {
					case 0:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_notsent')."\";");
					break;
					case 1:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_sent')."\";");
					break;
					case 2:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_arrived')."\";");
					break;
					case 3:
						eval("\$delivery_status = \"".$templates->get('prostore_trans_delivery_notarrived')."\";");
					break;
				}
				
				eval("\$translist .= \"".$templates->get('prostore_trans_viewown_transbit')."\";");
			}
		} else {
			redirect(PROSTORE_URL_STORE, $lang->error_no_mytrans);
		}

		eval("\$prostore = \"".$templates->get('prostore_trans_viewown')."\";");

		output_page($prostore);
	} else {
		error_no_permission();
	}
}

//*******************************************[ TERMS & CONDITIONS ]
if ($mybb->input['action'] == 'terms') {
	add_breadcrumb($lang->title_terms, PROSTORE_URL_STORE);
	
	//TODO: Update.
	
	eval("\$prostore = \"".$templates->get('prostore_terms')."\";");
	
	output_page($prostore);
}

//*********************************************************[ HELP ]
if ($mybb->input['action'] == 'help') {
	add_breadcrumb($lang->title_help, PROSTORE_URL_STORE);
	
	eval("\$prostore = \"".$templates->get('prostore_help')."\";");
	
	output_page($prostore);
}

//*****************************************************[ FRONTEND ]
if ($mybb->input['action'] == 'frontend' || $mybb->input['action'] == '') {
	//Display store closed reminder...
	if ($mybb->settings['prostore_enable_frontend'] == 0) {
		$closednotice = $lang->frontend_closed_manager;

		eval("\$closednotice = \"".$templates->get('prostore_frontend_closed_warning')."\";");
	}

	//Display manager controls...
	if ($ismanager) {
		if ($store['frontstatus'] == 1) {
			eval("\$togglestore = \"".$templates->get('prostore_manager_controls_closestore')."\";");
		} else {
			eval("\$togglestore = \"".$templates->get('prostore_manager_controls_openstore')."\";");
		}

		eval("\$controls = \"".$templates->get('prostore_manager_controls_frontend')."\";");

		eval("\$managercontrols = \"".$templates->get('prostore_manager_controls')."\";");
	}

	//Get product list...
	$query = $db->simple_select("prostore_products", "*", "`status` = '0'"); //array('limit' => 1)
	if ($db->num_rows($query) > 0) {
		while ($product = $db->fetch_array($query)) {
			if ($product['thumburl']) {
				//If using an external image source...
				if (substr($product['thumburl'], 0, 4) == 'http') {
					$imageurl = $product['thumburl'];
				} else {
					$imageurl = $mybb->settings['bburl'].'/uploads/prostore/'.$product['thumburl'];
				}
			} elseif ($product['imageurl']) {
				//If using an external image source...
				if (substr($product['thumburl'], 0, 4) == 'http') {
					$imageurl = $product['thumburl'];
				} else {
					$imageurl = $mybb->settings['bburl'].'/uploads/prostore/'.$product['thumburl'];
				}
			} else {
				$imageurl = $mybb->settings['bburl'].'/images/prostore/no_product_photo.jpg';
			}

			if ($product['price']) {
				eval("\$price = \"".$templates->get('prostore_product_view_price')."\";");
			} else {
				eval("\$price = \"".$templates->get('prostore_product_view_price_none')."\";");
			}

			eval("\$productlist .= \"".$templates->get('prostore_frontend_productbit')."\";");
		}
	} else {
		eval("\$productlist = \"".$templates->get('prostore_no_products_row')."\";");
	}

	//Get manager list...
	$managerlist = prostore_list_managers();

	//Parse description...
	if ($store['desc']) {
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;

		// Set up the parser options.
		$parser_options = array(
			"allow_html" => 0,
			"allow_mycode" => 1,
			"allow_smilies" => 1,
			"allow_imgcode" => 0,
			"allow_videocode" => 0,
			"filter_badwords" => 0
		);

		$description = $parser->parse_message($store['desc'], $parser_options);
	}

	//Get user controls...
	if ($mybb->user['uid']) {
		eval("\$controls = \"".$templates->get('prostore_user_controls_frontend')."\";");

		eval("\$usercontrols = \"".$templates->get('prostore_user_controls')."\";");
	}

	eval("\$prostore = \"".$templates->get('prostore_frontend')."\";");

	output_page($prostore);
}
?>