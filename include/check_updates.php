<?php

// Check if any sios have exceeded their goal or if the time has surpassed
$silo_check = mysql_query("SELECT silo_id FROM silos WHERE status = 'active' AND ((end_date <= NOW()) OR (goal <= collected))");
	while ($silo = mysql_fetch_array($silo_check)) {
		$silo_id_close = $silo['silo_id'];
		$updSilo = mysql_query("UPDATE silos SET status = 'latent' WHERE silo_id = '$silo_id_close'");
		$updItem = mysql_query("UPDATE items SET status = 'inert', end_date = NOW() WHERE silo_id = '$silo_id_close'");
		$notif = new Notification(); 
		$notif->SiloEnded($silo_id_close);
	}

// Check if there are any offers that are pending that haven't been declined/accepted
$offerCheck = mysql_query("SELECT item_id FROM offers WHERE expired_date < NOW() AND status = 'pending'");
	if (mysql_num_rows($offerCheck) > 0) {
		while ($offer = mysql_fetch_array($offerCheck)) {
			$item_id = $offer['item_id'];
			$updOffer = mysql_query("UPDATE offers SET status = 'declined' WHERE item_id = '$item_id'");
			$updItem = mysql_query("UPDATE items SET status = 'pledged' WHERE status = 'offer' AND item_id = '$item_id'");
		}
	}

// Check is there are any purchases that are pending that haven't been completed in the time frame and refund the user
$purchaseCheck = mysql_query("SELECT item_id FROM item_purchase WHERE expired_date < NOW() AND status = 'pending'");
	if (mysql_num_rows($purchaseCheck) > 0) {
		while ($pur = mysql_fetch_array($purchaseCheck)) {
			$item_id = $pur['item_id'];
			$updPurchase = mysql_query("UPDATE item_purchase SET status = 'refunded' WHERE item_id = '$item_id'");
			$updItem = mysql_query("UPDATE items SET status = 'pledged' WHERE item_id = '$item_id'");

			include('braintree/refunds.php');
		}
	}

// Check if the familiarity index is active or -- then check it if it needs to suspend any silos/items
if (FAM_INDEX_KILL == "on") {
	$lowFamIndexCheck = mysql_query("SELECT silo_id FROM flag_radar WHERE status = 'vouch' AND expired_date < NOW()");
	if (mysql_num_rows($lowFamIndexCheck) > 0) {
		while ($famIndex = mysql_fetch_array($lowFamIndexCheck)) {
			$silo_id = $famIndex['silo_id'];
			$updFlagRadar = mysql_query("UPDATE flag_radar SET status = 'cancel' WHERE silo_id = '$silo_id'");
			$updFlagSilo = mysql_query("UPDATE flag_silo SET active = 0 WHERE silo_id = '$silo_id'");
			$killSilo = mysql_query("UPDATE silos SET status = 'flagged' WHERE silo_id = '$silo_id'");
		}
	}
}

// Check and send out the 24 hour e-mail if the user hasn't gotten it yet
$email24HourCheck = mysql_query("SELECT user_id, email FROM users WHERE info_emails < 2 AND joined_date + INTERVAL 1 DAY <= NOW()");
$num24Hour = mysql_num_rows($email24HourCheck);
	if ($num24Hour > 0) {
		while ($user = mysql_fetch_array($email24HourCheck)) {
		$subject = "How ".SHORT_URL." works";
		$message = "<br> We think people spend too much time learning how to use things that should be simple. So, we want to tell you about ".SITE_NAME.", but we'll keep it short. We know how busy you are. <br><br>";
		$message .= "<b>How ".SITE_NAME." Works</b> <br><br>";
		$message .= "<ol>
				<li>A silo administrator creates a silo. This takes all of 5 minutes.</li>
				<li>That silo administrator promotes the silo, using Facebook and our email contact tools (AOL, Hotmail, Yahoo!, Gmail, etc.), and off-line flyers (printed out from the console). Hint: off-line promotion is best done at an event.</li>
				<li>Supporters spread the word about the silo (to get still more item donors, and shoppers) and donate items themselves, listing them online, from the comfort of their homes, like that other site that sells used stuff in your area. Note: the local public can donate items to a public silo, but private silos are viewable/joinable by invite only.</li>
				<li>Buyers pay online, and are issued a Voucher, which acts as cash. Sellers are given a Voucher Key, which proves a Voucher is authentic. Potential buyers and sellers are provided contact information for each other, and given one week in which to close a sale, which happens <b>when the seller enters the buyer's Voucher into the site</b>.</li>
				<li>The silo ends, we send the money raised to each silo administrator, and ask them to 'Thank' those who donated items.</li>
				</ol>";
		$message .= "We invite you to communicate your questions and concerns with us.<br><br>";
		$message .= "Thank You, and Happy Fundraising, <br><br><br>";
		$message .= "Zackery West <br><br> CEO, ".SITE_NAME." LLC";
		email_with_template($user['email'], $subject, $message);
		mysql_query("UPDATE users SET info_emails = 2 WHERE user_id = '$user[user_id]'");
		}
	}

