<?php
/**
 * Pro Store 1.1
 * An advanced eCommerce store that integrates with your forum.
 * 
 * Page: Transaction management.
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

//Get the transaction info...
$trans = prostore_trans_getinfo($mybb->input['tid']);


add_breadcrumb($store['name'], PROSTORE_URL_STORE);


//If not in allowed usergroup don't allow them...
if ($store['frontstatus'] == 0 && !$ismanager) {
	error($lang->frontend_closed.' '.$mybb->settings['prostore_closed_reason']);
}


if (!$trans && $mybb->input['action'] != 'do_new' && $mybb->input['action'] != 'new') {
	redirect(PROSTORE_URL_STORE, $lang->error_invalid_trans);
}


//*******************************************************[ DO NEW ]
if ($mybb->input['action'] == 'do_new') {
	//Call an external function to deal with validation...
	$newtrans = prostore_validate_trans_input();

	if (count($newtrans['errors']) == 0) {
		add_breadcrumb($lang->title_trans_trans, PROSTORE_URL_TRANS);

		$inserttrans = array(
			'tid' => '',
			'uid' => $db->escape_string($newtrans['uid']),
			'pids' => $db->escape_string($newtrans['pids']),
			'paid_prodcost' => $db->escape_string($newtrans['paid_prodcost']),
			'paid_postcost' => $db->escape_string($newtrans['paid_postcost']),

			'dateadded' => time(),
			'dateupdated' => time(),

			'whoadded' => $mybb->user['uid'],
			'whoupdated' => $mybb->user['uid'],

			'payment_status' => $db->escape_string($newtrans['payment_status']),
			'delivery_status' => $db->escape_string($newtrans['delivery_status'])
		);
		$lasttid = $db->insert_query("prostore_transactions", $inserttrans);

		redirect(PROSTORE_URL_TRANS.'?tid='.$lasttid,$lang->new_trans_success);
	} else {
		if ($ismanager) {
			$trans_errors = inline_error($newtrans['errors']);
			$mybb->input['action'] = 'new';

			$trans = $mybb->input;
		} else {
			error($lang->error_invalid_trans);
		}
	}
}
	
//****************************************************[ DO REMOVE ]
if ($mybb->input['action'] == 'do_remove') {
	if ($ismanager) {
		if ($trans) {
			$db->query("DELETE FROM ".TABLE_PREFIX."prostore_transactions WHERE `tid` = '{$trans['tid']}'");

			redirect(PROSTORE_URL_STORE.'?action=backend',$lang->remove_trans_success);
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}
	
//******************************************************[ DO EDIT ]
if ($mybb->input['action'] == 'do_edit') {
	if ($ismanager) {
		if ($trans) {
			add_breadcrumb($lang->title_trans_edit, PROSTORE_URL_TRANS);
			
			//Call an external function to deal with validation...
			$edittrans = prostore_validate_trans_input();

			if ($edittrans['errors'] == 0) {
				$db->query("
					UPDATE ".TABLE_PREFIX."prostore_transactions SET
						`pids` = '".$db->escape_string($edittrans['pids'])."', 
						`uid` = '".$db->escape_string($edittrans['uid'])."',
							
						`paid_prodcost` = '".$db->escape_string($edittrans['paid_prodcost'])."', 
						`paid_postcost` = '".$db->escape_string($edittrans['paid_postcost'])."', 
							
						`postal_details` = '".$db->escape_string($edittrans['postal_details'])."',
						`paypal_email` = '".$db->escape_string($edittrans['paypal_email'])."',
							
						`dateupdated` = '".time()."',
						`whoadded` = '".$db->escape_string($edittrans['whoadded'])."', 
						`whoupdated` = '".$mybb->user['uid']."',
							
						`payment_status` = '".$db->escape_string($edittrans['payment_status'])."',
						`delivery_status` = '".$db->escape_string($edittrans['delivery_status'])."'
					WHERE `tid` = '".$db->escape_string($trans['tid'])."'
				");
				
				redirect(PROSTORE_URL_TRANS.'?tid='.$trans['tid'], $lang->edit_trans_success);
			} else {
				$trans_errors = inline_error($edittrans['errors']);
				$mybb->input['action'] = 'edit';
				
				$trans = $mybb->input;
			}
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//*******************************************************[ DO ADD ]
if ($mybb->input['action'] == 'do_addprod') {
	if ($ismanager) {
		if ($trans) {
			add_breadcrumb($lang->title_trans_addprod, PROSTORE_URL_TRANS);
			
			if (intval($mybb->input['pid'])) {
				$db->query("
					UPDATE ".TABLE_PREFIX."prostore_transactions SET
						`pids` = CONCAT(`pids`, ',".$db->escape_string($mybb->input['pid'])."')
					WHERE `tid` = '".$db->escape_string($trans['tid'])."'
				");
				
				redirect(PROSTORE_URL_TRANS.'?tid='.$trans['tid'], $lang->addprod_trans_success);
			} else {
				error($lang->error_invalid_product);
			}
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//***********************************************[ DO REMOVE PROD ]
if ($mybb->input['action'] == 'do_removeprod') {
	if ($ismanager) {
		if ($trans) {
			add_breadcrumb($lang->title_trans_removeprod, PROSTORE_URL_TRANS);
			
			if (intval($mybb->input['pid'])) {
				$pids = explode(',', $trans['pids']);
				
				foreach ($pids as $pid) {
					if ($mybb->input['pid'] == $pid && $found != true) {
						$found = true;
					} else {
						$newpids[] = $pid;
					}
				}
				$newpids = implode(',', $newpids);
				
				$db->query("
					UPDATE ".TABLE_PREFIX."prostore_transactions SET
						`pids` = '".$db->escape_string($newpids)."'
					WHERE `tid` = '".$db->escape_string($trans['tid'])."'
				");
				
				redirect(PROSTORE_URL_TRANS.'?tid='.$trans['tid'], $lang->addprod_trans_success);
			} else {
				error($lang->error_invalid_product);
			}
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}
	
//**********************************************************[ NEW ]
if ($mybb->input['action'] == 'new') {
	if ($ismanager) {
		add_breadcrumb($lang->title_trans_new, PROSTORE_URL_TRANS);
		
		if ($trans_errors) {
			$form_errors = $trans_errors;
			
			eval("\$form_error = \"".$templates->get('prostore_form_errors')."\";");
		}
		
		//Generate dropdowns...
		$payment_status = prostore_generate_dropdown('payment_status');
		$delivery_status = prostore_generate_dropdown('delivery_status');
		
		eval("\$prostore = \"".$templates->get('prostore_trans_new')."\";");
		
		output_page($prostore);
	} else {
		error($lang->error_not_manager);
	}
}

//******************************************************[ DO EDIT ]
if ($mybb->input['action'] == 'delivery_update') {
	if ($trans['uid'] == $mybb->user['uid']) {
		if ($trans) {
			if ($mybb->input['delivery_update'] == 2 || $mybb->input['delivery_update'] == 3) {
				$db->query("
					UPDATE ".TABLE_PREFIX."prostore_transactions SET
						`delivery_status` = '".$db->escape_string($mybb->input['delivery_update'])."'
					WHERE `tid` = '".$db->escape_string($trans['tid'])."'
				");
				
				redirect(PROSTORE_URL_TRANS.'?tid='.$trans['tid'], $lang->msg_trans_delivery_update_success);
			} else {
				error($lang->error_invalid_trans);
			}
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}
	
//*******************************************************[ REMOVE ]
if ($mybb->input['action'] == 'remove') {
	if ($ismanager) {
		if ($trans) {
			add_breadcrumb($lang->title_trans_trans, PROSTORE_URL_TRANS);
			
			eval("\$prostore = \"".$templates->get('prostore_trans_remove')."\";");
			
			output_page($prostore);
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//*********************************************************[ EDIT ]
if ($mybb->input['action'] == 'edit') {
	if ($ismanager) {
		if ($trans) {
			add_breadcrumb($lang->title_trans_edit, PROSTORE_URL_TRANS);
			
			if ($trans_errors) {
				$form_errors = $trans_errors;

				eval("\$form_error = \"".$templates->get('prostore_form_errors')."\";");
			}
			
			//Generate dropdowns...
			$payment_status = prostore_generate_dropdown('payment_status', $trans['payment_status']);
			$delivery_status = prostore_generate_dropdown('delivery_status', $trans['delivery_status']);
		
			eval("\$prostore = \"".$templates->get('prostore_trans_edit')."\";");
			
			output_page($prostore);
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}

//*********************************************************[ VIEW ]
if ($mybb->input['action'] == 'view' || (intval($mybb->input['tid']) && $mybb->request_method == 'get')) {
	if ($trans['uid'] == $mybb->user['uid'] || $ismanager) {
		if ($trans) {
			add_breadcrumb($lang->title_trans_view, PROSTORE_URL_trans);

			//Display store closed reminder...
			if ($mybb->settings['prostore_enable_frontend'] == 0) {
				$closednotice = $lang->frontend_closed_manager;

				eval("\$closednotice = \"".$templates->get('prostore_frontend_closed_warning')."\";");
			}

			//Get the products in the transaction...
			if ($trans['pids']) {
				$pidarray = explode(',', $trans['pids']);
				$numofeach = array_count_values($pidarray);
				$totalquantity = count($pidarray);
				
				//Get all product details...
				$query = $db->simple_select("prostore_products","*","`pid` IN ({$trans['pids']})");
				
				if ($db->num_rows($query) > 0) {
					//Add them to the product list...
					while ($product = $db->fetch_array($query)) {
						if ($ismanager) {
							eval("\$managercontrols = \"".$templates->get('prostore_manager_controls_trans_view_productbit')."\";");
						}

						if (!$product['prodcost'] && $product['prodcost'] != 0 && $category['prodcost']) {
							$product['prodcost'] = $category['prodcost'];
						}

						if (!$product['postcost'] && $product['postcost'] != 0  && $category['postcost']) {
							$product['postcost'] = $category['postcost'];
						}

						$prodquantity = $numofeach[$product['pid']];

						$total_prodcost += $product['prodcost'] * $prodquantity;
						$total_postcost += $product['postcost'];

						eval("\$productlist .= \"".$templates->get('prostore_trans_view_productbit')."\";");
					}
				} else {
					$productlist = '';
				}
			} else {
				$eval("\$productlist .= \"".$templates->get('prostore_no_products_row')."\";");
			}
			
			//Format the totals...
			$total_prodcost = number_format($total_prodcost, 2, '.', ',');
			$total_postcost = number_format($total_postcost, 2, '.', ',');
				
			//Get user who did the transaction...
			$query = $db->simple_select("users","*","`uid` = '{$trans['uid']}'");
			$user = $db->fetch_array($query);
			$userfor = "<a href=\"".get_profile_link($user['uid'])."\">".htmlspecialchars_uni($user['username'])."</a>";
			
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
			
			//If can update the delivery status (managers shouldn't see)...
			if ($trans['delivery_status'] > 0 && $trans['uid'] == $mybb->user['uid']) {
				$dropdown = prostore_generate_dropdown('delivery_status_customer', $trans['delivery_status']);
				
				eval("\$delivery_dropdown = \"".$templates->get('prostore_trans_view_delivery_update')."\";");
				
				//Get user controls...
				if ($trans['uid'] == $mybb->user['uid']) {
					eval("\$controls = \"".$templates->get('prostore_user_controls_trans_view')."\";");

					eval("\$usercontrols = \"".$templates->get('prostore_user_controls')."\";");
				}
			}
			
			//PayPal email...
//				if ($trans['paypal_email']) {
//					eval("\$paypal = \"".$templates->get('prostore_trans_view_paypal_email')."\";");
//				}
			
			//Format paid...
			$paid_prodcost = number_format(floatval($trans['paid_prodcost']), 2, '.', ',');
			$paid_postcost = number_format(floatval($trans['paid_postcost']), 2, '.', ',');
			
			//Format postal details...	
			if ($trans['postal_details']) {
				$postal_details = nl2br($trans['postal_details']);
			}
			
			//Get manager controls...
			if ($ismanager) {
				eval("\$controls = \"".$templates->get('prostore_manager_controls_trans_view')."\";");

				eval("\$managercontrols = \"".$templates->get('prostore_manager_controls')."\";");
			}

			eval("\$prostore = \"".$templates->get('prostore_trans_view')."\";");

			output_page($prostore);
		} else {
			error($lang->error_invalid_trans);
		}
	} else {
		error($lang->error_not_manager);
	}
}
?>