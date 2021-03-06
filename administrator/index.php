<?php
	ini_set("include_path", "/var/www/vhosts/siloz.com/httpdocs/");
	//ini_set("include_path", "/var/www/vhosts/stage.james.siloz.com/httpdocs/website/"); 
	require_once("include/autoload.class.php");
	require_once('utils.php');
	require_once('config.php');
	setlocale(LC_MONETARY, 'en_US');

	if(isset($_POST['submit'])) {

        	if (!isset($_SESSION)){ session_start(); }

		$email = mysql_escape_string(trim($_POST['email']));
		$password = mysql_escape_string(trim($_POST['password']));
		$enc_pw = md5($password);

        	$res = mysql_query("SELECT user_id FROM users WHERE email = '$email' AND password='$enc_pw' AND admin = 'yes'");

       	if (empty($_POST['email']))
        	{
			$error = "Please enter an e-mail.<br>";
        	}
       	elseif (empty($_POST['password']))
        	{
			$error = "Please enter your password.<br>";
        	}

       	if (!$res || mysql_num_rows($res) <= 0)
        	{
			$error = "Login does not match and/or you are not an authorized administrator."; 
        	}
		else {
        		$_SESSION['admin_access']  = true;
        		$_SESSION['email']  = $email;
		}
	}

	if(isset($_POST['logout'])) {
		unset($_SESSION['admin_access']);
	}

	if (param_post('task') == 'unflag') {
		$silo_id = param_post('silo_id');
		$status = mysql_query("UPDATE silos SET status = 'active' WHERE silo_id = '$silo_id'");
		$flags = mysql_query("DELETE FROM flag_silo WHERE silo_id = '$silo_id'");
		$flagRadar = mysql_query("DELETE FROM flag_radar WHERE silo_id = '$silo_id' AND type='silo'");
			$notification = new Notification();
			$notification->silo_id = $silo_id;
			$notification->type = "Reactivate";
			$notification->Email();
		header('Location: '.$_SERVER['REQUEST_URI']);
		exit;
	}
	elseif (param_post('task') == 'item unflag') {
		$item_id = param_post('item_id');
		$status = mysql_query("UPDATE items SET status = 'pledged' WHERE item_id = '$item_id'");
		$flags = mysql_query("DELETE FROM flag_item WHERE item_id = '$item_id'");
		$flagRadar = mysql_query("DELETE FROM flag_radar WHERE item_id = '$item_id'");
			$notification = new Notification();
			$notification->item_id = $item_id;
			$notification->type = "Item Reactivate";
			$notification->Email();
		header('Location: '.$_SERVER['REQUEST_URI']);
		exit;
	}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml"	xml:lang="en">
	<head>
		<title><?=SITE_NAME?> - Site Admin</title>
		<link rel="stylesheet" type="text/css" href="../css/admin.css" />	
		<link rel="stylesheet" tyle="text/css" href="../css/jquery-ui-1.8.16.css"/>
	    <script type="text/javascript" src="../js/jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="../js/jquery-ui-1.8.16.min.js"></script> 				
		<script type="text/javascript" src="../js/popup-window.js"></script>	  
	    <script type="text/javascript" src="../js/jquery.placeholder.js"></script>		
	    <script type="text/javascript" src="../js/jquery.jconfirmation.js"></script>				
		<script type="text/javascript" src="../js/jquery.truncator.js"></script>
	  	<script type="text/javascript">
			$(document).ready(function() {
		      	$('.long_text').truncate({max_length: 1000});			    
				$('.confirmation').jConfirmAction({question : "Are you sure to delete?", yesAnswer : "Yes", cancelAnswer : "No"});		
			});
	  	</script>
		<?php
			//SPECIAL REDIRECT CASES
			$view = param_get('view');						
		?>
		
	</head>

