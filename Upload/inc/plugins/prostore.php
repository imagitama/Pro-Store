<?php
/**
 * Pro Store 0.9
 * An advanced eCommerce store that integrates with your forum.
 *  
 * By Jared Williams
 * Copyright 2012
 * 
 * Website: http://www.jazzza001.com
 *  
 * Please do not redistribute or sell this plugin.
 */

//Disallow direct access to this file for security reasons...
if(!defined("IN_MYBB")) {
	die("This file cannot be accessed directly.");
}


//Tell MyBB when to run our functions...
$plugins->add_hook("member_do_register_end", 	"prostore_register_add_cart");


//FUNCTION: Plugin info
function prostore_info() {
	return array(
		"name"						=> "Pro Store",
		"description"			=> "An advanced eCommerce store that integrates with your forum.",
		"author"					=> "Jazza",
		"authorsite"			=> "http://www.jazzza001.com/",
		"version"					=> "1.1",
		"compatibility"		=> "6"
	);
}


//FUNCTION: Is it installed
function prostore_is_installed() {
	global $mybb, $db;
	
	//TODO: Use wildcard!
	$tables = array(
		'prostore_products',
		'prostore_categories',
		'prostore_transactions',
		'prostore_carts'
	);

	//Loop through all tables and if one exists, it is installed...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			return true;
		}
	}
	
	return false;
}


//FUNCTION: Perform a clean install.
function prostore_install() {
	global $mybb, $db;
	
	//Insert database stuff...
	prostore_insert_database();
	
	//Insert all templates...
	prostore_insert_templates();
	
	//Insert all settings...
	prostore_insert_settings();
}