// Check and send out the 36 hour e-mail if the user hasn't gotten it yet
$email36HourCheck = mysql_query("SELECT user_id, email FROM users WHERE info_emails < 3 AND joined_date + INTERVAL 36 HOUR <= NOW()");
$num36Hour = mysql_num_rows($email36HourCheck);
	if ($num36Hour > 0) {
		while ($user = mysql_fetch_array($email36HourCheck)) {
		$subject = "How to get the most out of ".SHORT_URL;
		$message = "<br> <b>Item Donors</b> <br><br>";
		$message .= "<ul>
				<li>Be sure to investigate your silo and its administrator. Never donate an item to a silo you are unfamiliar with.</li>
				<li>You can donate more than one item! Also, many silos are able to offer tax-deductible receipts. Think of big ticket items when donating.</li>
				<li>Spread the word. The people you tell (via Facebook, or with our email contact tools), can become both shoppers and potential donors to the cause you support.</li>
				</ul>";
		$message .= "<b>silo Administrators</b> <br><br>";
		$message .= "<ul>
				<li>Spread the word, far and wide!</li>
				<li>Log into your ".SITE_NAME." account, and select 'manage silo' to view our on- and off-line promotion tools.</li>
				<li>Manage your silo; respond to inquiries about your organization.</li>
				<li>Remember to upload photos substantiating how your money raised was spent. If it is not a specific purchase or project, photos of your group will do. And of course, offer a warm message to those who helped you raise money.</li>
				</ul>";
		$message .= "<b>Shoppers</b> <br><br>";
		$message .= "<ul>
				<li>Act quickly to pick up your item. Remember your Voucher for a given item. If you have multiple items, you want to be sure to remember which is associated with. Finally: never provide your Voucher over the telephone or agree to receive a shipped item. Your Voucher is like cash. If it goes to the seller, and you don�t collect your item, there�s nothing we can do to help you.</li>
				</ul>";
		$message .= "We invite you to communicate your questions and concerns with us.<br><br>";
		$message .= "Thank You, and Happy Fundraising, <br><br><br>";
		$message .= "Zackery West <br><br> CEO, ".SITE_NAME." LLC";
		email_with_template($user[email], $subject, $message);
		mysql_query("UPDATE users SET info_emails = 3 WHERE user_id = '$user[user_id]'");
		}
	}

// Check and Send to requested silos that have expired and not been created
$pendingSiloCheck = mysql_query("SELECT silo_id, admin_id FROM silos WHERE status = 'pending' AND end_date <= NOW()");
$numPendingSilos = mysql_num_rows($pendingSiloCheck);
	if ($numPendingSilos > 0) {
		while ($silo = mysql_fetch_array($pendingSiloCheck)) {
		$admin = new User($silo['admin_id']);
		$item = mysql_fetch_array(mysql_query("SELECT item_id, title, user_id FROM items WHERE silo_id = '$silo[silo_id]'"));
		$refer = new User($item['user_id']);
		
		$subjectAdmin = "Pending silo deleted";
		$messageAdmin = "<h3>Your pending silo has been removed</h3>";
		$messageAdmin .= "Since you did not respond within 2 weeks, the silo that was pending in your name has been removed. You will no longer be able to use the item that was pledged for your silo, but you can still create a new silo at any time.";
		$messageAdmin .= "You will not receive any more notifications from ".SITE_NAME." if you chose not to create a silo. We thank you for your interest with us!";
		email_with_template($admin->email, $subjectAdmin, $messageAdmin);

		$subjectRefer = "Pending item deleted";
		$messageRefer = "<h3>Your item has been removed</h3>";
		$messageRefer .= "Since the silo administator, with the e-mail <b>".$admin->email."</b>, did not respond within 2 weeks, the silo that was pending with your pledged item has been removed. Although your item didn't get pledged to this silo, you can still re-pledge your item at any time to a different silo.";
		$messageRefer .= "Thanks for trying to help promote ".SITE_NAME."! We appreciate your support and we wish you the best.";
		email_with_template($refer->email, $subjectRefer, $messageRefer);

		$delFeed = mysql_query("DELETE FROM feed WHERE item_id = '$silo[silo_id]'");
		$delItem = mysql_query("DELETE FROM items WHERE item_id = '$item[item_id]'");
		$delSilo = mysql_query("DELETE FROM silos WHERE silo_id = '$silo[silo_id]'");
		$delMemb = mysql_query("DELETE FROM silo_membership WHERE user_id = '$refer->user_id'");
		$delVouch = mysql_query("DELETE FROM vouch WHERE user_id = '$refer->user_id'");
		}
	}
?>