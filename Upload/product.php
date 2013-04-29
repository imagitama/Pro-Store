<?php
/**
 * Pro Store 0.9
 * An advanced eCommerce store that integrates with your forum.
 *  
 * Page: Product management.
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

//Get the current user's cart...
$mycart = prostore_cart_getinfo();

//Get the product info...
$product = prostore_product_getinfo($mybb->input['pid']);


add_breadcrumb($store['name'], PROSTORE_URL_STORE);


//If not in allowed usergroup don't allow them...
if ($store['frontstatus'] == 0 && !$ismanager) {
	error($lang->frontend_closed.' '.$mybb->settings['prostore_closed_reason']);
}


if (!$product && $mybb->input['action'] != 'do_new' && $mybb->input['action'] != 'new') {
	redirect(PROSTORE_URL_STORE, $lang->error_invalid_product);
}


//*******************************************************[ DO NEW ]
if ($mybb->input['action'] == 'do_new') {
	if ($ismanager) {
		//Call an external function to deal with validation...
		$newproduct = prostore_validate_product_input();

		if ($newproduct['code'] && $newproduct['name']) {
			add_breadcrumb($lang->title_product_new, PROSTORE_URL_PRODUCT);
			
			$insertproduct = array(
				'pid' => '',
				'code' => $db->escape_string($newproduct['code']),
				'name' => $db->escape_string($newproduct['name']),
				'desc' => $db->escape_string($newproduct['desc']),
				'cid' => $db->escape_string($newproduct['category']),
				'imageurl' => $db->escape_string($newproduct['imageurl']),
				'thumburl' => $db->escape_string($newproduct['thumburl']),
				'prodcost' => $db->escape_string($newproduct['prodcost']),
				'postcost' => $db->escape_string($newproduct['postcost']),
				'numleft' => $db->escape_string($newproduct['numleft']),
				'numsold' => $db->escape_string($newproduct['numsold']),

				'dateadded' => time(),
				'dateupdated' => time(),

				'whoadded' => $mybb->user['uid'],
				'whoupdated' => $mybb->user['uid'],

				'status' => '0'
			);
			$lastpid = $db->insert_query("prostore_products", $insertproduct);

			redirect(PROSTORE_URL_PRODUCT.'?pid='.$lastpid,$lang->new_product_success);
		} else {
			$product_errors = inline_error($newproduct['errors']);
			$mybb->input['action'] = 'new';
			
			$product = $mybb->input;
		}
	} else {
		error($lang->error_not_manager);
	}
}
	
//****************************************************[ DO REMOVE ]
if ($mybb->input['action'] == 'do_remove') {
	if ($ismanager) {
		if ($product) {
			$db->query("DELETE FROM ".TABLE_PREFIX."prostore_products WHERE `pid` = '{$product['pid']}'");

			redirect(PROSTORE_URL_STORE,$lang->remove_product_success);
		} else {
			error($lang->error_invalid_product);
		}
	} else {
		error($lang->error_not_manager);
	}
}
	
//******************************************************[ DO EDIT ]
if ($mybb->input['action'] == 'do_edit') {
	if ($ismanager) {
		if ($product) {
			add_breadcrumb($lang->title_product_edit, PROSTORE_URL_PRODUCT);
			
			//Call an external function to deal with validation...
			$editproduct = prostore_validate_product_input();

			if ($editproduct['code'] && $editproduct['name']) {
				$db->query("
					UPDATE ".TABLE_PREFIX."prostore_products SET
						`code` = '".$db->escape_string($editproduct['code'])."', 
						`name` = '".$db->escape_string($editproduct['name'])."', 
						`desc` = '".$db->escape_string($editproduct['desc'])."', 
						`cid` = '".$db->escape_string($editproduct['category'])."', 
						`imageurl` = '".$db->escape_string($editproduct['imageurl'])."', 
						`thumburl` = '".$db->escape_string($editproduct['thumburl'])."', 
						`prodcost` = '".$db->escape_string($editproduct['prodcost'])."', 
						`postcost` = '".$db->escape_string($editproduct['postcost'])."', 
						`numleft` = '".$db->escape_string($editproduct['numleft'])."', 
						`numsold` = '".$db->escape_string($editproduct['numsold'])."',

						`dateupdated` = '".time()."',
						`whoupdated` = '".$mybb->user['uid']."'
					WHERE `pid` = '".$db->escape_string($product['pid'])."'
				");
				
				redirect(PROSTORE_URL_PRODUCT.'?pid='.$product['pid'],$lang->edit_product_success);
			} else {
				$product_errors = inline_error($editproduct['errors']);
				$mybb->input['action'] = 'edit';
				
				$product = $mybb->input;
			}
		} else {
			error($lang->error_invalid_product);
		}
	} else {
		error($lang->error_not_manager);
	}
}
	
//**********************************************************[ NEW ]
if ($mybb->input['action'] == 'new') {
	if ($ismanager) {
		add_breadcrumb($lang->title_product_new, PROSTORE_URL_PRODUCT);
		
		if ($product_errors) {
			$form_errors = $product_errors;
			
			eval("\$form_error = \"".$templates->get('prostore_form_errors')."\";");
		}
		
		$categoryoptions = prostore_generate_dropdown('category');
		
		eval("\$prostore = \"".$templates->get('prostore_product_new')."\";");
		
		output_page($prostore);
	} else {
		error($lang->error_not_manager);
	}
}
	
//*******************************************************[ REMOVE ]
if ($mybb->input['action'] == 'remove') {
	if ($ismanager) {
		if ($product) {
			add_breadcrumb($lang->title_product_remove, PROSTORE_URL_PRODUCT);
			
			eval("\$prostore = \"".$templates->get('prostore_product_remove')."\";");
			
			output_page($prostore);
		} else {
			error($lang->error_invalid_product);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//*********************************************************[ EDIT ]
if ($mybb->input['action'] == 'edit') {
	if ($ismanager) {
		if ($product) {
			add_breadcrumb($lang->title_product_edit, PROSTORE_URL_PRODUCT);
			
			if ($product_errors) {
				$form_errors = $product_errors;

				eval("\$form_error = \"".$templates->get('prostore_form_errors')."\";");
			}
			
			$categoryoptions = prostore_generate_dropdown('category', $product['cid']);
		
			eval("\$prostore = \"".$templates->get('prostore_product_edit')."\";");
			
			output_page($prostore);
		} else {
			error($lang->error_invalid_product);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//*********************************************************[ VIEW ]
if ($mybb->input['action'] == 'view' || (intval($mybb->input['pid']) && $mybb->request_method == 'get')) {
	if ($product) {
		add_breadcrumb($lang->title_product_view, PROSTORE_URL_PRODUCT);
		
		//Display store closed reminder...
		if ($mybb->settings['prostore_enable_frontend'] == 0) {
			$closednotice = $lang->frontend_closed_manager;
			
			eval("\$closednotice = \"".$templates->get('prostore_frontend_closed_warning')."\";");
		}
		
		$category = prostore_category_getinfo($product['cid']);

		if ($product['imageurl']) {
			//If using an external image source...
			if (substr($product['imageurl'], 0, 4) == 'http') {
				$imageurl = $product['imageurl'];
			} else {
				$imageurl = $mybb->settings['bburl'].'/uploads/prostore/'.$product['imageurl'];
			}
		} else {
			$imageurl = $mybb->settings['bburl'].'/images/prostore/no_product_photo.jpg';
		}
		
		//Reduce the stock levels just for the user so he doesn't think it's infinite...
		if ($mycart) {
			$cartpids = explode(',', $mycart['pids']);
			foreach ($cartpids as $pid) {
				if ($pid == $product['pid'])	$product['numleft']--;
			}
		}
		
		if ($product['numleft'] > 0) {
			eval("\$numleft = \"".$templates->get('prostore_product_view_numleft')."\";");
		} else {
			eval("\$numleft = \"".$templates->get('prostore_product_view_numleft_none')."\";");
		}
		
		if ($product['prodcost'] || $product['prodcost'] == 0) {
			eval("\$prodcost = \"".$templates->get('prostore_product_view_prodcost')."\";");
		} elseif ($product['prodcost'] == 0) {
			eval("\$prodcost = \"".$templates->get('prostore_product_view_prodcost_none')."\";");
		} elseif ($category['prodcost']) {
			$product['prodcost'] = $category['prodcost'];
			eval("\$prodcost = \"".$templates->get('prostore_product_view_prodcost')."\";");
		} else {
			eval("\$prodcost = \"".$templates->get('prostore_product_view_prodcost_none')."\";");
		}
		
		if ($product['postcost']) {
			eval("\$postcost = \"".$templates->get('prostore_product_view_postcost')."\";");
		} elseif ($product['postcost'] == 0) {
			eval("\$postcost = \"".$templates->get('prostore_product_view_postcost_none')."\";");
		} elseif ($category['postcost']) {
			$product['postcost'] = $category['postcost'];
			eval("\$postcost = \"".$templates->get('prostore_product_view_postcost')."\";");
		} else {
			eval("\$postcost = \"".$templates->get('prostore_product_view_postcost_none')."\";");
		}
		
		if ($mybb->user['uid'] && $product['numleft'] > 0) {
			eval("\$addcart = \"".$templates->get('prostore_user_controls_add_cart')."\";");
		}
		
		if ($product['desc']) {
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
			
			$description = $parser->parse_message($product['desc'], $parser_options);
		}
		
		//Get user controls...
		if ($mybb->user['uid']) {
			eval("\$controls = \"".$templates->get('prostore_user_controls_frontend')."\";");
			
			eval("\$usercontrols = \"".$templates->get('prostore_user_controls')."\";");
		}
		
		//Get manager controls...
		if ($ismanager) {
			eval("\$controls = \"".$templates->get('prostore_manager_controls_product_view')."\";");
			
			eval("\$managercontrols = \"".$templates->get('prostore_manager_controls')."\";");
		}
		
		eval("\$prostore = \"".$templates->get('prostore_product_view')."\";");
		
		output_page($prostore);
	} else {
		error($lang->error_invalid_product);
	}
}

//**********************************************************[ BUY ]
//if ($mybb->input['action'] == 'goodpurchase') {
//	if ($mybb->input['custom'] == '') {
//		add_breadcrumb($lang->title_product_buy_success, PROSTORE_URL_STORE);
//
//		eval("\$prostore = \"".$templates->get('prostore_product_buy_success')."\";");
//
//		output_page($prostore);
//	} else {
//		error($lang->error_invalid_product);
//	}
//}
?>