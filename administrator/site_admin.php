<?php
	ini_set("include_path", "/var/www/vhosts/stage.james.siloz.com/httpdocs/website"); 
	require_once('utils.php');
	require_once('config.php');
	setlocale(LC_MONETARY, 'en_US');
	
	$conn = mysql_connect(DB_HOST, DB_USERNAME, DB_PASSWORD);	
	mysql_select_db(DB_NAME, $conn);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml"	xml:lang="en">
	<head>
		<title>SiloZ - Site Admin</title>
		<link rel="stylesheet" type="text/css" href="css/admin.css" />	
		<link rel="stylesheet" tyle="text/css" href="css/jquery-ui-1.8.16.css"/>
	    <script type="text/javascript" src="js/jquery-1.6.4.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.16.min.js"></script> 				
		<script type="text/javascript" src="js/popup-window.js"></script>	  
	    <script type="text/javascript" src="js/jquery.placeholder.js"></script>		
	    <script type="text/javascript" src="js/jquery.jconfirmation.js"></script>				
		<script type="text/javascript" src="js/jquery.truncator.js"></script>
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
	<body style="background: #fff">
		<div style="margin: 50px;">
			<div id="header">
				<button type="button" style="<?php echo ($view == 'stats' ? 'border-color: #f60' : ''); ?>" onclick="window.location='site_admin.php?view=stats'">Site Statistics</button>
				<button type="button" style="<?php echo ($view == 'members' ? 'border-color: #f60' : ''); ?>" onclick="window.location='site_admin.php?view=members'">Members</button>
				<button type="button" style="<?php echo ($view == 'items' ? 'border-color: #f60' : ''); ?>" onclick="window.location='site_admin.php?view=items'">Items</button>
				<button type="button" style="<?php echo ($view == 'silos' ? 'border-color: #f60' : ''); ?>" onclick="window.location='site_admin.php?view=silos'">Silos</button>
				<button type="button" style="<?php echo ($view == 'radar' ? 'border-color: #f60' : ''); ?>" onclick="window.location='site_admin.php?view=radar'">Radar Notifications</button>
			</div>
			<div id="main">
			<?php
			
				//STATS
				if ($view == 'stats') {
					echo "<br/>";
					$today = date("Y-m-d")."";					
					$html = "<table>";
					$active_community_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE end_date >= '$today' AND type = 'Community'"));
					$active_personal_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE end_date >= '$today' AND type = 'Personal'"));

					$community_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE type = 'Community'"));
					$personal_silos_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE AND type = 'Personal'"));
					$personal_silos_count = $personal_silos_count[0] == null ? 0 : $personal_silos_count[0];
					$html .= "<tr><td>Total number of <b>Community</b> silos:</td><td align=right><b> $active_community_silos_count[0] (active) / $community_silos_count[0] (total) </b></td></tr>";
					$html .= "<tr><td>Total number of <b>Personal</b> silos:</td><td align=right><b> $active_personal_silos_count[0] (active) / $personal_silos_count (total)</b></td></tr>";
					
					$users_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM users"));
					$html .= "<tr><td>Total number of members:</td><td align=right><b>$users_count[0]</b></td></tr>";
					
					$active_listings_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0"));
					$listing_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items"));
					$html .= "<tr><td>Total number of listings:</td><td align=right><b> $active_listings_count[0] (active) / $listing_count[0] (total)</b></td></tr>";
					
					$listing_average_value = mysql_fetch_array(mysql_query("SELECT AVG(price) FROM items"));
					$html .= "<tr><td>Average value of listings:</td><td align=right><b>".money_format('%(#10n', round($listing_average_value[0],2))."</b></td></tr>";
					
					$donations_count = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM donations"));
					$donations_average_value = mysql_fetch_array(mysql_query("SELECT AVG(amount) FROM donations"));
					$html .= "<tr><td>Total number of donations:</td><td align=right><b> $donations_count[0]</b></td></tr>";
					$html .= "<tr><td>Average value of donations:</td><td align=right><b>".money_format('%(#10n', round($donations_average_value[0],2))."</b></td></tr>";

					$community_funds_received = mysql_fetch_array(mysql_query("SELECT SUM(price) FROM items WHERE status = 'Funds Received' AND silo_id IN (SELECT silo_id FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE type = 'Community')"));
					$personal_funds_received = mysql_fetch_array(mysql_query("SELECT SUM(price) FROM items WHERE status = 'Funds Received' AND silo_id IN (SELECT silo_id FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE type = 'Personal')"));
					$html .= "<tr><td>Total funds received - <b>Community</b> silos:</td><td align=right><b>".money_format('%(#10n', round($community_funds_received[0],2))."</b></td></tr>";
					$html .= "<tr><td>Total funds received - <b>Personal</b> silos:</td><td align=right><b>".money_format('%(#10n', round($personal_funds_received[0],2))."</b></td></tr>";

					$html .= "</table>";
					echo $html;
				}
				
				//ITEMS
				else if ($view == 'items') {
					echo "<h2>Active items</h2>";
					$active_items = mysql_query("SELECT * FROM items INNER JOIN item_categories USING (item_cat_id) WHERE deleted_date = 0 ORDER BY item_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='7%'>Item #</th><th width='40%'>Title</th><th width='15%'>Category</th><th width='15%'>Seller</th><th width='10%'>Status</th><th width='10%' style='text-align:center'>Price</th></tr>";
					while ($item = mysql_fetch_array($active_items)) {
						$item_id = $item['item_id'];
						$mem = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = ".$item['user_id']));						
						$html .= "<tr><td><input type='checkbox' name='items[]' value=$item_id></td><td>#$item_id</td><td><a class='bluelink'  href='index.php?task=view_item&id=$item_id'>".$item['title']."</a></td><td>".$item['category']."</td><td><a class='bluelink'  href='index.php?task=view_user&id=".$mem['user_id']."'>".$mem['username']."</a></td><td>".$item['status']."</td><td align=right>".money_format('%(#10n', floatval($item['price']))."</td></tr>";
					}
					$html .= "</table>";
					echo $html;					

					echo "<h2>Deleted items</h2>";					
					$deleted_items = mysql_query("SELECT * FROM items INNER JOIN item_categories USING (item_cat_id) WHERE deleted_date <> 0 ORDER BY item_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='7%'>Item #</th><th width='40%'>Title</th><th width='15%'>Category</th><th width='15%'>Seller</th><th width='10%'>Status</th><th width='10%' style='text-align:center'>Price</th></tr>";
					while ($item = mysql_fetch_array($deleted_items)) {
						$item_id = $item['item_id'];
						$mem = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id = ".$item['user_id']));						
						$html .= "<tr><td><input type='checkbox' name='items[]' value=$item_id></td><td>#$item_id</td><td><a class='bluelink'  href='index.php?task=view_item&id=$item_id'>".$item['title']."</a></td><td>".$item['category']."</td><td><a class='bluelink'  href='index.php?task=view_user&id=".$mem['user_id']."'>".$mem['username']."</a></td><td>".$item['status']."</td><td align=right>".money_format('%(#10n', floatval($item['price']))."</td></tr>";
					}
					$html .= "</table>";
					echo $html;					
				}
				
				//MEMBERS
				else if ($view == 'members') {
					if (param_post('task') == 'delete') {
						$users_to_delete = param_post('users');
						$in_clause = implode(',', $users_to_delete);
						$sql1 = "DELETE FROM users WHERE user_id IN ($in_clause)";
						mysql_query($sql1);
						$sql2 = "DELETE FROM silos WHERE admin_id IN ($in_clause)";
						mysql_query($sql2);
						$sql3 = "DELETE FROM items WHERE user_id IN ($in_clause)";
						mysql_query($sql3);
						$sql4 = "DELETE FROM silo_membership WHERE user_id IN ($in_clause)";
						mysql_query($sql4);
					}
					echo "<br/><form name='members' id='members' action='site_admin.php?view=members' method=post>";
					echo "<input type='hidden' name='task' value='delete'>";
					$users = mysql_query("SELECT * FROM users ORDER BY user_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='7%'>Account #</th><th width='10%'>Username</th><th width='30%'>Address</th><th width='8%'>Since</th><th width='10%' style='text-align:right'>Sold</th><th width='10%' style='text-align:right'>Fees</th><th width='7%' style='text-align:center'>Listings</th><th width='5%' style='text-align:right'>Success</th><th width='5%' style='text-align:center'>SiloZ</th><th width='5%'>Flags</th></tr>";
					while ($user = mysql_fetch_array($users)) {
						$user_id = $user['user_id'];
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND user_id=$user_id"));
						$siloz = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM silos WHERE admin_id=$user_id"));
						
						$html .= "<tr><td><input type='checkbox' name='users[]' value=$user_id></td><td>$user_id</td><td><a class='bluelink'  href='index.php?task=view_user&id=$user_id'>".$user['username']."</a></td><td>".$user['address']."</td><td>".substr($user['joined_date'],0,10)."</td><td align=right>".money_format('%(#10n', floatval("5000"))."</td><td align=right>".money_format('%(#10n', floatval("200"))."</td><td align=center>".$listings[0]."</td><td align=center>77%</td><td align=center>".$siloz[0]."</td><td align=center>2</td></tr>";
					}
					$html .= "</table><br/>";
					echo $html;		
					echo "<button type='submit' value='Delete' class='round'>Delete</button>";
					echo "</form>";	
				}
				
				//SILOS
				else if ($view == 'silos') {
					if (param_post('task') == 'delete') {
						$silos_to_delete = param_post('silos');
						$in_clause = implode(',', $silos_to_delete);
						$sql2 = "DELETE FROM silos WHERE silo_id IN ($in_clause)";
						mysql_query($sql2);
						$sql3 = "DELETE FROM items WHERE silo_id IN ($in_clause)";
						mysql_query($sql3);
						$sql4 = "DELETE FROM silo_membership WHERE silo_id IN ($in_clause)";
						mysql_query($sql4);
					}
					echo "<br/><form name='silos' id='silos' action='site_admin.php?view=silos' method=post>";
					echo "<input type='hidden' name='task' value='delete'>";					
					echo "<h2>Active silos</h2>";
					$today = date("Y-m-d")."";
					$silos = mysql_query("SELECT * FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE end_date >= '$today' ORDER BY silo_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='5%'>ID #</th><th width='30%'>Silo Name</th><th width='19%'>Category</th><th width='8%'>Type</th><th width='8%'>Admin</th><th width='10%' style='text-align:right'>Goal</th><th width='5%' style='text-align:center'>%</th><th width='5%'>Listings</th><th width='7%' style='text-align:center'>Ends</th></tr>";
					while ($silo = mysql_fetch_array($silos)) {
						$silo_id = $silo['silo_id'];						
						$admin = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id=".$silo['admin_id']));
						$sql = "SELECT SUM(price) FROM items WHERE deleted_date = 0 AND silo_id = $silo_id AND status = 'Funds Received'";
						$s2 = mysql_fetch_row(mysql_query($sql));
						$pct = round(floatval($s2[0])*100.0/floatval($silo['goal']),1);
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND silo_id=$silo_id"));
						$ends_in = floor((strtotime($silo['end_date']) - strtotime($today))/(60*60*24));
						$html .= "<tr><td><input type='checkbox' name='silos[]' value=$silo_id></td><td>$silo_id</td><td><a class='bluelink'  href='index.php?task=view_silo&id=$silo_id'>".$silo['name']."</a></td><td>".$silo['subtype']." > ".$silo['subsubtype']."</td><td>".$silo['type']."</td><td><a class='bluelink'  href='index.php?task=view_user&id=".$admin['user_id']."'>".$admin['username']."</a></td><td align=right>".money_format('%(#10n', floatval($silo['goal']))."</td><td align=center>".$pct."</td><td align=center>".$listings[0]."</td><td align=center>$ends_in days</td></tr>";
					}
					$html .= "</table>";
					echo $html;										
					
					
					echo "<h2>Ended silos</h2>";
					$today = date("Y-m-d")."";
					$silos = mysql_query("SELECT * FROM silos INNER JOIN silo_categories USING (silo_cat_id) WHERE end_date < '$today' ORDER BY silo_id");
					$html = "<table id='alternate_table'><tr><th width='3%'></th><th width='5%'>ID #</th><th width='30%'>Silo Name</th><th width='19%'>Category</th><th width='8%'>Type</th><th width='8%'>Admin</th><th width='10%' style='text-align:right'>Goal</th><th width='5%' style='text-align:center'>%</th><th width='5%'>Listings</th><th width='7%' style='text-align:center'>Ends</th></tr>";
					while ($silo = mysql_fetch_array($silos)) {
						$silo_id = $silo['silo_id'];						
						$admin = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id=".$silo['admin_id']));
						$sql = "SELECT SUM(price) FROM items WHERE deleted_date = 0 AND silo_id = $silo_id AND status = 'Funds Received'";
						$s2 = mysql_fetch_row(mysql_query($sql));
						$pct = round(floatval($s2[0])*100.0/floatval($silo['goal']),1);
						$listings = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM items WHERE deleted_date = 0 AND silo_id=$silo_id"));
						$ends_in = floor((strtotime($silo['end_date']) - strtotime($today))/(60*60*24));
						$html .= "<tr><td><input type='checkbox' name='silos[]' value=$silo_id></td><td>$silo_id</td><td><a class='bluelink'  href='index.php?task=view_silo&id=$silo_id'>".$silo['name']."</a></td><td>".$silo['subtype']." > ".$silo['subsubtype']."</td><td>".$silo['type']."</td><td><a class='bluelink'  href='index.php?task=view_user&id=".$admin['user_id']."'>".$admin['username']."</a></td><td align=right>".money_format('%(#10n', floatval($silo['goal']))."</td><td align=center>".$pct."</td><td align=center>".$listings[0]."</td><td align=center>$ends_in days</td></tr>";
					}
					$html .= "</table>";					
					echo $html;
					echo "<br/><button type='submit' value='Delete' class='round'>Delete</button>";
					echo "</form>";	
				}
			?>
			</div>
		</div>
	</body>
</html>