//FUNCTION: Inserts database tables and default rows.
function prostore_insert_database() {
	global $mybb, $db;
	
	$collation = $db->build_create_table_collation();
	
	//Create products table if it does not exist...
	if(!$db->table_exists('prostore_products')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."prostore_products` (
			`pid` int(10) NOT NULL AUTO_INCREMENT,
			`code` varchar(256) NOT NULL default '',
			`name` varchar(256) NOT NULL default '',
			`desc` varchar(1024) NOT NULL default '',
			`cid` int(10) NOT NULL default 1,
			`imageurl` varchar(1024) NOT NULL default '',
			`thumburl` varchar(1024) NOT NULL default '',
			`prodcost` varchar(256) NOT NULL default '',
			`postcost` varchar(256) NOT NULL default '',
			`numleft` INT NOT NULL default 0,
			`numsold` INT NOT NULL default 0,
			`dateadded` varchar(256) NOT NULL default 0,
			`dateupdated` varchar(256) NOT NULL default 0,
			`whoadded` INT NOT NULL default 0,
			`whoupdated` INT NOT NULL default 0,
			`status` INT NOT NULL default 1, 
			PRIMARY KEY  (`pid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//Insert a demo product...
	//TODO: Use $mybb->insert_query()!
	$db->query("
		INSERT INTO `".TABLE_PREFIX."prostore_products`
		VALUES(
			'',
			'".$db->escape_string('52A29')."',
			'".$db->escape_string('Demo Product')."',
			'".$db->escape_string('This is a product to demonstrate the store.')."',
			'".$db->escape_string('1')."',
			'".$db->escape_string('1_full.jpg')."',
			'".$db->escape_string('1_thumb.jpg')."',
			'".$db->escape_string('5.99')."',
			'".$db->escape_string('10.00')."',
			'".$db->escape_string('10')."',
			'".$db->escape_string('0')."',
			'".time()."',
			'".time()."',
			'".$mybb->user['uid']."',
			'".$mybb->user['uid']."',
			'0'
		)
	");
	
	//Create category table if it does not exist...
	if(!$db->table_exists('prostore_categories')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."prostore_categories` (
			`cid` int(10) NOT NULL AUTO_INCREMENT,
			`name` varchar(256) NOT NULL default '',
			`desc` varchar(1024) NOT NULL default '',
			`managergroups` varchar(1024) NOT NULL default '',
			`prodcost` varchar(256) NOT NULL default '',
			`postcost` varchar(256) NOT NULL default '',
			`dateadded` varchar(256) NOT NULL default 0,
			`dateupdated` varchar(256) NOT NULL default 0,
			`whoadded` INT NOT NULL default 0,
			`whoupdated` INT NOT NULL default 0,
			`status` INT NOT NULL default 1, 
			PRIMARY KEY  (`cid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//Insert a default category...
	//TODO: Use $mybb->insert_query()!
	$db->query("
		INSERT INTO ".TABLE_PREFIX."prostore_categories
		VALUES(
			'',
			'".$db->escape_string('Default')."',
			'".$db->escape_string('Default category.')."',
			'".$db->escape_string('')."',
			'".$db->escape_string('4.99')."',
			'".$db->escape_string('9.99')."',
			'".time()."',
			'".time()."',
			'".$mybb->user['uid']."',
			'".$mybb->user['uid']."',
			'0'
		)
	");
	
	//Create transactions table if it does not exist...
	if(!$db->table_exists('prostore_transactions')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."prostore_transactions` (
			`tid` int(10) NOT NULL AUTO_INCREMENT,
			`uid` int(10) NOT NULL default 0,
			`pids` varchar(1024) NOT NULL default '',
			`paid_prodcost` varchar(256) NOT NULL default 0,
			`paid_postcost` varchar(256) NOT NULL default 0,
			`postal_details` varchar(1024) NOT NULL default 0,
			`paypal_email` varchar (1024) NOT NULL default 0,
			`dateadded` varchar(256) NOT NULL default 0,
			`dateupdated` varchar(256) NOT NULL default 0,
			`whoadded` INT NOT NULL default 0,
			`whoupdated` INT NOT NULL default 0,
			`payment_status` INT NOT NULL default 0,
			`delivery_status` INT NOT NULL default 0,
			PRIMARY KEY (`tid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//Insert a demo transaction...
	//TODO: Use $mybb->insert_query()!
	$inserttrans = array(
		'tid' => '',
		'uid' => $mybb->user['uid'],
		'pids' => '1',
		'paid_prodcost' => '5.99',
		'paid_postcost' => '10.00',
		'postal_details' => 'Joe Bloggs
123 Mary Lane
Sydney NSW 2000
Australia',
		'paypal_email' => 'joebloggs@url.com.au',
		'dateadded' => time(),
		'dateupdated' => time(),
		'whoadded' => $mybb->user['uid'],
		'whoupdated' => $mybb->user['uid'],
		'payment_status' => '1',
		'delivery_status' => '2'
	);
	$last_transid = $db->insert_query("prostore_transactions", $inserttrans);
	
	//Create cart table if it does not exist...
	if(!$db->table_exists('prostore_carts')) {
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."prostore_carts` (
			`cid` int(10) NOT NULL AUTO_INCREMENT,
			`uid` int(10) NOT NULL default 0,
			`pids` varchar(1024) NOT NULL default '',
			`dateadded` varchar(256) NOT NULL default 0,
			`dateupdated` varchar(256) NOT NULL default 0,
			`whoadded` INT NOT NULL default 0,
			`whoupdated` INT NOT NULL default 0,
			`status` INT NOT NULL default 1, 
			PRIMARY KEY  (`cid`)
		) ENGINE=MyISAM{$collation}");
	}
	
	//Insert a cart for each user...
	$query = $db->simple_select("users", "uid");
	while ($user = $db->fetch_array($query)) {
		//Insert a cart...
		$insertcart = array(
			'cid' => '',
			'uid' => $user['uid'],
			'pids' => '',
			'dateadded' => time(),
			'dateupdated' => time(),
			'whoadded' => $user['uid'],
			'whoupdated' => $user['uid'],
			'status' => '1'
		);
		$last_cartid = $db->insert_query("prostore_carts", $insertcart);
	}
}
	
	
//FUNCTION: Insert templates.
function prostore_insert_templates($Doadd=array(), $Donotadd=array()) {
	global $mybb, $db;
	
	//Add templates...
	$templates = array(
		//TEMPLATE: Frontend
		'prostore_frontend'					=> '
<html>
	<head>
		<title>{$store[\'name\']}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<table width="100%" class="tborder">
			<tr>
				<td width="100%" colspan="10" class="trow1" style="padding-left: 20px">
					<h1>{$store[\'name\']}</h1>
					<h3>{$description}</h3>
					<h4>Managed by: {$managerlist}</h4>
				</td>
			</tr>
		</table>
		<br />
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_frontend}</td>
			</tr>
			<tr>
				<td width="20%"></td><td width="20%"></td><td width="20%"></td><td width="20%"></td><td width="20%"></td>
			</tr>
			{$productlist}
		</table>
		{$usercontrols}
		{$managercontrols}

		{$footer}
	</body>
</html>',
			
		'prostore_frontend_productbit'					=> '
<td id="{$product[\'pid\']}" width="20%" class="trow1" align="center">
	<a href="product.php?pid={$product[\'pid\']}">
		<img src="{$imageurl}" width="100%" alt="Product image" />
	</a><br />
	<a href="product.php?pid={$product[\'pid\']}">{$product[\'name\']}</a> {$prodcost}
</td>',

		'prostore_trans_viewown'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_trans_viewown}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_trans_viewown}</td>
			</tr>
			<tr>
				<td width="50%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_prodlist}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_prodcost}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_postcost}</strong></td>
				<td width="5%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_payment_status}</strong></td>
				<td width="5%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_delivery_status}</strong></td>
			</tr>
			{$translist}
		</table>
		{$usercontrols}
		{$managercontrols}

		{$footer}
	</body>
</html>',
			
		'prostore_trans_viewown_transbit'					=> '
<tr>
	<td class="trow1" width="50%"><a href="transaction.php?tid={$trans[\'tid\']}">{$productnum} products</a></td>
	<td class="trow1" width="20%">&#36;{$paid_prodcost} products</td>
	<td class="trow1" width="20%">&#36;{$paid_postcost} postage</td>
	
	<td class="trow1" width="5%">{$payment_status}</td>
	<td class="trow1" width="5%">{$delivery_status}</td>
</tr>',
			
		'prostore_terms'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_terms}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		
		<h1>{$lang->title_tc}</h1>
		<p>
			{$lang->body_tc}
		</p>
		
		{$managercontrols}

		{$footer}
	</body>
</html>',
			
		'prostore_help'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_help}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		
		<h1>{$lang->title_help}</h1>
		<p>
			{$lang->body_help}
		</p>
		
		{$managercontrols}

		{$footer}
	</body>
</html>',

		//TEMPLATE: Backend
		'prostore_backend'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_backend}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_backend_products}</td>
			</tr>
			<tr>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_name}</strong></td>
				<td width="5%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_prodcost}</strong></td>
				<td width="5%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_postcost}</strong></td>
				<td width="5%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_numleft}</strong></td>
				<td width="5%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_numsold}</strong></td>
				<td width="10%" colspan="2" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_controls}</strong></td>
			</tr>
			{$productlist}
		</table>
		<br />
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_backend_trans}</td>
			</tr>
			<tr>
				<td width="30%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_userfor}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_prodlist}</strong></td>
				<td width="10%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_prodcost}</strong></td>
				<td width="10%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_postcost}</strong></td>
				<td width="10%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_payment_status}</strong></td>
				<td width="10%" class="trow1" style="text-align: center"><strong>{$lang->label_translist_delivery_status}</strong></td>
				<td width="10%" colspan="2" class="trow1" style="text-align: center"><strong>{$lang->label_translist_controls}</strong></td>
			</tr>
			{$translist}
		</table>
		<br />
		<form action="store.php" method="post">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_backend_settings}</td>
			</tr>
			<tr>
				<td width="20%" class="trow1">
					{$lang->label_closed_reason}
				</td>
				<td width="80%" class="trow1">
					<textarea name="closed_reason" cols="50" rows="5" class="textbox">{$closed_reason}</textarea>
				</td>
			</tr>
		</table>
		<br />

			<input type="hidden" name="action" value="update" />
			<div style="text-align: center">
				<input type="submit" value="{$lang->button_update}" />
			</div>
		</form>
		{$managercontrols}

		{$footer}
	</body>
</html>',
			
		'prostore_backend_productbit'					=> '
<tr>
	<td class="trow1" width="20%"><a href="product.php?pid={$product[\'pid\']}">{$product[\'name\']}</a></td>
	<td class="trow1" width="5%">&#36;{$product[\'prodcost\']}</td>
	<td class="trow1" width="5%">&#36;{$product[\'postcost\']}</td>
	<td class="trow1" width="5%" style="text-align: center">{$numleft}</td>
	<td class="trow1" width="5%" style="text-align: center">{$product[\'numsold\']}</td>

	<td width="5%" class="trow1">
		<form action="product.php" method="post">
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="pid" value="{$product[\'pid\']}" />

			<input type="submit" value="{$lang->button_edit}" />
		</form>
	</td>
	<td width="5%" class="trow1">
		<form action="product.php" method="post">
			<input type="hidden" name="action" value="remove" />
			<input type="hidden" name="pid" value="{$product[\'pid\']}" />

			<input type="submit" value="{$lang->button_remove}" />
		</form>
	</td>	
</tr>',
			
		'prostore_backend_transbit'					=> '
<tr>
	<td class="trow1" width="30%">{$userfor}</td>
	<td class="trow1" width="20%"><a href="transaction.php?tid={$trans[\'tid\']}">{$productnum} products</a></td>
	<td class="trow1" width="10%">&#36;{$paid_prodcost}</td>
	<td class="trow1" width="10%">&#36;{$paid_postcost}</td>
	<td class="trow1" width="10%">{$payment_status}</td>
	<td class="trow1" width="10%">{$delivery_status}</td>

	<td width="5%" class="trow1">
		<form action="transaction.php" method="post">
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="tid" value="{$trans[\'tid\']}" />

			<input type="submit" value="{$lang->button_edit}" />
		</form>
	</td>
	<td width="5%" class="trow1">
		<form action="transaction.php" method="post">
			<input type="hidden" name="action" value="remove" />
			<input type="hidden" name="tid" value="{$trans[\'tid\']}" />

			<input type="submit" value="{$lang->button_remove}" />
		</form>
	</td>
</tr>',
			
		'prostore_backend_num_none'							=> '<span style="color: #D40000">{$product[\'numleft\']}</span>',
		'prostore_backend_num_verylow'					=> '<span style="color: #D40000">{$product[\'numleft\']}</span>',
		'prostore_backend_num_low'							=> '<span style="color: #858500">{$product[\'numleft\']}</span>',
		'prostore_backend_num_normal'						=> '<span style="color: #008C00">{$product[\'numleft\']}</span>',

		//TEMPLATE: Product
		//https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_html_Appx_websitestandard_htmlvariables
		'prostore_product_view'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$product[\'name\']}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_product_view}</td>
			</tr>
			<tr>
				<td width="25%" class="trow1" rowspan="10">
					<img src="{$imageurl}" width="100%" alt="Product image" />
				</td>
				<td width="75%" class="trow1">
					<h2>{$product[\'name\']}</h2>
					<h3>{$product[\'type\']}</h3>
					{$description}
				</td>
			</tr>
			<tr>
				<td width="75%" class="trow1">
					{$prodcost}{$postcost}
					<br />
					{$numleft}
					<br />
					{$addcart}
				</td>
			</tr>
		</table>
		{$usercontrols}
		{$managercontrols}
		{$footer}
	</body>
</html>',

		'prostore_product_edit'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_product_edit}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<form action="product.php" method="post" name="editproduct">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_product_edit}</td>
			</tr>
			{$form_errors}
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_code}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="code" size="40" maxlength="256" value="{$product[\'code\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_name}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="name" size="40" maxlength="256" value="{$product[\'name\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_category}</strong></td>
				<td width="80%" class="trow1"><select class="textbox" name="category">{$categoryoptions}</select></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_desc}</strong></td>
				<td width="80%" class="trow1"><textarea name="desc" rows="5" cols="36" class="textbox">{$product[\'desc\']}</textarea></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_prodcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="prodcost" size="40" maxlength="256" value="{$product[\'prodcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_postcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="postcost" size="40" maxlength="256" value="{$product[\'postcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_numleft}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="numleft" size="40" maxlength="256" value="{$product[\'numleft\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_numsold}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="numsold" size="40" maxlength="256" value="{$product[\'numsold\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_imageurl}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="imageurl" size="40" maxlength="256" value="{$product[\'imageurl\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_thumburl}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="thumburl" size="40" maxlength="256" value="{$product[\'thumburl\']}" /></td>
			</tr>
		</table>
		<br />
		<div style="text-align: center"><input type="submit" class="button" name="submit" value="{$lang->button_edit}" accesskey="s" /></div>
		
			<input type="hidden" name="action" value="do_edit" />
			<input type="hidden" name="pid" value="{$product[\'pid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',
			
				'prostore_product_new'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_product_new}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<form action="product.php" method="post" name="newproduct">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_product_edit}</td>
			</tr>
			{$form_errors}
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_code}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="code" size="40" maxlength="256" value="{$product[\'code\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_name}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="name" size="40" maxlength="256" value="{$product[\'name\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_category}</strong></td>
				<td width="80%" class="trow1"><select class="textbox" name="category">{$categoryoptions}</select></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_desc}</strong></td>
				<td width="80%" class="trow1"><textarea name="desc" rows="5" cols="36" class="textbox">{$product[\'desc\']}</textarea></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_prodcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="prodcost" size="40" maxlength="256" value="{$product[\'prodcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_postcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="postcost" size="40" maxlength="256" value="{$product[\'postcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_numleft}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="numleft" size="40" maxlength="256" value="{$product[\'numleft\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_numsold}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="numsold" size="40" maxlength="256" value="{$product[\'numsold\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_imageurl}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="imageurl" size="40" maxlength="256" value="{$product[\'imageurl\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_product_edit_thumburl}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="thumburl" size="40" maxlength="256" value="{$product[\'thumburl\']}" /></td>
			</tr>
			<tr>
		</table>
		<br />
		<div style="text-align: center"><input type="submit" class="button" name="submit" value="{$lang->button_new}" accesskey="s" /></div>
		
			<input type="hidden" name="action" value="do_new" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',
			
							'prostore_product_remove'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_product_remove}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<form action="product.php" method="post" name="removeproduct">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_product_remove}</td>
			</tr>
			<tr>
				<td class="trow1" align="center">
					<br />
					Are you sure you want to remove product {$product[\'name\']}? This cannot be undone!
					<br /><br />
					<input type="submit" class="button" name="submit" value="{$lang->button_remove}" accesskey="s" />
					<br /><br />
			</td>
			</tr>
		</table>
		<br />

			<input type="hidden" name="action" value="do_remove" />
			<input type="hidden" name="pid" value="{$product[\'pid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',
			
		'prostore_product_view_postcost'				=> '(plus &#36;{$product[\'postcost\']} postage)',
		'prostore_product_view_postcost_none'		=> 'with <span style="color: #D6AF00; font-weight:bold">FREE POSTAGE</span>',
			
		'prostore_product_view_prodcost'				=> '&#36;{$product[\'prodcost\']}',
		'prostore_product_view_prodcost_none'		=> '<span style="color: #D6AF00; font-weight:bold">FREE</span>',
			
		'prostore_product_view_numleft'					=> '{$product[\'numleft\']} in stock',
		'prostore_product_view_numleft_none'		=> '<span style="color: #D40000; font-weight:bold">SOLD OUT</span>',
			
		'prostore_manager_controls_closestore'	=> '<a href="{$store[\'url\']}?action=close">Close store</a>',
		'prostore_manager_controls_openstore'		=> '<a href="{$store[\'url\']}?action=open">Open store</a>',
			
		'prostore_frontend_closed_warning'			=> '<div class="red_alert"><strong><span style="color:#FF0000;">{$lang->frontend_closed_manager}</span></strong></div>',
			
		'prostore_cart_frozen_warning'					=> '<div class="red_alert"><strong><span style="color:#FF0000;">{$lang->cart_frozen_warning}</span></strong></div>',
			
		//TEMPLATE: Transaction
		'prostore_trans_view'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_trans_view}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_trans_view_basicinfo}</td>
			</tr>
			<tr>
				<td class="trow1" width="20%" style="text-align: center">
					<h3>{$userfor}</h3>
				</td>
				<td class="trow1" width="20%" style="text-align: center">
					<strong>{$lang->label_trans_edit_payment_status} {$payment_status}</strong>
				</td>
				<td class="trow1" width="20%" style="text-align: center">
					<strong>{$lang->label_trans_edit_delivery_status} {$delivery_status}</strong>
				</td>
				<td class="trow1" width="20%" style="text-align: center">
					<strong>{$lang->label_trans_edit_paid_prodcost}</strong> &#36;{$paid_prodcost}
					<br />
					<strong>{$lang->label_trans_edit_paid_postcost}</strong> &#36;{$paid_postcost}
				</td>
				<td class="trow1" width="20%">
					{$postal_details}
				</td>
			</tr>
		</table>
		<br />
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_trans_view_productlist}</td>
			</tr>
			<tr>
				<td width="40%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_name}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_quantity}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_prodcost}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_postcost}</strong></td>
			</tr>
			{$productlist}
			<tr>
				<td width="40%" class="trow1" style="text-align: right">
					<strong>{$lang->label_prodlist_total}</strong>
				</td>
				<td width="20%" class="trow1">
					<strong>{$totalquantity}</strong>
				</td>
				<td width="20%" class="trow1">
					<strong>&#36;{$total_prodcost} total</strong>
				</td>
				<td width="20%" class="trow1">
					<strong>&#36;{$total_postcost} postage</strong>
				</td>
			</tr>
		</table>
		{$usercontrols}
		{$managercontrols}

		{$footer}
	</body>
</html>',
			
		//NEW
		'prostore_trans_view_delivery_update' => '
<form action="transaction.php" method="post">
	{$lang->label_trans_edit_delivery_update} 
	<select class="textbox" name="delivery_update">{$dropdown}</select>
	
	<input type="hidden" name="action" value="delivery_update" />
	<input type="hidden" name="tid" value="{$trans[\'tid\']}" />
	<input type="submit" label="{$lang->button_update}" />
</form>',
			
		//OLD
//		'prostore_trans_status_cancelled'		=> '<span style="color: #777">{$lang->label_trans_cancelled}</a>',
//		'prostore_trans_status_unpaid'			=> '<span style="color: #B00000">{$lang->label_trans_unpaid}</a>',
//		'prostore_trans_status_paid'				=> '<span style="color: #009000">{$lang->label_trans_paid}</a>',
			
		//NEW
		'prostore_trans_payment_pending'		=> '<span style="color: #8A7A00">{$lang->label_trans_payment_pending}</a>',
		'prostore_trans_payment_paid'				=> '<span style="color: #009000">{$lang->label_trans_payment_paid}</a>',
		'prostore_trans_payment_failed'			=> '<span style="color: #B00000">{$lang->label_trans_payment_failed}</a>',
		'prostore_trans_payment_refunded'		=> '<span style="color: #000000">{$lang->label_trans_payment_refunded}</a>',
		'prostore_trans_payment_other'			=> '<span style="color: #00F2FF">{$lang->label_trans_payment_other}</a>',
			
		'prostore_trans_delivery_notsent'		=> '<span style="color: #000">{$lang->label_trans_delivery_notsent}</a>',
		'prostore_trans_delivery_sent'			=> '<span style="color: #000">{$lang->label_trans_delivery_sent}</a>',
		'prostore_trans_delivery_arrived'		=> '<span style="color: #009000">{$lang->label_trans_delivery_arrived}</a>',
		'prostore_trans_delivery_notarrived'=> '<span style="text-decoration:underline; color: #B00000">{$lang->label_trans_delivery_notarrived}</a>',
			
		'prostore_trans_view_userbit' => '<a href="{$userlink}">{$user[\'username\']}</a>',
		'prostore_trans_view_productbit' => '
<tr>
	<td width="40%" class="trow1">
		<a href="product.php?pid={$product[\'pid\']}">{$product[\'name\']}</a>
	</td>
	<td width="20%" class="trow1">
		{$prodquantity}
	</td>
	<td width="20%" class="trow1">
		&#36;{$product[\'prodcost\']}
	</td>
	<td width="20%" class="trow1">
		&#36;{$product[\'postcost\']}
	</td>
</tr>',

		'prostore_trans_edit'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_trans_edit}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<form action="transaction.php" method="post" name="edittrans">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_trans_edit}</td>
			</tr>
			{$form_errors}
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_uid}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="uid" size="40" maxlength="256" value="{$trans[\'uid\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_pids}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="pids" size="40" maxlength="256" value="{$trans[\'pids\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_prodcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="paid_prodcost" size="40" maxlength="256" value="{$trans[\'paid_prodcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_postcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="paid_postcost" size="40" maxlength="256" value="{$trans[\'paid_postcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_postal_details}</strong></td>
				<td width="80%" class="trow1"><textarea class="textbox" name="postal_details" rows="4" cols="40">{$trans[\'postal_details\']}</textarea></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_paypal_email}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="paypal_email" size="40" maxlength="1024" value="{$trans[\'paypal_email\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_payment_status}</strong></td>
				<td width="80%" class="trow1"><select class="textbox" name="payment_status">{$payment_status}</select></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_delivery_status}</strong></td>
				<td width="80%" class="trow1"><select class="textbox" name="delivery_status">{$delivery_status}</select></td>
			</tr>
		</table>
		<br />
		<div style="text-align: center"><input type="submit" class="button" name="submit" value="{$lang->button_edit}" accesskey="s" /></div>
		
			<input type="hidden" name="action" value="do_edit" />
			<input type="hidden" name="tid" value="{$trans[\'tid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',

				'prostore_trans_new'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_trans_new}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<form action="transaction.php" method="post" name="newtrans">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_trans_edit}</td>
			</tr>
			{$form_errors}
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_uid}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="uid" size="40" maxlength="256" value="{$trans[\'uid\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_pids}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="pids" size="40" maxlength="256" value="{$trans[\'pids\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_prodcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="paid_prodcost" size="40" maxlength="256" value="{$trans[\'paid_prodcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_postcost}</strong></td>
				<td width="80%" class="trow1">&#36;<input type="text" class="textbox" name="paid_postcost" size="40" maxlength="256" value="{$trans[\'paid_postcost\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_postal_details}</strong></td>
				<td width="80%" class="trow1"><textarea class="textbox" name="postal_details" rows="4" cols="40">{$trans[\'postal_details\']}</textarea></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_paypal_email}</strong></td>
				<td width="80%" class="trow1"><input type="text" class="textbox" name="paypal_email" size="40" maxlength="1024" value="{$trans[\'paypal_email\']}" /></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_payment_status}</strong></td>
				<td width="80%" class="trow1"><select class="textbox" name="payment_status">{$payment_status}</select></td>
			</tr>
			<tr>
				<td width="20%" class="trow1"><strong>{$lang->label_trans_edit_delivery_status}</strong></td>
				<td width="80%" class="trow1"><select class="textbox" name="delivery_status">{$delivery_status}</select></td>
			</tr>
		</table>
		<br />
		<div style="text-align: center"><input type="submit" class="button" name="submit" value="{$lang->button_new}" accesskey="s" /></div>
		
			<input type="hidden" name="action" value="do_new" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',
			
				'prostore_trans_remove'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_trans_remove}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<form action="transaction.php" method="post" name="removetrans">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_trans_remove}</td>
			</tr>
			<tr>
				<td class="trow1" align="center">
					<br />
					{$lang->msg_trans_remove}
					<br /><br />
					<input type="submit" class="button" name="submit" value="{$lang->button_remove}" accesskey="s" />
					<br /><br />
			</td>
			</tr>
		</table>
		<br />

			<input type="hidden" name="action" value="do_remove" />
			<input type="hidden" name="tid" value="{$trans[\'tid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',
			
			'prostore_trans_purchase_success'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_trans_purchase_success}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead">{$lang->title_trans_purchase_success}</td>
			</tr>
			<tr>
				<td align="center" class="trow1">
					<br />
					{$lang->trans_purchase_success}
					<br />
					<br />
				</td>
			</tr>
		</table>
		
		{$footer}
	</body>
</html>',
			
		//TEMPLATE: Cart
		'prostore_cart_view'					=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_cart_view}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		{$frozennotice}
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_cart_view}</td>
			</tr>
			<tr>
				<td width="40%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_name}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_quantity}</strong></td>
				<td width="10%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_prodcost}</strong></td>
				<td width="10%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_postcost}</strong></td>
				<td width="20%" class="trow1" style="text-align: center"><strong>{$lang->label_prodlist_controls}</strong></td>
			</tr>
			{$productlist}
			<tr>
				<td width="40%" class="trow1" style="text-align: right">
					<strong>{$lang->label_prodlist_total}</strong>
				</td>
				<td width="20%" class="trow1">
					<strong>{$totalquantity}</strong>
				</td>
				<td width="10%" class="trow1">
					<strong>&#36;{$total_prodcost} total</strong>
				</td>
				<td width="10%" class="trow1">
					&#36;{$total_postcost} postage
				</td>
				<td width="20%" class="trow1">
					
				</td>
			</tr>
		</table>
		<br />
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="10">{$lang->title_cart_controls}</td>
			</tr>
			<tr>
				<td width="25%" class="trow1">
					<a href="cart.php?action=empty">{$lang->label_cart_empty}</a>
				</td>
				<td width="25%" class="trow1">
					
				</td>
				<td width="25%" class="trow1">
					
				</td>
				<td width="25%" class="trow1">
					<a href="cart.php?action=process">{$lang->label_cart_process}</a>
				</td>
			</tr>
		</table>
		{$usercontrols}
		{$managercontrols}
		{$footer}
	</body>
</html>',
			
		'prostore_cart_view_productbit' => '
<tr>
	<td width="40%" class="trow1">
		<a href="product.php?pid={$product[\'pid\']}">{$product[\'name\']}</a>
	</td>
	<td width="20%" class="trow1">
		{$prodquantity}
	</td>
	<td width="10%" class="trow1">
		&#36;{$product[\'prodcost\']}
	</td>
	<td width="10%" class="trow1">
		&#36;{$product[\'postcost\']}
	</td>
	<td width="20%" class="trow1">
		<form action="cart.php" method="post">
			<input type="hidden" name="action" value="do_remove" />
			<input type="hidden" name="pid" value="{$product[\'pid\']}" />

			<input type="submit" value="{$lang->button_remove}" />
		</form>
	</td>
</tr>',
			
		'prostore_cart_empty'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_cart_empty}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		<form action="cart.php" method="post" name="emptycart">
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_cart_empty}</td>
			</tr>
			<tr>
				<td class="trow1" align="center">
					<br />
					Are you sure you want to empty your cart? This cannot be undone!
					<br /><br />
					<input type="submit" class="button" name="submit" value="{$lang->button_empty}" accesskey="s" />
					<br /><br />
			</td>
			</tr>
		</table>
		<br />

			<input type="hidden" name="action" value="do_empty" />
			<input type="hidden" name="cid" value="{$cart[\'cid\']}" />
			<!-- <input type="hidden" name="posthash" value="{$posthash}" /> -->
		</form>

		{$footer}
	</body>
</html>',
			
			'prostore_cart_process'		=> '
<html>
	<head>
		<title>{$store[\'name\']} - {$lang->title_cart_process}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$closednotice}
		
		<table width="100%" class="tborder">
			<tr>
				<td class="thead" colspan="2">{$lang->title_cart_process}</td>
			</tr>
			<tr>
				<td width="100%" class="trow1" style="text-align: center">
					<form action="https://www.paypal.com/cgi-bin/webscr" target="paypal" method="post"> 
						<input type="hidden" name="business" value="{$store[\'paypal\']}">  
						<input type="hidden" name="currency_code" value="AUD">  
						<input type="hidden" name="lc" value="AU">

						<input type="hidden" name="cmd" value="_xclick">
						<input type="hidden" name="notify_url" value="{$mybb->settings[\'bburl\']}/ipn.php?action=validate">

						<input type="hidden" name="custom" value="{$transid}">

						<input type="hidden" name="return" value="{$mybb->settings[\'bburl\']}/transaction.php?action=purchased">
						<input type="hidden" name="cancel_return" value="{$mybb->settings[\'bburl\']}/cart.php">
						<input type="hidden" name="shopping_url" value="{$mybb->settings[\'bburl\']}/{$store[\'url\']}">

						<input type="hidden" name="item_name" value="{$store[\'name\']} Purchase">
						<input type="hidden" name="amount" value="{$total_prodcost}">
						<input type="hidden" name="shipping" value="{$total_postcost}">
						<input type="hidden" name="item_number" value="">
						<input type="hidden" name="shipping2" value="0.00">
						<input type="hidden" name="handling" value="0.00">
						
						<input type="hidden" name="quantity" value="1">
						<input type="hidden" name="undefined_quantity" value="0">

						<!--<input type="hidden" name="handling_cart" value="{$product[\'postcost\']}">-->

						<input type="hidden" name="no_note" value="1">
						<input type="hidden" name="undefined_quantity" value="1">
						<br />
						{$lang->paypal_redirect_warning}
						<br /><br />
						<input type="submit" class="button" name="submit" value="{$lang->button_process}" accesskey="s" />
						<br /><br />

						<!-- <input type="hidden" name="bn" value="PP-BuyNowBF">  -->
						<!-- <input type="image" src="http://www.paypalobjects.com/en_US/i/btn/x-click-but22.gif" border="0" name="submit" width="87" height="23" alt="Make payments with PayPal - it\'s fast, free and secure!"> -->  
					</form>
				</td>
			</tr>
		</table>
		<br />

		{$footer}
	</body>
</html>',
			
		'prostore_cart_process_productbit' => '
<input type="hidden" name="item_name_{$counter}" value="Product {$product[\'code\']} - {$product[\'name\']}">  
<input type="hidden" name="item_number_{$counter}" value="{$productcount}">
<input type="hidden" name="amount" value="{$product[\'prodcost\']}">
<input type="hidden" name="shipping" value="{$product[\'postcost\']}">
',

		//TEMPLATE: User Controls	
		'prostore_user_controls'		=> '
<br />
<table width="100%" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			{$lang->title_user_controls}
		</td>
	</tr>
	<tr>
		{$controls}
	</tr>
</table>',
			
			'prostore_user_controls_frontend' => '
<td width="25%" class="trow1">
	<a href="store.php?action=mytrans">{$lang->user_controls_view_trans}</a>
</td>
<td width="25%" class="trow1">
	<a href="cart.php">{$lang->user_controls_view_cart}</a>
</td>
<td width="25%" class="trow1">
	<a href="store.php?action=terms">{$lang->user_controls_tc}</a>
</td>
<td width="25%" class="trow1">
	<a href="store.php?action=help">{$lang->user_controls_help}</a>
</td>',
			
			//NEW
			'prostore_user_controls_trans_view' => '
<td width="25%" class="trow1">
	{$delivery_dropdown}
</td>
<td width="25%" class="trow1">
	
</td>
<td width="25%" class="trow1">
	
</td>
<td width="25%" class="trow1">
	
</td>',
			
			'prostore_user_controls_add_cart' => '
<form action="cart.php" method="post">
	<input type="hidden" name="action" value="do_add" />
	<input type="hidden" name="pid" value="{$product[\'pid\']}" />
		
	<input type="submit" value="{$lang->button_add_cart}" />
</form>',
		
		//TEMPLATE: Manager Controls	
		'prostore_manager_controls'		=> '
<br />
<table width="100%" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			{$lang->title_manager_controls}
		</td>
	</tr>
	<tr>
		{$controls}
	</tr>
</table>',
			
			'prostore_manager_controls_frontend' => '
<td width="25%" class="trow1">
	<a href="store.php?action=backend">{$lang->manager_controls_backend}</a>
</td>
<td width="25%" class="trow1">
	<a href="product.php?action=new">{$lang->manager_controls_newprod}</a>
</td>
<td width="25%" class="trow1">
	<a href="transaction.php?action=new">{$lang->manager_controls_newtrans}</a>
</td>
<td width="25%" class="trow1">
	{$togglestore}
</td>		
',
			
			'prostore_manager_controls_backend' => '
<td width="25%" class="trow1">
	<a href="store.php">{$lang->manager_controls_frontend}</a>
</td>
<td width="25%" class="trow1">
	<a href="product.php?action=new">{$lang->manager_controls_newprod}</a>
</td>
<td width="25%" class="trow1">
	<a href="transaction.php?action=new">{$lang->manager_controls_newtrans}</a>
</td>
<td width="25%" class="trow1">
	{$togglestore}
</td>		
',
			
		'prostore_manager_controls_product_view' => '
<td width="25%" class="trow1">
	<form action="product.php" method="post">
		<input type="hidden" name="action" value="edit" />
		<input type="hidden" name="pid" value="{$product[\'pid\']}" />
		
		<input type="submit" value="{$lang->button_edit}" />
	</form>
</td>
<td width="25%" class="trow1">
	<form action="product.php" method="post">
		<input type="hidden" name="action" value="remove" />
		<input type="hidden" name="pid" value="{$product[\'pid\']}" />
		
		<input type="submit" value="{$lang->button_remove}" />
	</form>
</td>
<td width="25%" class="trow1">
	<!--<form action="transaction.php" method="post">
		<input type="hidden" name="action" value="do_addprod" />
		<input type="hidden" name="pid" value="{$product[\'pid\']}" />
		
		Add to transaction: 
		<input type="text" name="tid" value="" size="1" /><input type="submit" value="{$lang->button_add}" />
	</form>-->
</td>
<td width="25%" class="trow1">

</td>			
',
			
		'prostore_manager_controls_trans_view' => '
<td width="25%" class="trow1">
	<form action="transaction.php" method="post">
		<input type="hidden" name="action" value="edit" />
		<input type="hidden" name="tid" value="{$trans[\'tid\']}" />
		
		<input type="submit" value="{$lang->button_edit}" />
	</form>
</td>
<td width="25%" class="trow1">
	<form action="transaction.php" method="post">
		<input type="hidden" name="action" value="remove" />
		<input type="hidden" name="tid" value="{$trans[\'tid\']}" />
		
		<input type="submit" value="{$lang->button_remove}" />
	</form>
</td>
<td width="25%" class="trow1">
	
</td>
<td width="25%" class="trow1">

</td>			
',
			
			'prostore_manager_controls_trans_view_productbit' => '
<form action="transaction.php" method="post">
	<input type="hidden" name="action" value="do_removeprod" />
	<input type="hidden" name="pid" value="{$product[\'pid\']}" />
		
	<input type="submit" value="{$lang->button_remove}" />
</form>',
			
		'prostore_manager_controls_cart_view' => '
<td width="25%" class="trow1">
	{$togglefreeze}
</td>
<td width="25%" class="trow1">
	
</td>
<td width="25%" class="trow1">
	
</td>
<td width="25%" class="trow1">

</td>',
			
		'prostore_manager_controls_cart_freeze' => '
<form action="cart.php" method="post">
	<input type="hidden" name="action" value="freeze" />
	<input type="hidden" name="tid" value="{$cart[\'cid\']}" />

	<input type="submit" value="{$lang->button_freeze}" />
</form>',
			
		'prostore_manager_controls_cart_unfreeze' => '
<form action="cart.php" method="post">
	<input type="hidden" name="action" value="unfreeze" />
	<input type="hidden" name="tid" value="{$cart[\'cid\']}" />

	<input type="submit" value="{$lang->button_unfreeze}" />
</form>',
			
		//TEMPLATE: Misc
		'prostore_form_errors'		=> '
<tr>
	<td colspan="4">
		<strong>Errors:</strong>
		{$errors}
	</td>
</tr>',
			
		'prostore_form_errors_errorbit'		=> '
{$errormsg}<br />',
		
		'prostore_no_products_row' => '
<tr>
	<td colspan="10" class="trow1" style="padding: 10px; text-align: center">{$lang->error_no_products}</td>
</tr>',
			
		//NEW
		'prostore_no_trans_row' => '
<tr>
	<td colspan="10" class="trow1" style="padding: 10px; text-align: center">{$lang->error_no_trans}</td>
</tr>',
	);

	//Insert templates...
	foreach ($templates as $title => $data) {
		//If to insert or to insert all, and we are permitted to insert it...
		if ((in_array($title, $Doadd) || count($Doadd) == 0) && !in_array($title, $Donotadd)) {
			$insert = array(
				'title' => $db->escape_string($title),
				'template' => $db->escape_string($data),
				'sid' => "-1",
				'version' => '1',
				'dateline' => TIME_NOW
			);
			$db->insert_query('templates', $insert);
		}
	}
}
	

//FUNCTION: Insert settings.
function prostore_insert_settings($Doadd=array(), $Donotadd=array()) {
	global $mybb, $db;
	
  //Insert a new settings group...
	$insertgroup = array(
		'name' => 'prostore',
		'title' => 'Pro Store',
		'description' => 'Settings for Pro Store.',
		'disporder' => '61',
		'isdefault' => 0
	);
	$group['gid'] = $db->insert_query("settinggroups", $insertgroup);
	
	$insertarray = array();
	
	//Enabling...
	$insertarray[] = array(
		'name' => 'prostore_enable_frontend',
		'title' => 'Enable frontend',
		'description' => 'Enable users access the frontend of the store.',
		'optionscode' => 'yesno',
		'value' => '0',			//Off to let the managers set up!
		'disporder' => 2,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'prostore_enable_backend',
		'title' => 'Enable backend',
		'description' => 'Enable managers access the backend of the store.',
		'optionscode' => 'yesno',
		'value' => '1',
		'disporder' => 3,
		'gid' => $group['gid']
	);
	
	
	//Store details:
	$insertarray[] = array(
		'name' => 'prostore_store_name',
		'title' => 'Store name',
		'description' => 'The name of the store (empty to generate one using board name).',
		'optionscode' => 'text',
		'value' => '',		//Empty to use board name + store
		'disporder' => 4,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'prostore_store_desc',
		'title' => 'Store description',
		'description' => 'The description of the store (empty to use default).',
		'optionscode' => 'text',
		'value' => '',		//Empty to use predefined one
		'disporder' => 5,
		'gid' => $group['gid']
	);
	
	
	//Other settings:
	$insertarray[] = array(
		'name' => 'prostore_manager_usergroups',
		'title' => 'Manager usergroups',
		'description' => 'Comma delimited list of usergroups who can manage the store inventory and basic store info (new usergroup recommended).',
		'optionscode' => 'text',
		'value' => '4',
		'disporder' => 6,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'prostore_allowed_usergroups',
		'title' => 'Allowed usergroups',
		'description' => 'Comma delimited list of usergroups who can access the store (only when open) and buy products (leave blank for all usergroups except banned).',
		'optionscode' => 'text',
		'value' => '',
		'disporder' => 7,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'prostore_paypal_email',
		'title' => 'PayPal email',
		'description' => 'Valid PayPal email address to send customers for product payment (default admin email).',
		'optionscode' => 'text',
		'value' => '',
		'disporder' => 8,
		'gid' => $group['gid']
	);
	
	//TODO: Toggle specific notifications.
	$insertarray[] = array(
		'name' => 'prostore_enable_notify',
		'title' => 'Enable notifications',
		'description' => 'Enable sending notifications to managers of updated transactions.',
		'optionscode' => 'yesno',
		'value' => '1',				//Enable by default
		'disporder' => 9,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'prostore_notify_sender',
		'title' => 'Notification sender',
		'description' => 'Who the message would be sent from.',
		'optionscode' => 'text',
		'value' => '1',				//Super admin
		'disporder' => 10,
		'gid' => $group['gid']
	);
	
	$insertarray[] = array(
		'name' => 'prostore_notify_message',
		'title' => 'Notification message',
		'description' => 'Message of the manager notification. Use {customer} and {transurl}.',
		'optionscode' => 'textarea',
		'value' => '[b]Notification from the store[/b]

	A transaction made by {customer} has been updated by PayPal. View the transaction [url={transurl}]here[/url].

	This is an automated message. Please do not reply.',
		'disporder' => 11,
		'gid' => $group['gid']
	);
	
	//SETTING: Closed reason
	$insertarray[] = array(
		'name' => 'prostore_closed_reason',
		'title' => 'Frontend closed reason',
		'description' => 'Message for users who cannot access the store.',
		'optionscode' => 'textarea',
		'value' => '',
		'disporder' => 12,
		'gid' => $group['gid']
	);
	
	foreach ($insertarray as $properties) {
		//If to insert or to insert all, and we are permitted to insert it...
		if ((in_array($properties['name'], $Doadd) || count($Doadd) == 0) && !in_array($properties['name'], $Donotadd)) {
			//Insert our setting...
			$db->insert_query("settings", $properties);
		}
	}
	
	//Update all settings...
	rebuild_settings();
}


//FUNCTION: Uninstall the plugin
function prostore_uninstall() {
	global $mybb, $db;
	
	//Deactivate just to be sure...
	prostore_deactivate();
	
	//Remove all settings from the database...
	$db->delete_query("settings", "name LIKE 'prostore%'");
	$db->delete_query("settinggroups", "name = 'prostore'");

	//Update the settings...
	rebuild_settings();
	
	//TODO: Use wildcard!
	$tables = array(
		'prostore_products',
		'prostore_categories',
		'prostore_transactions',
		'prostore_carts'
	);

	//Drop tables if they exist...
	foreach ($tables as $tablename) {
		if ($db->table_exists($tablename)) {
			$db->drop_table($tablename);
		}
	}
	
	//Remove all other templates...
	$db->delete_query("templates", "`title` LIKE 'prostore%'");
}


//FUNCTION: Activate the plugin
function prostore_activate() {
	global $mybb, $db;
	
	//Deactivate it first so we start fresh...
	prostore_deactivate();
	
	//Add the variable to templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	#find_replace_templatesets("header", '#</ul>#', "	<li><a href=\"{\$mybb->settings['bburl']}/store.php\"><img src=\"{\$theme['imgdir']}/toplinks/help.gif\" alt=\"\" title=\"\" />Store</a></li>\n</ul>");
	//{\$lang->toplinks_store}
}


//FUNCTION: Deactivate the plugin
function prostore_deactivate() {
	global $mybb, $db;
	
	//Remove the variable from templates...
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	#find_replace_templatesets("header", "#<li><a href=\"{\$mybb->settings['bburl']}/store.php\"><img src=\"{\$theme['imgdir']}/toplinks/help.gif\" alt=\"\" title=\"\" />Store</a></li>#", '', 0);
}


//FUNCTION: Create new cart for new user
function prostore_register_add_cart() {
	global $mybb, $db;
	
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."users ORDER BY `uid` ASC LIMIT 1");
	
	$lastuser = $db->fetch_array($query);
	
	if ($lastuser['uid']) {
		//Insert a cart for the user...
		$insertcart = array(
			'cid' => '',
			'uid' => $lastuser['uid'],
			'pids' => '',
			'dateadded' => time(),
			'dateupdated' => time(),
			'whoadded' => $lastuser['uid'],
			'whoupdated' => $lastuser['uid'],
			'status' => '1'
		);
		
		$last_cartid = $db->insert_query("prostore_carts", $insertcart);
	} else {
		return false;
	}
}


//FUNCTION: Check if user is allowed to access store
function prostore_is_allowed() {
	global $mybb, $db;
	
	$allowedgroups = explode(',', $mybb->settings['prostore_allowed_usergroups']);
	$managergroups = explode(',', $mybb->settings['prostore_manager_usergroups']);
	
	//Always allow managers...
	$allowedgroups = array_merge($allowedgroups, $managergroups);
	
	$additionalgroups = explode(',', $mybb->user['additionalgroups']);
	
	//Check if they are allowed...
	if (in_array($mybb->user['usergroup'], $allowedgroups)) {
		return true;
	} else {
		foreach ($additionalgroups as $gid) {
			if (in_array($gid, $allowedgroups)) {
				return true;
			}
		}

		return false;
	}
}


//FUNCTION: Get closed message
function prostore_get_closed_message() {
	global $mybb, $db, $lang;
	
	$reason = $lang->error_frontend_closed;

	if ($mybb->settings['prostore_closed_reason']) {
		$reason .= '<br /><br /><strong>Reason:</strong> '.$mybb->settings['prostore_closed_reason'];
	}

	return $reason;
}


//FUNCTION: Check if user is a manager
//TODO: Accept user ID only?
function prostore_is_manager($userobj=array()) {
	global $mybb, $db;
	
	$managergroups = explode(',', $mybb->settings['prostore_manager_usergroups']);
	
	//If we're being passed an array...
	if ($userobj['usergroup'] || $userobj['additionalgroups']) {
		$usergroup = $userobj['usergroup'];
		$additionalgroups = explode(',', $userobj['additionalgroups']);
	} else {
		$usergroup = $mybb->user['usergroup'];
		$additionalgroups = explode(',', $mybb->user['additionalgroups']);
	}
	
	//Check if they are allowed...
	if (in_array($usergroup, $managergroups)) {
		return true;
	} else {
		foreach ($additionalgroups as $gid) {
			if (in_array($gid, $managergroups)) {
				return true;
			}
		}
		
		return false;
	}
}


//FUNCTION: List all managers
function prostore_list_managers() {
	global $mybb, $db;
	
	$managergroups = $mybb->settings['prostore_manager_usergroups'];
	
	//We need a list of managers...
	if ($managergroups) {
		//Get users who are managers...
		#$managers = $db->simple_select("users","*","`usergroup` IN ({$managergroups}) OR `additionalgroups` IN ({$managergroups})");
		#$managers = $db->simple_select("users","*","`usergroup` LIKE CONCAT({$managergroups}) OR `additionalgroups` LIKE CONCAT({$managergroups})");
		
		$managers = $db->simple_select("users","*");

		//Loop through them all...
		while ($user = $db->fetch_array($managers)) {
			if (prostore_is_manager($user)) {

				if (isset($managerlist))		$comma = ',';

				$managerlist .= "{$comma} <a href=\"".get_profile_link($user['uid'])."\">".htmlspecialchars_uni($user['username'])."</a>";
			}
		}

		return $managerlist;
	} else {
		//TODO: Report error!
		return 'None';
	}
}


//FUNCTION: Gets all manager user IDs.
function prostore_get_manager_ids() {
	global $mybb, $db;
	
	$managergroups = $mybb->settings['prostore_manager_usergroups'];
	
	//We need a list of managers...
	if ($managergroups) {
		//Get all users (inefficient I know)...
		$managers = $db->simple_select("users","*");
		
		$manager_ids = array();
		
		//If we have managers...
		if ($db->num_rows($managers) > 0) {
			//Loop through them all...
			while ($user = $db->fetch_array($managers)) {
				//If a manager...
				if (prostore_is_manager($user)) {
					$manager_ids[] = $user['uid'];
				}
			}

			return $manager_ids;
		} else {
			return array();
		}
	} else {
		//We have no managers...
		return array();
	}
}


//FUNCTION: Gets the store info
function prostore_store_getinfo() {
	global $mybb, $db;
	
	$store = array(
		'name' 						=> $mybb->settings['prostore_store_name'],
		'desc'						=> $mybb->settings['prostore_store_desc'],
		'frontstatus'			=> $mybb->settings['prostore_enable_frontend'],
		'backstatus'			=> $mybb->settings['prostore_enable_backend'],
		'managergroups'		=> explode(',', $mybb->settings['prostore_manager_usergroups']),
		'managerids' 			=> explode(',', $mybb->settings['prostore_manager_userids']),
		'paypal' 					=> $mybb->settings['prostore_paypal_email'],
		'url'							=> 'store.php',
	);
	
	if (!$store['name']) {
		$store['name'] = $mybb->settings['bbname'].' Store';
	}
	
	if (!$store['desc']) {
		$store['desc'] = 'Store for '.$mybb->settings['bbname'].'.';
	}
	
	if (!$store['paypal']) {
		$store['paypal'] = $mybb->settings['adminemail'];
	}
	
	return $store;
}


//FUNCTION: Gets the cart
function prostore_cart_getinfo($uid=0, $cid=0) {
	global $mybb, $db;
	
	//Sanatise...
	if (!intval($cid)) {
		$cid = 0;
	}
	if (!intval($uid)) {
		$uid = 0;
	}
	
	//Default to current user's cart...
	if (!$uid && !$cid) {
		$uid = $mybb->user['uid'];
	}

	$query = $db->simple_select("prostore_carts", "*", "`cid` = '{$cid}' OR `uid` = '{$uid}'", array('limit' => 1));
	$cart = $db->fetch_array($query);

	if ($cart['cid']) {
		return $cart;
	} else {
		return false;
	}
}


//FUNCTION: Gets the product info
function prostore_product_getinfo($pid) {
	global $mybb, $db;
	
	if (intval($pid)) {
		$query = $db->simple_select("prostore_products", "*", "pid = '{$pid}'", array('limit' => 1));
		$product = $db->fetch_array($query);

		if ($product['pid']) {
			return $product;
		}
	}
}


//FUNCTION: Gets the category info
function prostore_category_getinfo($cid) {
	global $mybb, $db;
	
	if (intval($cid)) {
		$query = $db->simple_select("prostore_categories", "*", "cid = '{$cid}'", array('limit' => 1));
		$category = $db->fetch_array($query);

		if ($category['cid']) {
			return $category;
		}
	}
}


//FUNCTION: Gets the transaction info
function prostore_trans_getinfo($tid) {
	global $mybb, $db;
	
	if (intval($tid)) {
		$query = $db->simple_select("prostore_transactions", "*", "`tid` = '{$tid}'", array('limit' => 1));
		$trans = $db->fetch_array($query);

		if ($trans['tid']) {
			return $trans;
		}
	}
}


//FUNCTION: Gets if a cart is frozen or not
function prostore_cart_getfrozen($cid=null, $uid=null) {
	global $mybb, $db;
	
	if (!intval($uid)) {
		$uid = $mybb->user['uid'];
	}
	
	$query = $db->simple_select("prostore_carts", "*", "`cid` = '{$cid}' OR `uid` = '{$uid}'", array('limit' => 1));
	$cart = $db->fetch_array($query);

	if ($cart['status'] == 0) {
		return true;
	} else {
		return false;
	}
}


//FUNCTION: Close the front end
function prostore_close_frontend() {
	global $mybb, $db;
	
	$db->query("UPDATE ".TABLE_PREFIX."settings SET `value` = 0 WHERE `name` = 'prostore_enable_frontend'");
	
	rebuild_settings();
	
	return true;
}


//FUNCTION: Open the front end
function prostore_open_frontend() {
	global $mybb, $db;
	
	$db->query("UPDATE ".TABLE_PREFIX."settings SET `value` = 1 WHERE `name` = 'prostore_enable_frontend'");
	
	rebuild_settings();
	
	return true;
}


//FUNCTION: Updates the backend settings.
function prostore_update_backend() {
	global $mybb, $db;
	
	//TODO: Make more flexible!
	$db->query("UPDATE ".TABLE_PREFIX."settings SET `value` = '".$db->escape_string($mybb->input['closed_reason'])."' WHERE `name` = 'prostore_closed_reason'");
	
	rebuild_settings();
	
	return true;
}


//FUNCTION: Validate new product data
function prostore_validate_product_input() {
	global $mybb, $db;
	
	$product = array();

	if ($mybb->input['code']) {
		if (strlen($mybb->input['code']) <= 5) {
			$product['code'] = $mybb->input['code'];
		} else {
			$errors[] = 'Product code too long (max 5 chars)';
		}
	} else {
		$errors[] = 'No product code';
	}
	
	if ($mybb->input['name']) {
		$product['name'] = $mybb->input['name'];
	} else {
		$errors[] = 'No product name';
	}
	
	if ($mybb->input['desc']) {
		$product['desc'] = $mybb->input['desc'];
	}
	
	if ($mybb->input['category']) {
		if (intval($mybb->input['category'])) {
			$query = $db->simple_select("prostore_categories", "*", "`cid` = '{$mybb->input['category']}'", array('limit' => 1));

			if ($category = $db->fetch_array($query)) {
				$product['category'] = $mybb->input['category'];
			} else {
				$errors[] = 'Invalid product category';
			}
		} else {
			$errors[] = 'Invalid product category';
		}
	} else {
		$product['category'] = 0;
	}
	
	if ($mybb->input['prodcost']) {
		if (floatval($mybb->input['prodcost'])) {
			$product['prodcost'] = floatval($mybb->input['prodcost']);
		} else {
			$errors[] = 'Invalid product cost (needs 0.00 format)';
		}
	} elseif ($mybb->input['prodcost'] == 0) {
		$product['prodcost'] = 0;
	} else {
		$product['prodcost'] = '';
	}
	
	if ($mybb->input['postcost']) {
		if (floatval($mybb->input['postcost'])) {
			$product['postcost'] = floatval($mybb->input['postcost']);
		} else {
			$errors[] = 'Invalid product postage cost (needs 0.00 format)';
		}
	} elseif ($mybb->input['postcost'] == 0) {
		$product['postcost'] = 0;
	} else {
		$product['postcost'] = '';
	}

	if ($mybb->input['numleft']) {
		if (intval($mybb->input['numleft'])) {
			$product['numleft'] = $mybb->input['numleft'];
		} else {
			$product['numleft'] = 0;
		}
	} else {
		$product['numleft'] = 0;
	}

	if ($mybb->input['numsold']) {
		if (intval($mybb->input['numsold'])) {
			$product['numsold'] = $mybb->input['numsold'];
		} else {
			$product['numsold'] = 0;
		}
	} else {
		$product['numsold'] = 0;
	}
	
	if ($mybb->input['imageurl']) {
		//if (filter_var($mybb->input['imageurl'], FILTER_VALIDATE_URL)) {
			$product['imageurl'] = $mybb->input['imageurl'];
		//} else {
		//	$errors[] = 'Invalid image URL';
		//}
	}
	
	if ($mybb->input['thumburl']) {
		//if (filter_var($mybb->input['thumburl'], FILTER_VALIDATE_URL)) {
			$product['thumburl'] = $mybb->input['thumburl'];
		//} else {
		//	$errors[] = 'Invalid thumb URL';
		//}
	}
	
//	if ($mybb->input['name']) {
//		$product['name'];
//	} else {
//		$errors[] = 'No product name';
//	}

	//If we have any errors, return them, otherwise return valid product...
	if (count($errors) > 0) {
		return array('errors' => $errors);
	} else {
		return $product;
	}
}


//FUNCTION: Validate new transaction data
function prostore_validate_trans_input() {
	global $mybb;
	
	$trans = array();
	
	if ($mybb->input['pids']) {
		$pids = explode(',', $mybb->input['pids']);
		
		foreach ($pids as $pid) {
			if (intval($pid))				$goodids[] = $pid;
		}
		
		if (count($goodids) > 0) {
			$trans['pids'] = implode(',', $goodids);
		} else {
			$errors[] = 'Invalid product IDs';
		}
	} else {
		$errors[] = 'No product IDs';
	}

	if ($mybb->input['uid']) {
		if (intval($mybb->input['uid'])) {
			$trans['uid'] = $mybb->input['uid'];
		} else {
			$errors[] = 'Invalid buyer ID';
		}
	} else {
		$errors[] = 'No buyer ID';
	}

	if ($mybb->input['paid_prodcost']) {
		if (floatval($mybb->input['paid_prodcost'])) {
			$trans['paid_prodcost'] = floatval($mybb->input['paid_prodcost']);
		} else {
			$errors[] = 'Invalid product cost (needs 0.00 format)';
		}
	} else {
		$trans['paid_prodcost'] = '';
	}
	
	if ($mybb->input['paid_postcost']) {
		if (floatval($mybb->input['paid_postcost'])) {
			$trans['paid_postcost'] = floatval($mybb->input['paid_postcost']);
		} else {
			$errors[] = 'Invalid postage cost (needs 0.00 format)';
		}
	} else {
		$trans['paid_postcost'] = '';
	}
	
	if ($mybb->input['postal_details']) {
		$trans['postal_details'] = $mybb->input['postal_details'];
	} else {
		$trans['postal_details'] = '';
	}
	
	if ($mybb->input['paypal_email']) {
		$trans['paypal_email'] = $mybb->input['paypal_email'];
	} else {
		$trans['paypal_email'] = '';
	}
	
	if ($mybb->input['payment_status'] || $mybb->input['payment_status'] == 0) {
		if (intval($mybb->input['payment_status']) || $mybb->input['payment_status'] == 0) {
			$trans['payment_status'] = $mybb->input['payment_status'];
		} else {
			$errors[] = 'Invalid payment status';
		}
	} else {
		$errors[] = 'Invalid payment status';
	}
	
	if ($mybb->input['delivery_status'] || $mybb->input['delivery_status'] == 0) {
		if (intval($mybb->input['delivery_status']) || $mybb->input['delivery_status'] == 0) {
			$trans['delivery_status'] = $mybb->input['delivery_status'];
		} else {
			$errors[] = 'Invalid delivery status';
		}
	} else {
		$errors[] = 'Invalid delivery status';
	}

	//If we have any errors, return them, otherwise return valid product...
	if (count($errors) > 0) {
		return array('errors' => $errors);
	} else {
		return $trans;
	}
}


//FUNCTION: Generate HTML dropdown lists
function prostore_generate_dropdown($Name, $Selected=null) {
	global $mybb, $db, $lang;
	
	switch ($Name) {
		case 'category':
			$query = $db->simple_select("prostore_categories", "*");

			if ($db->num_rows($query) > 0) {
				while ($category = $db->fetch_array($query)) {
					if ($category['cid'] == $Selected || $category['name'] == $Selected) {
						$selectit = ' selected';
					} else {
						$selectit = '';
					}

					$options .= '	<option value="'.$category['cid'].'"'.$selectit.'>'.$category['name'].'</option>';
				}
			} else {
				$options .= '<option value="">--- NONE ---</option>';
			}
		break;
		
		case 'payment_status':
			$statusarray = array($lang->label_trans_payment_pending, $lang->label_trans_payment_paid, $lang->label_trans_payment_failed, $lang->label_trans_payment_refunded, $lang->label_trans_payment_other);
			
			foreach ($statusarray as $id => $name) {
				if ($id == $Selected || $name == $Selected) {
					$selectit = ' selected';
				} else {
					$selectit = '';
				}
				
				$options .= '	<option value="'.$id.'"'.$selectit.'>'.$name.'</option>';
			}
		break;
		
		case 'delivery_status':
			$statusarray = array($lang->label_trans_delivery_notsent, $lang->label_trans_delivery_sent, $lang->label_trans_delivery_arrived, $lang->label_trans_delivery_notarrived);
			
			foreach ($statusarray as $id => $name) {
				if ($id == $Selected || $name == $Selected) {
					$selectit = ' selected';
				} else {
					$selectit = '';
				}
				
				$options .= '	<option value="'.$id.'"'.$selectit.'>'.$name.'</option>';
			}
		break;
		
		case 'delivery_status_customer':
			$statusarray = array($lang->label_trans_delivery_arrived, $lang->label_trans_delivery_notarrived);
			
			foreach ($statusarray as $id => $name) {
				if ($id == $Selected || $name == $Selected) {
					$selectit = ' selected';
				} else {
					$selectit = '';
				}
				
				//Customers can't choose low numbers...
				$id = $id + 2;
				
				$options .= '	<option value="'.$id.'"'.$selectit.'>'.$name.'</option>';
			}
		break;
	}
	
	return $options;
}


//FUNCTION: Validate PayPal IPN transaction
function prostore_ipn_validate($ipn_post_data) {
	//If we are testing with the PayPal sandbox...
//	if(array_key_exists('test_ipn', $ipn_post_data) && 1 === (int) $ipn_post_data['test_ipn']) {
//		$url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
//	} else {
//		$url = 'https://www.paypal.com/cgi-bin/webscr';
//	}
	
	//The URL to query...
	$url = 'https://www.paypal.com/cgi-bin/webscr';
	
	//cURL method: http://www.geekality.net/2011/05/28/php-tutorial-paypal-instant-payment-notification-ipn/

	//Set up request to PayPal...
	$request = curl_init();
	curl_setopt_array($request, array (
		CURLOPT_URL => $url,
		CURLOPT_POST => TRUE,
		CURLOPT_POSTFIELDS => http_build_query(array('cmd' => '_notify-validate') + $ipn_post_data),
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HEADER => FALSE,
		CURLOPT_SSL_VERIFYPEER => TRUE,
		CURLOPT_CAINFO => 'cacert.pem'
	));

	//Execute request and get response and status code...
	$response = curl_exec($request);
	$status   = curl_getinfo($request, CURLINFO_HTTP_CODE);

	//Close connection...
	curl_close($request);

	//Return result...
	if($status == 200 && $response == 'VERIFIED') {
		return true;
	} else {
		return false;
	}
}


//FUNCTION: Notify managers by PM.
function prostore_notify_managers($Message) {
	global $mybb, $db;
	
	$manager_ids = prostore_get_manager_ids();
	
	//If we have managers to send...
	if (count($manager_ids) > 0) {
		require_once MYBB_ROOT."inc/datahandlers/pm.php";
		$pmhandler = new PMDataHandler();
		$pmhandler->admin_override = true;
		
		$pm = array(
			"subject" 	=> 'Store notification',
			"message" 	=> $Message,
			"icon" 		=> "-1",
			"toid" 		=> $manager_ids,
			"fromid" 	=> 1,						//TODO: Not use root admin! (what if user ID 1 is deleted?)
			"do" 		=> '',
			"pmid" 		=> ''
		);
		$pm['options'] = array(
			"signature" 		=> "0",
			"disablesmilies"	=> "0",
			"savecopy" 			=> "0",
			"readreceipt" 		=> "0"
		);
		$pmhandler->set_data($pm);

		//If valid...
		if($pmhandler->validate_pm()) {
			//Insert a PM...
			$pmhandler->insert_pm();
			
			return true;
		}
	}
	
	return false;
}
?>