<?php
if (empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'on') { 
	echo "<script>window.location = 'https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "';</script>";
    	exit();
} elseif (!isset($_SESSION['admin_access'])) {
?>
<div align="center" style="margin-top: 100px;" class="login" id="login">
	<div>
		<form name="login_form" id="login_form" method="POST">
			<input type="hidden" name="purpose" value=""/>
			<h2>Admin login</h2>
			<table>
				<tr>
					<td>
						<input type="text" name="email" id="email" onfocus="select();" placeholder="E-mail" value="<?=$email?>"/>
					</td>
					<td>
						<input type="password" name="password" id="password" onfocus="select();"  placeholder="Password"/>
					</td>
				</tr>
			</table>
			<br/>			
			<button type="submit" name="submit">Login</button>
			<br><br>
			<font color="red"><b><?=$error?></b></font>
		</form>
<?php
} else {
?>

	<body style="background: #fff">

	<?php
	$admin = new User($_SESSION['user_id']);
	$checkRadar = mysql_num_rows(mysql_query("SELECT * FROM flag_radar WHERE notify = 1"));
		if ($checkRadar) { $bColor = "red"; } else { $bColor = "#2f8dcb"; }
	$silopay = mysql_query("SELECT * FROM silos WHERE status = 'ended' and paid = 'no'");
		if (mysql_num_rows($silopay) > 0) {
	?>
	<div align="center" style="margin-top: 25px">
		<a href="index.php?view=paysilo" style="text-decoration: none; font-size: 16px; color: red;">**ALERT: A silo needs to be payed out**</a>
	</div>
	<?php } ?>

		<div style="margin: 50px;">
			<div id="header">
				<button type="button" style="<?php echo ($view == 'stats' ? 'border-color: #f60' : ''); ?>" onclick="window.location='index.php?view=stats'">Site Statistics</button>
				<button type="button" style="<?php echo ($view == 'members' ? 'border-color: #f60' : ''); ?>" onclick="window.location='index.php?view=members'">Members</button>
				<button type="button" style="<?php echo ($view == 'items' ? 'border-color: #f60' : ''); ?>" onclick="window.location='index.php?view=items'">Items</button>
				<button type="button" style="<?php echo ($view == 'silos' ? 'border-color: #f60' : ''); ?>" onclick="window.location='index.php?view=silos'">Silos</button>
				<button type="button" style="border-color: <?=$bColor?> <?php echo ($view == 'radar' ? '; border-color: #f60' : ''); ?>" onclick="window.location='index.php?view=radar'">Radar Notifications</button>
			<div style="float: right; padding-right: 50px;"><form method="post"><input type="submit" name="logout" value="Logout"></form></div>
			<div style="float: right; padding-right: 100px;">Admin: <b><?=$admin->email?></b> <br><br>
			<?php	if ($checkRadar) {
					echo "<a href='index.php?view=radar' style='text-decoration: none'><font color='red'>New Flag Activity!</font></a></div>";
				}
			?>
			</div>
			<div id="main">
			<?php
			
				//STATS
				if ($view == 'stats') {
					echo "<br/>";
					$today = date("Y-m-d")."";					
					$html = "<table>";
					$active_public_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE status = 'active' AND silo_type = 'public'"));
					$active_private_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE status = 'active' AND silo_type = 'private'"));

					$public_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE silo_type = 'public'"));
					$private_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE silo_type = 'private'"));
					$html .= "<tr><td>Total number of <b>Public</b> silos:</td><td align=right><b> $active_public_silos_count[0] (active) / $public_silos_count[0] (total) </b></td></tr>";
					$html .= "<tr><td>Total number of <b>Private</b> silos:</td><td align=right><b> $active_private_silos_count[0] (active) / $private_silos_count[0] (total)</b></td></tr>";
					
					$users_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM users"));
					$admin_users = mysql_num_rows(mysql_query("SELECT * FROM users WHERE admin = 'yes'"));
					$adj_users_count = $users_count[0] - $admin_users;
					$html .= "<tr><td>Total number of members (excluding <b>$admin_users</b> admin users):</td><td align=right><b>".$adj_users_count."</b></td></tr>";
					
					$active_listings_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0"));
					$listing_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items"));
					$html .= "<tr><td>Total number of listings:</td><td align=right><b> $active_listings_count[0] (active) / $listing_count[0] (total)</b></td></tr>";
					
					$listing_average_value = mysql_fetch_array(mysql_query("SELECT AVG(price) FROM items"));
					$html .= "<tr><td>Average value of listings:</td><td align=right><b>".money_format('%(#10n', round($listing_average_value[0],2))."</b></td></tr>";
					
					//$donations_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM donations"));
					//$donations_average_value = mysql_fetch_array(mysql_query("SELECT AVG(amount) FROM donations"));
					//$html .= "<tr><td>Total number of donations:</td><td align=right><b> $donations_count[0]</b></td></tr>";
					//$html .= "<tr><td>Average value of donations:</td><td align=right><b>".money_format('%(#10n', round($donations_average_value[0],2))."</b></td></tr>";

					$public_funds_received = mysql_fetch_array(mysql_query("SELECT collected FROM silos WHERE silo_type = 'public'"));
					$private_funds_received = mysql_fetch_array(mysql_query("SELECT collected FROM silos WHERE silo_type = 'private"));
					$html .= "<tr><td>Total funds received - <b>Public</b> silos:</td><td align=right><b>".money_format('%(#10n', round($public_funds_received[0],2))."</b></td></tr>";
					$html .= "<tr><td>Total funds received - <b>Private</b> silos:</td><td align=right><b>".money_format('%(#10n', round($private_funds_received[0],2))."</b></td></tr>";

					$pur_success = mysql_num_rows(mysql_query("SELECT * FROM item_purchase WHERE status = 'sold'"));
					$pur_failed = mysql_num_rows(mysql_query("SELECT * FROM item_purchase WHERE status = 'declined' OR status = 'refunded'"));
					$pur_pct = ($pur_success/$pur_failed) * 100;
					$html .= "<tr><td>Total successful transactions:</td> <td align='right'><b>".$pur_success."</td>";
					$html .= "<tr><td>Percent of successful transactions:</td> <td align='right'><b>".$pur_pct."%</td>";

					$html .= "</table>";
					echo $html;
				}
				
				//ITEMS
				else if ($view == 'items') {
					if (param_post('delete') == 'items') {
						$items_to_delete = param_post('items');
						$in_clause = implode(',', $items_to_delete);
						$delBuyer = mysql_query("DELETE FROM buyer WHERE item_id IN ($in_clause)");
						$delFav = mysql_query("DELETE FROM favorites WHERE item_id IN ($in_clause)");
						$delFeed = mysql_query("DELETE FROM feed WHERE item_id IN ($in_clause)");
						$delFlagItem = mysql_query("DELETE FROM flag_item WHERE item_id IN ($in_clause)");
						$delRadarItem = mysql_query("DELETE FROM flag_radar WHERE item_id IN ($in_clause)");
						$delItem = mysql_query("DELETE FROM items WHERE item_id IN ($in_clause)");
						$delItemPur = mysql_query("DELETE FROM item_purchase WHERE item_id IN ($in_clause)");
						$delOffers = mysql_query("DELETE FROM offers WHERE item_id IN ($in_clause)");
						$delSellerCleared = mysql_query("DELETE FROM seller_cleared WHERE item_id IN ($in_clause)");
					}
					echo "<form name='items' id='items' action='index.php?view=items' method='post' onSubmit=\"return confirm('Delete these item(s)?')\">";
					echo "<input type='hidden' name='delete' value='items'>";
					$checkFlag = mysql_num_rows(mysql_query("SELECT * FROM flag_radar WHERE type = 'item'"));
					if ($checkFlag) {
					echo "<h2>Flagged items</h2>";
					$flagged_items = mysql_query("SELECT * FROM flag_radar WHERE type = 'item' ORDER BY item_id");				
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>Item ID</th><th width='4%' style='text-align: center'>Silo ID</th><th width='20%' style='text-align: center'>Title</th><th width='15%' style='text-align: center'>Category</th><th width='5%' style='text-align: center'>Seller's ID</th><th width='15%' style='text-align: center'>Seller's Name</th><th width='10%' style='text-align: center'>Status</th><th width='10%' style='text-align:center'>Price</th><th style='text-align:center'>Date Added</th></tr>";
					while ($item = mysql_fetch_array($flagged_items)) {
						$item_id = $item['item_id'];
						$user_id = $item['user_id'];
						$mem = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = '$user_id'"));
						$added_date = strtotime($item['added_date']); $date_added = date('m/d/y h:i a', $added_date);	
						$html .= "<tr><td><input type='checkbox' name='items[]' value=$item_id></td><td align='center'>".$item_id."</td><td align='center'>".$item['silo_id']."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_item&id=".$item[id]."' target='_blank'>".$item['title']."</a></td><td align='center'>".$item['category']."</td><td align='center'>".$user_id."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_user&id=".$mem['id']."' target='_blank'>".$mem['fname']." ".$mem['lname']."</a></td><td align='center'>".$item['status']."</td><td align='center'>".money_format('%(#10n', floatval($item['price']))."</td><td align='center'>".$date_added."</tr>";
						$html .= "<form action='' method='POST' name='unflag_".$item_id."'>
								<input type='hidden' name='task' value='item unflag'>
								<input type='hidden' name='item_id' value='$item_id'>
								<input type='submit' value='Reactivate'></input>
							</form></td></tr>";
					}
					$html .= "</table>";
					echo $html;
					}

					echo "<h2>Active items</h2>";
					$active_items = mysql_query("SELECT * FROM items INNER JOIN item_categories USING (item_cat_id) WHERE status = 'pledged' ORDER BY item_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>Item ID</th><th width='4%' style='text-align: center'>Silo ID</th><th width='20%' style='text-align: center'>Title</th><th width='15%' style='text-align: center'>Category</th><th width='5%' style='text-align: center'>Seller's ID</th><th width='15%' style='text-align: center'>Seller's Name</th><th width='10%' style='text-align: center'>Status</th><th width='10%' style='text-align:center'>Price</th><th style='text-align:center'>Date Added</th></tr>";
					while ($item = mysql_fetch_array($active_items)) {
						$item_id = $item['item_id'];
						$user_id = $item['user_id'];
						$mem = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = '$user_id'"));
						$added_date = strtotime($item['added_date']); $date_added = date('m/d/y h:i a', $added_date);	
						$html .= "<tr><td><input type='checkbox' name='items[]' value='$item_id'></td><td align='center'>".$item_id."</td><td align='center'>".$item['silo_id']."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_item&id=".$item['id']."' target='_blank'>".$item['title']."</a></td><td align='center'>".$item['category']."</td><td align='center'>".$user_id."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_user&id=".$mem['id']."' target='_blank'>".$mem['fname']." ".$mem['lname']."</a></td><td align='center'>".$item['status']."</td><td align='center'>".money_format('%(#10n', floatval($item['price']))."</td><td align='center'>".$date_added."</tr>";
					}
					$html .= "</table>";
					echo $html;

					$checkReq = mysql_num_rows(mysql_query("SELECT * FROM items WHERE status = 'requested'"));
					if ($checkReq) {
					echo "<h2>Requested items (Pending silo creation)</h2>";					
					$requested_items = mysql_query("SELECT * FROM items INNER JOIN item_categories USING (item_cat_id) WHERE status = 'requested' ORDER BY item_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>Item ID</th><th width='4%' style='text-align: center'>Silo ID</th><th width='20%' style='text-align: center'>Title</th><th width='15%' style='text-align: center'>Category</th><th width='5%' style='text-align: center'>Seller's ID</th><th width='15%' style='text-align: center'>Seller's Name</th><th width='10%' style='text-align: center'>Status</th><th width='10%' style='text-align:center'>Price</th><th style='text-align:center'>Date Added</th></tr>";
					while ($item = mysql_fetch_array($requested_items)) {
						$item_id = $item['item_id'];
						$user_id = $item['user_id'];
						$mem = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = '$user_id'"));
						$added_date = strtotime($item['added_date']); $date_added = date('m/d/y h:i a', $added_date);	
						$html .= "<tr><td><input type='checkbox' name='items[]' value=$item_id></td><td align='center'>".$item_id."</td><td align='center'>".$item['silo_id']."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_item&id=".$item['id']."' target='_blank'>".$item['title']."</a></td><td align='center'>".$item['category']."</td><td align='center'>".$user_id."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_user&id=".$mem['id']."' target='_blank'>".$mem['fname']." ".$mem['lname']."</a></td><td align='center'>".$item['status']."</td><td align='center'>".money_format('%(#10n', floatval($item['price']))."</td><td align='center'>".$date_added."</tr>";
					}
					$html .= "</table>";
					echo $html;
					}			

					$checkDel = mysql_num_rows(mysql_query("SELECT * FROM items WHERE status = 'deleted'"));
					if ($checkDel) {
					echo "<h2>Deleted items</h2>";					
					$deleted_items = mysql_query("SELECT * FROM items INNER JOIN item_categories USING (item_cat_id) WHERE status = 'deleted' ORDER BY item_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>Item ID</th><th width='4%' style='text-align: center'>Silo ID</th><th width='20%' style='text-align: center'>Title</th><th width='15%' style='text-align: center'>Category</th><th width='5%' style='text-align: center'>Seller's ID</th><th width='15%' style='text-align: center'>Seller's Name</th><th width='10%' style='text-align: center'>Status</th><th width='10%' style='text-align:center'>Price</th><th style='text-align:center'>Date Added</th></tr>";
					while ($item = mysql_fetch_array($deleted_items)) {
						$item_id = $item['item_id'];
						$user_id = $item['user_id'];
						$mem = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = '$user_id'"));
						$added_date = strtotime($item['added_date']); $date_added = date('m/d/y h:i a', $added_date);	
						$html .= "<tr><td><input type='checkbox' name='items[]' value=$item_id></td><td align='center'>".$item_id."</td><td align='center'>".$item['silo_id']."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_item&id=".$item['id']."' target='_blank'>".$item['title']."</a></td><td align='center'>".$item['category']."</td><td align='center'>".$user_id."</td><td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_user&id=".$mem['id']."' target='_blank'>".$mem['fname']." ".$mem['lname']."</a></td><td align='center'>".$item['status']."</td><td align='center'>".money_format('%(#10n', floatval($item['price']))."</td><td align='center'>".$date_added."</tr>";
					}
					$html .= "</table>";
					echo $html;
					}

					echo "<br/><button type='submit' value='Delete' class='round'>Delete</button>";
					echo "</form>";		
				}
				
				//MEMBERS
				else if ($view == 'members') {
					if (param_post('delete') == 'users') {
						$users_to_delete = param_post('users');
						$in_clause = implode(',', $users_to_delete);
						$delBuyers = mysql_query("DELETE FROM buyer WHERE user_id IN ($in_clause)");
						$delFavs = mysql_query("DELETE FROM favorites WHERE user_id IN ($in_clause)");
						$delFeeds = mysql_query("DELETE FROM feed WHERE user_id IN ($in_clause)");
						$delFlags = mysql_query("DELETE FROM flag_radar WHERE user_id IN ($in_clause)");
						$delItems = mysql_query("DELETE FROM items WHERE user_id IN ($in_clause)");
						$delNotifs = mysql_query("DELETE FROM notifications WHERE user_id IN ($in_clause)");
						$delPWs = mysql_query("DELETE FROM password_reset WHERE user_id IN ($in_clause)");
						$delSellers = mysql_query("DELETE FROM seller_cleared WHERE user_id IN ($in_clause)");
						$delSilos = mysql_query("DELETE FROM silos WHERE admin_id IN ($in_clause)");
						$delMembs = mysql_query("DELETE FROM silo_membership WHERE user_id IN ($in_clause)");
						$delPrivs = mysql_query("DELETE FROM silo_private WHERE user_id IN ($in_clause)");
						$delUsers = mysql_query("DELETE FROM users WHERE user_id IN ($in_clause)");
						$delPCs = mysql_query("DELETE FROM user_paycodes WHERE user_id IN ($in_clause)");
						$delSess = mysql_query("DELETE FROM user_sessions WHERE user_id IN ($in_clause)");
					}
					echo "<br/><form name='members' id='members' action='index.php?view=members' onSubmit=\"return confirm('Delete these user(s)?')\" method='post'>";
					echo "<input type='hidden' name='delete' value='users'>";
					$users = mysql_query("SELECT * FROM users ORDER BY user_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>User ID</th><th width='15%' style='text-align: center'>E-mail</th><th width='10%' style='text-align: center'>Name</th><th width='10%' style='text-align: center'>Phone</th><th width='30%' style='text-align: center'>Address</th><th width='5%' style='text-align: center'>Status</th><th width='8%' style='text-align: center'>Join Date</th><th width='5%' style='text-align: center'>Sold</th><th width='5%' style='text-align: center'>Listings</th><th width='10%' style='text-align:center'>Silos</th><th style='text-align:center'>Admin</td></tr>";
					while ($user = mysql_fetch_array($users)) {
						$user_id = $user['user_id'];
						$j_date = strtotime($user['joined_date']); $joined_date = date('m/d/y', $j_date);	
						$sold = mysql_fetch_array(mysql_query("SELECT SUM(price) FROM items WHERE user_id = '$user_id' AND status = 'sold'"));
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND user_id=$user_id"));
						$silos = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos WHERE admin_id = '$user_id'"));
						
						$html .= "<tr><td><input type='checkbox' name='users[]' value=$user_id></td><td align='center'>$user_id</td><td align='center'>".$user['email']."<td align='center'><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_user&id=".$user['id']."' target='_blank'>".$user['fname']." ".$user['lname']."</a></td><td align='center'>".$user['phone']."<td align='center'>".$user['address']."</td><td align='center'>".$user['status']."<td align='center'>".$joined_date."</td><td align='center'>".money_format('%(#10n', floatval(".$sold[0]."))."</td><td align='center'>".$listings[0]."</td><td align='center'>".$silos[0]."</td><td align='center'>".$user['admin']."</tr>";
					}
					$html .= "</table><br/>";
					echo $html;		
					echo "<button type='submit' value='Delete' class='round'>Delete</button>";
					echo "</form>";
				}
				
				//SILOS
				else if ($view == 'silos') {
					if (param_post('delete') == 'silos') {
						$silos_to_delete = param_post('silos');
						$in_clause = implode(',', $silos_to_delete);
						$delFlag = mysql_query("DELETE FROM flag_radar WHERE silo_id IN ($in_clause)");
						$delSiloFlag = mysql_query("DELETE FROM flag_silo WHERE silo_id IN ($in_clause)");
						$delSilos = mysql_query("DELETE FROM silos WHERE silo_id IN ($in_clause)");

						$delItems = mysql_query("DELETE FROM items WHERE silo_id IN ($in_clause)");
						$delSilos = mysql_query("DELETE FROM silos WHERE silo_id IN ($in_clause)");
						$delMembs = mysql_query("DELETE FROM silo_membership WHERE silo_id IN ($in_clause)");
						$delPriv = mysql_query("DELETE FROM silo_private WHERE silo_id IN ($in_clause)");
						$delThank = mysql_query("DELETE FROM silo_thank WHERE silo_id IN ($in_clause)");
						$delVouch = mysql_query("DELETE FROM vouch WHERE silo_id IN ($in_clause)");
					}
					if (param_post('task') == 'markPaid') {
						$paid_status = param_post('paid');
						$silo_id = param_post('silo_id');
						$paid = mysql_query("UPDATE silos SET status = 'completed', paid = '$paid_status' WHERE silo_id = '$silo_id'");
						if ($paid_status == "yes") { $notif = new Notification(); $notif->SiloPaid($silo_id); }
						header('Location: '.$_SERVER['REQUEST_URI']);
						exit;
					}
					echo "<br><a href='".ACTIVE_URL."index.php?task=create_silo_admin'>Create an Official Silo</a>";
					echo "<br/><form name='silos' id='silos' action='index.php?view=silos' onsubmit=\"return confirm('Delete these silo(s)?')\" method='post'>";
					echo "<input type='hidden' name='delete' value='silos'>";
					$checkRadar = mysql_num_rows(mysql_query("SELECT * FROM silos WHERE type = 'flagged'"));
					if ($checkRadar) {					
					echo "<h2>Flagged silos</h2>";
					$today = date("Y-m-d")."";
					$silos = mysql_query("SELECT * FROM flag_radar WHERE type = 'silo' ORDER BY silo_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>Silo ID</th><th width='20%' style='text-align: center'>Silo Name</th><th width='5%' style='text-align: center'>Type</th><th width='10%' style='text-align: center'>Category</th><th width='8%' style='text-align: center'>Employee ID</th><th width='5%' style='text-align: center'>Admin ID</th><th width='8%' style='text-align: center'>Admin Name</th><th width='8%' style='text-align: center'>Phone</th><th width='8%' style='text-align: center'>E-mail</th><th width='10%' style='text-align: center'>Goal</th><th width='5%' style='text-align:center'>Complete</th><th width='5%' style='text-align:center'>Listings</th><th width='7%' style='text-align:center'>Ends</th></tr>";
					while ($silo = mysql_fetch_array($silos)) {
						$silo_id = $silo['silo_id'];						
						$admin = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id=".$silo['admin_id']));
						$pct = round(floatval($silo['collected'])*100.0/floatval($silo['goal']),1);
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND silo_id=$silo_id"));
						$ends_in = floor((strtotime($silo['end_date']) - strtotime($today))/(60*60*24));
						$html .= "<tr><td><input type='checkbox' name='silos[]' value=$silo_id></td><td align='center'>$silo_id</td><td align='center'><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_silo&id=".$silo['id']."' target='_blank'>".$silo['name']."</a></td><td align='center'>".$silo['silo_type']."<td align='center'>".$silo['type']."</td><td align='center'>".$silo['employee_discount']."</td><td align='center'>".$admin['user_id']."<td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_user&id=".$admin['id']."' target='_blank'>".$admin['fname']." ".$admin['lname']."</a></td><td align='center'>".$admin['phone']."</td><td align='center'>".$admin['email']."</td><td align='center'>".money_format('%.0n', $silo['goal'])."</td><td align='center'>".$pct."%</td><td align='center'>".$listings[0]."</td><td align='center'>$ends_in days</td></tr>";
						$html .= "<form action='' method='POST' name='unflag_".$silo_id."'>
								<input type='hidden' name='task' value='unflag'>
								<input type='hidden' name='silo_id' value='$silo_id'>
								<input type='submit' value='Reactivate'></input>
							</form></td></tr>";
					}
					$html .= "</table>";
					echo $html;
					}

					echo "<input type='hidden' name='task' value='delete'>";					
					echo "<h2>Active silos</h2>";
					$today = date("Y-m-d")."";
					$silos = mysql_query("SELECT * FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE status = 'active' ORDER BY silo_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>Silo ID</th><th width='20%' style='text-align: center'>Silo Name</th><th width='5%' style='text-align: center'>Type</th><th width='10%' style='text-align: center'>Category</th><th width='8%' style='text-align: center'>Employee ID</th><th width='5%' style='text-align: center'>Admin ID</th><th width='8%' style='text-align: center'>Admin Name</th><th width='8%' style='text-align: center'>Phone</th><th width='8%' style='text-align: center'>E-mail</th><th width='10%' style='text-align: center'>Goal</th><th width='5%' style='text-align:center'>Complete</th><th width='5%' style='text-align:center'>Listings</th><th width='7%' style='text-align:center'>Ends</th></tr>";
					while ($silo = mysql_fetch_array($silos)) {
						$silo_id = $silo['silo_id'];						
						$admin = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id=".$silo['admin_id']));
						$pct = round(floatval($silo['collected'])*100.0/floatval($silo['goal']),1);
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND silo_id=$silo_id"));
						$ends_in = floor((strtotime($silo['end_date']) - strtotime($today))/(60*60*24));
						$html .= "<tr><td><input type='checkbox' name='silos[]' value=$silo_id></td><td align='center'>$silo_id</td><td align='center'><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_silo&id=".$silo['id']."' target='_blank'>".$silo['name']."</a></td><td align='center'>".$silo['silo_type']."<td align='center'>".$silo['type']."</td><td align='center'>".$silo['employee_discount']."</td><td align='center'>".$admin['user_id']."<td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_user&id=".$admin['id']."' target='_blank'>".$admin['fname']." ".$admin['lname']."</a></td><td align='center'>".$admin['phone']."</td><td align='center'>".$admin['email']."</td><td align='center'>".money_format('%.0n', $silo['goal'])."</td><td align='center'>".$pct."%</td><td align='center'>".$listings[0]."</td><td align='center'>$ends_in days</td></tr>";
					}
					$html .= "</table>";
					echo $html;

					$checkPending = mysql_num_rows(mysql_query("SELECT * FROM silos WHERE status = 'pending'"));
					if ($checkPending) {
					echo "<h2>Pending silos (Pledge an item first)</h2>";
					$silos = mysql_query("SELECT * FROM silos WHERE status = 'pending' ORDER BY silo_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='4%' style='text-align: center'>Silo ID</th><th width='5%' style='text-align: center'>Admin ID</th><th width='10%' style='text-align: center'>Admin Name</th><th width='10%' style='text-align: center'>Admin Phone</th><th width='15%' style='text-align: center'>Admin E-mail</th><th width='5%' style='text-align: center'>Referer ID</th><th width='10%' style='text-align: center'>Referer Name</th><th width='10%' style='text-align: center'>Referer Phone</th><th width='15%' style='text-align: center'>Referer E-mail</th><th style='text-align: center'>Expires on</th></tr>";
					while ($silo = mysql_fetch_array($silos)) {
						$silo_id = $silo['silo_id'];						
						$admin = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = ".$silo['admin_id']));
						$item = mysql_fetch_array(mysql_query("SELECT * FROM items WHERE silo_id = '$silo_id'"));
						$refer = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = '$item[user_id]'"));
						$end_date = strtotime($silo['end_date']); $exp_date = date('m/d/y h:i a', $end_date);	
						$html .= "<tr><td><input type='checkbox' name='silos[]' value=$silo_id></td><td align='center'>$silo_id</td><td align='center'>".$admin['user_id']."<td align='center'><a class='bluelink' href='".ACTIVE_URL."index.php?task=view_user&id=".$admin['id']."' target='_blank'>".$admin['fname']." ".$admin['lname']."</a></td><td align='center'>".$admin['phone']."</td><td align='center'>".$admin['email']."</td><td align='center'>".$refer['user_id']."</td><td align='center'>".$refer['fname']." ".$refer['lname']."</td><td align='center'>".$refer['phone']."</td><td align='center'>".$refer['email']."</td><td align='center'>$exp_date</td></tr>";
					}
					$html .= "</table>";
					echo $html;
					}							
					
					$checkEnded = mysql_num_rows(mysql_query("SELECT * FROM silos WHERE status = 'inert' OR status = 'completed'"));
					if ($checkEnded) {
					echo "<h2>Ended silos</h2>";
					$today = date("Y-m-d")."";
					$silos = mysql_query("SELECT * FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE status = 'latent' OR status = 'completed' ORDER BY paid, id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='5%'>ID #</th><th width='25%'>Silo Name</th><th width='16%'>Category</th><th width='8%'>Admin Name</th><th width='8%'>Phone</th><th width='8%'>E-mail</th><th width='10%' style='text-align:right'>Goal</th><th width='5%' style='text-align:center'>%</th><th width='5%'>Listings</th><th width='10%' style='text-align:center'>Paid?</th></tr>";
					while ($silo = mysql_fetch_array($silos)) {
						$silo_id = $silo['silo_id'];						
						$admin = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id=".$silo['admin_id']));
						$sql = "SELECT SUM(price) FROM items WHERE deleted_date = 0 AND silo_id = $silo_id AND status = 'Sold'";
						$s2 = mysql_fetch_row(mysql_query($sql));
						$pct = round(floatval($s2[0])*100.0/floatval($silo['goal']),1);
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND silo_id=$silo_id"));
						$ends_in = floor((strtotime($silo['end_date']) - strtotime($today))/(60*60*24));
						$paid = $silo['paid'];
						$opt = ""; $other_opt = "";
						$opt .= '<option value="' . $paid . '">' . $paid . '</option>';
						if ($paid == "no") { $other_opt .= '<option value="yes">yes</option>'; } else { $other_opt .= '<option value="no">no</option>'; }
						$html .= "<tr><td><input type='checkbox' name='silos[]' value=$silo_id></td><td>$silo_id</td><td><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_silo&id=".$silo['id']."' target='_blank'>".$silo['name']."</a></td><td>".$silo['type']."</td><td><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_user&id=".$admin['id']."' target='_blank'>".$admin['fname']." ".$admin['lname']."</a></td><td>".$admin['phone']."</td><td>".$admin['email']."</td><td align=right>".money_format('%(#10n', floatval($silo['goal']))."</td><td align=center>".$pct."</td><td align=center>".$listings[0]."</td><td align=center>";
							$pur = mysql_fetch_array(mysql_query("SELECT expired_date  FROM item_purchase WHERE silo_id = '$silo_id' ORDER BY expired_date DESC LIMIT 1"));
							$expired = strtotime($pur[0]);
							$now = strtotime("now");
							$exp_date = date("D, M jS (g:i a)", $expired);
						if ($expired < $now) {
							$html .= "<form action='' method='POST'>
								<input type='hidden' name='task' value='markPaid'>
								<input type='hidden' name='silo_id' value='$silo_id'>
    								<select name='paid' onchange='this.form.submit()'>
									".$opt."
									".$other_opt."
    								</select>
							</form></td></tr>";
						} else { $html .= "Pay after: ".$exp_date; }
					}
					$html .= "</table>";					
					echo $html;
					}							
					
					echo "<br/><button type='submit' value='Delete' class='round'>Delete</button>";
					echo "</form>";	
				}elseif($view === "radar"){
				$checkSilos = mysql_num_rows(mysql_query("SELECT * FROM flag_radar WHERE type = 'silo'  AND notify = 1"));
				$checkItems = mysql_num_rows(mysql_query("SELECT * FROM flag_radar WHERE type = 'item' AND notify = 1"));
				$updNotify = mysql_query("UPDATE flag_radar SET notify = 0");
				if ($checkSilos) {
					echo "<br/><form name='silos' id='silos' action='index.php?view=silos' method=post>";
					echo "<input type='hidden' name='task' value='delete'>";					
					echo "<h2>Flagged silos</h2>";
					$today = date("Y-m-d")."";
					$silos = mysql_query("SELECT *, flag_radar.status AS flagStatus FROM flag_radar INNER JOIN silos USING (silo_id) WHERE type = 'silo'");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='5%'>ID #</th><th width='30%'>Silo Name</th><th width='16%'>Status</th><th width='8%'>Admin</th><th width='7%' style='text-align:center'>Override?</th></tr>";
					while ($silo = mysql_fetch_array($silos)) {
						$silo_id = $silo['silo_id'];						
						$admin = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id=".$silo['admin_id']));
						$sql = "SELECT SUM(price) FROM items WHERE deleted_date = 0 AND silo_id = $silo_id AND status = 'Sold'";
						$s2 = mysql_fetch_row(mysql_query($sql));
						$pct = round(floatval($s2[0])*100.0/floatval($silo['goal']),1);
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND silo_id=$silo_id"));
						$html .= "<tr><td><input type='checkbox' name='silos[]' value=$silo_id></td><td>$silo_id</td><td><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_silo&id=".$silo['id']."' target='_blank'>".$silo['name']."</a></td><td>".$silo['flagStatus']."</td><td><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_user&id=".$admin['id']."' target='_blank'>".$admin['fname']." ".$admin['lname']."</a></td><td align=center>";
						$html .= "<form action='' method='POST' name='unflag_".$silo_id."'>
								<input type='hidden' name='task' value='unflag'>
								<input type='hidden' name='silo_id' value='$silo_id'>
								<input type='submit' value='Reactivate'></input>
							</form></td></tr>";
					}
					$html .= "</table>";
					echo $html;
				}
				if ($checkItems) {
					$items = mysql_query("SELECT *, flag_radar.status AS flagStatus FROM flag_radar INNER JOIN items USING (item_id)");
					echo "<h2>Flagged items</h2>";
					$flagged_items = mysql_query("SELECT * FROM flag_radar WHERE type = 'item' ORDER BY item_id");				
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='5%'>ID #</th><th width='30%'>Title</th><th width='16%'>Status</th><th width='8%'>Seller</th><th width='7%' style='text-align:center'>Override?</th></tr>";
					while ($getItem = mysql_fetch_array($flagged_items)) {
						$item = mysql_fetch_array(mysql_query("SELECT * FROM items INNER JOIN item_categories USING (item_cat_id) WHERE item_id = '$getItem[item_id]' ORDER BY item_id"));
						$item_id = $item['item_id'];
						$mem = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = ".$item['user_id']));						
						$html .= "<tr><td><input type='checkbox' name='items[]' value=$item_id></td><td>#$item_id</td><td><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_item&id=$item_id' target='_blank'>".$item['title']."</a></td><td>".$getItem['status']."</td><td><a class='bluelink'  href='".ACTIVE_URL."index.php?task=view_user&id=".$mem['id']."' target='_blank'>".$mem['fname']." ".$mem['lname']."</a></td><td align=center>";
						$html .= "<form action='' method='POST' name='unflag_".$item_id."'>
								<input type='hidden' name='task' value='item unflag'>
								<input type='hidden' name='item_id' value='$item_id'>
								<input type='submit' value='Reactivate'></input>
							</form></td></tr>";
					}
					$html .= "</table>";
					echo $html;
				}
				if (!$checkSilos && !$checkItems) { echo "<br><br><center><b>There are currently no items or silos on the flag radar. Yahoo!</b></center>"; }
				}
			?>
			</div>
		</div>
	</body>
<?php
}
?>
</html>
