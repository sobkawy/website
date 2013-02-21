<h1>Payment Form</h1>
<?php
	$process = param_post('process');
	$id = param_get('id');
	if ($id == '')
		$id = param_post('id');
	$item = new Item($id);
	if ($process != '') {
		require_once 'braintree/lib/Braintree.php';
		Braintree_Configuration::environment('sandbox');
		Braintree_Configuration::merchantId('3g3ms64nnp4jthgj');
		Braintree_Configuration::publicKey('b7pqj735f7zpv843');
		Braintree_Configuration::privateKey('wq85yksj4vp6zdfq');
		$cc = $_POST['credit_card'];
		$billing = $cc['billing_address'];
		//die(print_r($billing));
		$result = Braintree_Transaction::sale(array(
		    'amount' => $_POST['amount'],
		    'creditCard' => array(
		        'number' => $cc['number'],
		        'expirationDate' => $cc['expiration_month']."/".$cc['expiration_year'],
				'cardholderName' => $cc['cardholder_name'],
				'cvv' => $cc['cvv']
		    ),
			'customer' => array(
				'id' => $cc['customer_id']
			),
			'billing' => array (
				'firstName' => $billing['first_name'],
				'lastName' => $billing['last_name'],
				'streetAddress' => $billing['street_address'],
				'region' => $billing['region'],
				'postalCode' => $billing['postal_code']
			),
			'options' => array(
			    'storeInVault' => true
			)
		));

		if ($result->success) {
		    $paykey = strtoupper($result->transaction->id);
		    
		    $ItemPurchase = new ItemPurchase();
		    $ItemPurchase->paykey = $paykey;
		    $ItemPurchase->item_id = $item->item_id;
		    $ItemPurchase->silo_id = $item->silo_id;
		    $ItemPurchase->user_id = $_SESSION["user_id"];
		    $ItemPurchase->ip = Common::RemoteIp();
		    $ItemPurchase->amount = $_POST["amount"];
		    $ItemPurchase->status = "pending";
		    if($ItemPurchase->Save()){
		    	$item->status = "paid";
		    	$item->Save();
		    }
		    
			$buyer_email = "<h2>What Happens Now?</h2>";
			$buyer_email .= "Congratulations! You have made payment for <b>".$item->title."</b>, which helps the silo ".$item->silo->name.". Now you have one week to meet the seller and collect your item, completing the purchase.  Your PayKey, below, <b>acts as cash</b>, and is provided to, or withheld from, the seller, to make or decline a purchase.<br/><br/>";
			$buyer_email .= "<b>The PayKey for item \"".$item->title."\" is ".$paykey."</b><br/><br/>";
			$buyer_email .= "The seller has received a PayLock that ensures your PayKey is genuine. <b>Treat your PayKey like cash;</b> provide it to the seller only if and after you have inspected, accepted, and have taken <b>physical custody</b> of, your item. <b>The seller in entering your PayKey into the site proves to us that you received your item.</b>  Do not provide your PayKey to the seller via email or over the telephone, or via any means but in person, after inspecting an item.  Do not bring cash to transact your purchase.  Do not return your item to the seller after you have provided your PayKey.<br/>";
			$buyer_email .= "<h2>STEP 1: Contact the seller and arrange a time and place to meet to inspect the item.</h2>";
			$buyer_email .= "<b>If you are making multiple purchases, be sure to keep your PayKeys <b>organized</b> and associated with the correct items and sellers!</b><br/>";
			$buyer_email .= "<h3>Seller Contact Information:</h3>";
			$buyer_email .= "Item Name: <b>".$item->title."</b><br/>";
			$buyer_email .= "Fullname: <b>".$item->owner->fullname."</b><br/>";
			$buyer_email .= "Email Address: <b>".$item->owner->email."</b><br/>";
			$buyer_email .= "Telephone Number: <b>".$item->owner->phone."</b><br/><br/>";
			$buyer_email .= "<h2>STEP 2: Meet, inspect the item.  Decline or accept it.  Bring your PayKey!</h2>";
			$buyer_email .= "<ul><li><b>Accept</b> the item by providing your PayKey to the seller.</li>";
			$buyer_email .= "<li><b>Decline</b> the item by withholding your PayKey from the seller; after one week, if a seller does not enter your PayKey into siloz.com, the pending transaction will cancel.  Or, log in to your siloz.com account and select 'decline' for the pending item. After a few days, you will be refunded your payment, less small fees.</li></ul>";
			echo $buyer_email;
			email_with_template($current_user['email'], "Notification: Information for item \"".$item->title."\"", $buyer_email);
			
			$seller_email = "<h2>What Happens Now?</h2>";
			$seller_email .= "Congratulations! ".$current_user['username']." has made payment for ".$item->title.", and must meet with you to inspect, and complete (accept or decline), the transaction.  The potential buyer has received a PayKey, which meets the criteria of your PayLock (so you know it’s authentic).  You must enter the PayKey into the item plate in your siloz.com account screen to prove you surrendered the item, and for payment to go to your silo.  The potential buyer’s PayKey is like cash; once it is provided to you, you are obligated to provide the buyer with the item. <br/><br/>";
			$seller_email .= "<b>The PayKey for item \"".$item->title."\" is ".$paykey."</b><br/><br/>";
			$seller_email .= "Do not surrender your item without receiving a PayKey that meets the descriptive criteria of your PayLock. <b>Do not wait</b> until the 72 deadline to transact your sale approaches, or until after the 72 hour window to transact your sale has closed, to enter a valid PayKey.  If you cannot get Internet or cellular data service to enter your PayKey without approaching the 72 hour deadline, <b>do not surrende your item to the buyer</b>.  We offer no remedy if you surrender an item and do not enter a valid PayKey within 72 hours.<br/>";
			$seller_email .= "<h2>STEP 1: Contact the potential buyer and arrange a time and place to meet to inspect the item.</h2>";
			$seller_email .= "<b>If you are making multiple sales, be sure to keep your PayLocks organized and associated with the correct items and potential buyers! </b><br/>";
			$seller_email .= "<h3>Potential Buyer Contact Information:</h3>";
			$seller_email .= "Fullname: <b>".$current_user['fullname']."</b><br/>";
			$seller_email .= "Email Address: <b>".$current_user['email']."</b><br/>";
			$seller_email .= "Telephone Number: <b>".$current_user['phone']."</b><br/><br/>";
			$seller_email .= "<h2>STEP 2: Meet, allow the potential buyer to inspect the item.</h2>";
			$seller_email .= "<ul><li>The potential buyer, alone, has the option to <b>accept</b> the item by providing you with the PayKey.  Once you are in receipt of a PayKey that meets the descriptive criteria of your PayLock, enter it into the item plate in your siloz.com account. </li>";
			$seller_email .= "<li>After 72 hours, if a you do not enter a valid PayKey, the pending transaction will <b>cancel</b>.</li></ul>";
			// email_with_template($item->owner->email, "Notification: Potential buyer for item \"".$item->title."\"", $seller_email);
			email_with_template("robert@aronedesigns.com", "Notification: Potential buyer for item \"".$item->title."\"", $seller_email);
			
		} else if ($result->transaction) {
		    print_r("Error processing transaction:");
			echo "<h2 style='color: red'>Error: ".$result->message."</h2>";
			echo "<h2 style='color: red'>Code: ".$result->transaction->processorResponseCode."</h2>";
			echo "<h2 style='color: red'>Text: ".$result->transaction->processorResponseText."</h2>";		
			$process = '';
		} else {			
			echo "<h2 style='color: red'>Error: ".$result->message."</h2>";
			$process = '';
		}
	}
	if ($process == '') {
?>
<form action="index.php" method="post">
	<input type="hidden" name="task" value="payment"/>
	<input type="hidden" name="process" value="true"/>
	<input type="hidden" name="amount" value="<?php echo $item->price;?>"/>
	<input type="hidden" name="id" value="<?php echo $id;?>"/>
	<input type="hidden" name="credit_card[customer_id]" value="<?php echo $current_user->id;?>"/>
	
	<table cellpadding="10px">
		<tr>
			<td valign="top" width="200px">
			<h3>Item Detail</h3>
			<table>
				<tr>
					<td>ID<td>
					<td><b><?php echo $id;?></b></td>
				</tr>
				<tr>
					<td>Name<td>
					<td><b><?php echo $item->title;?></b></td>
				</tr>
				<tr>
					<td>Price<td>
					<td><b><?php echo "$".$item->price;?></b></td>
				</tr>
			</table>				
			</td>
			<td valign="top">
			<h3>Credit Card</h3>		
			<table>
				<tr>
					<td>Credit Holder Name<td>
					<td><input type="text" style="width: 150px" name="credit_card[cardholder_name]"/></td>
				</tr>
				<tr>
					<td>Credit Number<td>
					<td><input type="text" style="width: 150px" name="credit_card[number]" value="5105105105105100"/></td>
				</tr>
				<tr>
					<td>CVV Code<td>
					<td><input type="text" style="width: 30px" name="credit_card[cvv]"/></td>
				</tr>
				<tr>
					<td>Expiration Month<td>
					<td>
						<select name="credit_card[expiration_month]">
							<option value="01">01</option>
							<option value="02">02</option>
							<option value="03">03</option>
							<option value="04">04</option>
							<option value="05">05</option>
							<option value="06">06</option>
							<option value="07">07</option>
							<option value="08">08</option>
							<option value="09">09</option>
							<option value="10">10</option>
							<option value="11">11</option>
							<option value="12">12</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Expiration Year here<td>
					<td>
						<select name="credit_card[expiration_year]">
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
						</select>
					</td>
				</tr>
			</table>
			</td>
			<td valign="top">
				<h3>Billing Address</h3>
				<table>
					<tr>
						<td>First Name</td>
						<td><input type="text" style="width: 200px" name="credit_card[billing_address][first_name]"/></td>
					</tr>
					<tr>
						<td>Last Name</td>
						<td><input type="text" style="width: 200px" name="credit_card[billing_address][last_name]"/></td>
					</tr>
					<tr>
						<td>Street Address</td>
						<td><input type="text" style="width: 200px" name="credit_card[billing_address][street_address]"/></td>
					</tr>
					<tr>
						<td>State</td>
						<td><input type="text" style="width: 50px" name="credit_card[billing_address][region]"/></td>
					</tr>
					<tr>
						<td>Postal Code</td>
						<td><input type="text" style="width: 50px" name="credit_card[billing_address][postal_code]"/></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="3" align="center">
				<input type="submit" name="submit" value="Submit"/>
			</td>
		</tr>
	</table>
</form>
<?php
}
?>
