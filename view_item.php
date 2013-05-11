<?php
	$item_id = param_get('id');
	require("include/autoload.class.php");
	if ($item_id == '') {
		echo "<script>window.location = 'index.php';</script>";			
	}
	else {		
		$item = new Item($item_id);
		$item->Update();
		$seller = $item->owner;
		$silo = $item->silo;
		$item = new Item($item_id);

		$silo_id = urlencode($item->silo_id);
		$item_long = urlencode($item->longitude);
		$item_lat = urlencode($item->latitude);
		$user_long = urlencode($user['longitude']);
		$user_lat = urlencode($user['latitude']);
		$distBuyerSeller = $item->getDistance($item_long, $item_lat, $user_long, $user_lat);
		$checkClosed = mysql_num_rows(mysql_query("SELECT * FROM silos WHERE silo_id = '$silo_id' AND status != 'active'"));
		if ($checkClosed > 0) { $closed_silo = "_closed"; }

	if (param_post('fav') == 'add to favorites') {
		$user_id = param_post('user_id');
		$item_id = param_post('item_id');

			$Item = new Item();
			$Item->user_id = $user_id;
			$Item->item_id = $item_id;
			$Item->AddFav();
	}

	if (param_post('fav') == 'remove from favorites') {
		$user_id = param_post('user_id');
		$item_id = param_post('item_id');

			$Item = new Item();
			$Item->user_id = $user_id;
			$Item->item_id = $item_id;
			$Item->RemoveFav();
	}

	if (param_post('offer') == 'send offer') {
		$item_id = param_post('item_id');
		$buyer_id = param_post('buyer_id');
		$seller_id = param_post('seller_id');
		$amount = param_post('amount');

			$Item = new Item();
			$Item->item_id = $item_id;
			$Item->buyer_id = $buyer_id;
			$Item->seller_id = $seller_id;
			$Item->amount = $amount;
			$Item->NewOffer();
	}

	if (param_post('offer') == 'cancel') {
		$item_id = param_post('item_id');
		$buyer_id = param_post('buyer_id');
		$seller_id = param_post('seller_id');

			$Item = new Item();
			$Item->item_id = $item_id;
			$Item->buyer_id = $buyer_id;
			$Item->seller_id = $seller_id;
			$Item->RemoveOffer();
	}

	$user_id = $_SESSION['user_id'];
	$isSeller = mysql_num_rows(mysql_query("SELECT * FROM items WHERE user_id = '$user_id' AND item_id = '$item->item_id'"));
	$isAdmin = mysql_num_rows(mysql_query("SELECT * FROM silos WHERE admin_id = '$user_id' AND silo_id = '$silo->silo_id'"));

	$fav = mysql_num_rows(mysql_query("SELECT * FROM favorites WHERE user_id = '$user_id' AND item_id = '$item->item_id'"));
	$itemOffer = mysql_num_rows(mysql_query("SELECT * FROM items WHERE item_id = '$item->item_id' AND status = 'offer'"));
	$itemFlagged = mysql_num_rows(mysql_query("SELECT * FROM flag_item WHERE user_id = '$user_id' AND item_id = '$item->item_id'"));

	$offerUser = mysql_fetch_array(mysql_query("SELECT status, amount FROM offers WHERE buyer_id = '$user_id' AND item_id = '$item->item_id'"));
	$offerStatus = $offerUser['status'];
	$offerAmount = $offerUser['amount'];

	$offerItem = mysql_fetch_array(mysql_query("SELECT status, expired_date FROM offers WHERE item_id = '$item->item_id' ORDER BY id DESC"));
	$statusO = $offerItem[0];
	$expO = strtotime($offerItem[1]); $offerExp = date('g:i a F j, Y', $expO);

	if ($offerStatus == 'accepted') { $price = $offerAmount; } else { $price = $item->price; }
?>

<div class="login" id="offer" style="width: 300px;">
	<div id="offer_drag" style="float:right">
		<img id="offer_exit" src="images/close.png"/>
	</div>
	<div>
		<form action="" method="POST">
			<input type="hidden" name="item_id" value="<?=$item->item_id?>">
			<input type="hidden" name="buyer_id" value="<?=$user_id?>">
			<input type="hidden" name="seller_id" value="<?=$item->user_id?>">
			<h2>Enter your offer below:</h2>
			$<input onclick=this.value="" type="text" value="0.00" name="amount">
			<br/><br>	
			<button type="submit" name="offer" value="send offer">Send offer</button>
		</form>
	</div>
</div>

<div class="login" id="offerp" style="width: 300px;">
	<div id="offerp_drag" style="float:right">
		<img id="offerp_exit" src="images/close.png"/>
	</div>
	<div>
		<form action="" method="POST">
			<input type="hidden" name="item_id" value="<?=$item->item_id?>">
			<input type="hidden" name="buyer_id" value="<?=$user_id?>">
			<input type="hidden" name="seller_id" value="<?=$item->user_id?>">
			<h2><font color="FF642F">Offer: $<?=$offerAmount?></font></h2>
			<h2>You are only allowed one offer per item. Are you sure you want to cancel it?</h2>
			<br/><br>	
			<button type="submit" name="offer" value="cancel">Yes, cancel my offer</button>
		</form>
	</div>
</div>

<div class="login" id="ioffer" style="width: 300px;">
	<div id="ioffer_drag" style="float:right">
		<img id="ioffer_exit" src="images/close.png"/>
	</div>
	<div>
		<?php if ($statusO == "pending") { ?>
		<h2>Another user has already sent an offer for this item. If the seller doesn't take any action, it will expire on <?=$offerExp?>. <br><br>
		You can still purchase this item for the original asking price at any time.</h2>
		<?php } elseif ($statusO == "accepted") { ?>
		<h2>Another user has made an offer and the seller has accepted it. No more offers will be able to be made for this item. <br><br>
		You can still purchase this item at the seller's original asking price. Buy it soon!</h2>
		<?php } ?>
	</div>
</div>

<div class="login" id="dist" style="width: 300px;">
	<div id="dist_drag" style="float:right">
		<img id="dist_exit" src="images/close.png"/>
	</div>
	<div>
		<h2>You are too far away from the seller. Please find an item closer to your current location.</h2>
	</div>
</div>

<div class="login" id="flagged" style="width: 300px;">
	<div id="flagged_drag" style="float:right">
		<img id="flagged_exit" src="images/close.png"/>
	</div>
	<div>
		<h2>You have flagged this item already. You can only flag each item once.</h2>
	</div>
</div>

<div class="login" id="closed_silo" style="width: 300px;">
	<div id="closed_silo_drag" style="float:right">
		<img id="closed_silo_exit" src="images/close.png"/>
	</div>
	<div>
		<h2>This function has been disabled because the silo is no longer active.</h2>
		<button type="button" onclick="document.getElementById('overlay').style.display='none';document.getElementById('closed_silo').style.display='none';">Okay</button>
	</div>
</div>

<div class="contact_seller" id="contact_admin">
	<div id="contact_admin_drag" style="float: right">
		<img id="contact_admin_exit" src="images/close.png"/>
	</div>
	<div>
		<form name="contact_admin_form" id="contact_admin_form" method="POST">
			<h2>Contact Admin</h2>
			<p>Silo <b><?php echo $silo->name; ?></b></p>
			<div id="contact_admin_status"></div>			
			<table>
				<tr>
					<td valign="top">
						<b>Email</b>
					</td>
					<td>
						<input type="text" name="contact_email" id="contact_email" onfocus="select();" style="width:300px;" 
						value=<?php echo $_SESSION['is_logged_in'] != 1 ? "" : $current_user['email'];?> >
					</td>
				</tr>
				<tr>
					<td valign="top">
						<b>Subject</b>
					</td>
					<td>
						<input type="text" name="contact_subject" id="contact_subject" onfocus="select();" style="width:300px;"/>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<b>Inquiry<br/>/Offer</b>
					</td>
					<td>
						<textarea style='width: 300px; height: 200px' name="inquiry" id="inquiry"></textarea>
					</td>
				</tr>
			</table>
			<br/>			
			<button type="button" id="contact_admin_button">Send</button>
			<button type="button" onclick="document.getElementById('overlay').style.display='none';document.getElementById('contact_admin').style.display='none';">Cancel</button>
		</form>
		<script>
			$("#contact_admin_button").click(function(event) {	
				document.getElementById('overlay').style.display='none';
				document.getElementById('contact_admin').style.display='none';
				$.post(<?php echo "'".API_URL."'"; ?>, 
					{	
						request: 'email_silo_admin',
						silo_id: <?php echo $silo->silo_id; ?>,
						email: document.getElementById('contact_email').value,
						subject: document.getElementById('contact_subject').value,
						content: document.getElementById('inquiry').value
					}, 
					function (xml) {
						$(xml).find('response').each(function (){
							if ($(this).text() == 'successful') 
								alert("Your inquiry has been sent!");
							else
								alert("Failed to send your inquiry!");
							document.getElementById('contact_email').value = "<?php echo $_SESSION['is_logged_in'] != 1 ? "" : $current_user['email'];?>";
							document.getElementById('contact_subject').value = "";
							document.getElementById('inquiry').value = "";							
						});
					}
				);
			});
		</script>		
	</div>
</div>

<div class="contact_seller" id="contact_seller">
	<div id="contact_seller_drag" style="float: right">
		<img id="contact_seller_exit" src="images/close.png"/>
	</div>
	<div>
		<form name="contact_seller_form" id="contact_seller_form" method="POST">
			<h2>Contact Seller</h2>
			<div id="contact_seller_status"></div>			
			<table>
				<tr>
					<td valign="top">
						<b>Email</b>
					</td>
					<td>
						<input type="text" id="contact_email" style="width:300px;" 
						value=<?php echo $_SESSION['is_logged_in'] != 1 ? "" : $current_user['email'];?> >
					</td>
				</tr>
				<tr>
					<td valign="top">
						<b>Subject</b>
					</td>
					<td>
						<input type="text" id="contact_subject" style="width:300px;"/>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<b>Inquiry<br/>/Offer</b>
					</td>
					<td>
						<textarea style='width: 300px; height: 200px' id="inquiry"></textarea>
					</td>
				</tr>
			</table>
			<br/>			
			<button type="button" id="contact_seller_button">Send</button>
			<button type="button" onclick="document.getElementById('overlay').style.display='none';document.getElementById('contact_seller').style.display='none';">Cancel</button>
		</form>
		<script>
			$("#contact_seller_button").click(function(event) {	
				$.post(<?php echo "'".API_URL."'"; ?>, 
					{	
						request: 'email_seller',
						item_id: <?php echo "'$item_id'"; ?>,
						email: $('#contact_email').val(),
						subject: $('#contact_subject').val(),
						content: $('#inquiry').val()
					}, 
					function (xml) {
						$(xml).find('response').each(function (){
							if ($(this).text() == 'successful') { 
								document.getElementById('overlay').style.display='none';
								document.getElementById('contact_seller').style.display='none';
								
								alert("Your inquiry has been sent!");								
							}
							else
								alert("Failed to send your inquiry!");
							document.getElementById('contact_email').value = "<?php echo $_SESSION['is_logged_in'] != 1 ? "" : $current_user['email'];?>";
							document.getElementById('contact_subject').value = "";
							document.getElementById('inquiry').value = "";							
						});
					}
				);
			});
		</script>		
	</div>
</div>

<div class="spacer"></div>

<table>
	<tr>
		<td valign='top' width="610px">
			<div id="items" style="width: 610px;">	
				<table>
					<tr>
						<td colspan="2">
							<table width="100%" class="titleHeading">
								<tr>
									<td align="left">
										<b style="font-size: 14px;"><?php echo $item->title; ?></b>
									</td>
									<td align="right">
										<b style="font-size: 14px; color:#f60; text-align:right">$<?php echo $price; ?></b>							
									</td>
								</tr>
							</table>
							<br/>					
						</td>
					</tr>
					<tr>
						<td valign="top" width="290px" class="item-details">
							<?php
								if ($item->photo_file_1 != '')
									echo "<img src='uploads/items/".$item->photo_file_1."?".$item->last_update."' width=280px height=210px id='current_item_photo'/> &nbsp;&nbsp;";
							?>
							<br/>
							<?php
								for ($i = 1; $i <=4; $i++) {
									$fn = $item->getPhoto($i);
									if ($fn == "'no_image.jpg'")
										$fn = "no_image.jpg";
									if ($fn != '' && $fn != "no_image.jpg")
										echo "<img src='uploads/items/$fn' width=65px height=49px onclick=\"document.getElementById('current_item_photo').src='uploads/items/$fn';\" /> &nbsp;";
								}									
							?>							
						</td>
						<td valign="top" width="340px">
							<div style="text-align:justify; margin-left: 5px; height: 240px;">
								<?php 
									$desc = preg_replace("/\n/","<br>",html_entity_decode($item->description));
									echo $desc;
								?>
							<div style="margin-top: 15px;">
							<table cellpadding="2px">
								<tr>
									<td><b>ID:</b></td>
									<td><?php echo $item->id;?></td>
								</tr>
								<tr>
									<td><b>Seller:</b></td>
									<td><a href="index.php?task=view_user&id=<?php echo $seller->id;?>"><font color="#2f8dcb"><?php echo $seller->fname?></font></a></td>
								</tr>
								<tr>
									<td><b>Listed on:</b></td>
									<td><?php echo $item->added_date;?></td>
								</tr>
							</table>
							</div>
					<div style="margin-top: 15px; z-index: 200;">
					<table width="100%">
						<tr>
							<?php if (!$closed_silo) { ?>
							<td rowspan="2"><a href="mailto:?Subject=<?=ACTIVE_URL?>index.php?task=view_item%26<?php echo $item->id;?>&Body=Check out this item on <?=SITE_NAME?>!"><img src="images/mail-icon.png"></a></td>
							<td rowspan="2"><img src="images/facebook.jpg" onclick='postToFeed();'/></td>
							<?php } if ($closed_silo) { ?>
							<td><div class="voucherText"><font size="1">&nbsp;</font></div></td>
							<?php } elseif (!$_SESSION['is_logged_in']) { ?>
							<td class="click_me" onclick="javascript:popup_show('login', 'login_drag', 'login_exit', 'screen-center', 0, 0);"><div class="voucherText"><font size="1">Flag this item</font></div></td>
							<?php } elseif (!$distBuyerSeller) { ?>
							<td class="click_me" onclick="javascript:popup_show('dist', 'dist_drag', 'dist_exit', 'screen-center', 0, 0);"><div class="voucherText"><font size="1">Flag this item</font></div></td>
							<?php } elseif ($itemFlagged) { ?>
							<td class="click_me" onclick="javascript:popup_show('flagged', 'flagged_drag', 'flagged_exit', 'screen-center', 0, 0);"><div class="voucherText"><font size="1">Item Flagged</font></div></td>
							<?php } elseif (!$isSeller) { ?>
							<td class="click_me" onclick="javascript:popup_show('flag_box', 'login_drag', 'login_exit', 'screen-center', 0, 0);"><div class="voucherText"><font size="1">Flag this item</font></div></td>
							<?php } ?>
									<td align="center">
											<?php if ($closed_silo) {} elseif (!$_SESSION['is_logged_in']) {
											?>
											<button onclick="popup_show('login', 'login_drag', 'login_exit', 'screen-center', 0, 0);">Buy This Item</button>
											<?php
											} elseif ($isSeller) {} elseif (!$distBuyerSeller) {
											?>
											<button onclick="popup_show('dist', 'dist_drag', 'dist_exit', 'screen-center', 0, 0);">Buy This Item</button>
											<?php
											} elseif ($addInfo_full) {
											?>
											<button onclick="popup_show('addInfo_item', 'addInfo_item_drag', 'addInfo_item_exit', 'screen-center', 0, 0);">Buy This Item</button>
											<?php
											} else {
											?>
											<button onclick="window.location = 'index.php?task=payment&id=<?php echo $item->id;?>'">Buy This Item</button>
											<?php
											}
											?>
									</td>
						</tr>
						<tr>
							<?php if ($closed_silo) { ?>
							<td>&nbsp;</td>
							<?php } elseif (!$_SESSION['is_logged_in']) { ?>
							<td class="click_me" onclick="javascript:popup_show('login', 'login_drag', 'login_exit', 'screen-center', 0, 0);"><img height="40px" src="img/flag.png" alt="Flag this item" /></td>
							<?php } elseif (!$distBuyerSeller) { ?>
							<td class="click_me" onclick="javascript:popup_show('dist', 'dist_drag', 'dist_exit', 'screen-center', 0, 0);"><img height="40px" src="img/flag.png" alt="Flag this item" /></td>
							<?php } elseif ($itemFlagged) { ?>
							<td class="click_me" onclick="javascript:popup_show('flagged', 'flagged_drag', 'flagged_exit', 'screen-center', 0, 0);"><img height="40px" src="img/flag.png" alt="Flag this item" /></td>
							<?php } elseif (!$isSeller || $closed_silo) { ?>
							<td class="click_me" onclick="javascript:popup_show('flag_box', 'login_drag', 'login_exit', 'screen-center', 0, 0);"><img height="40px" src="img/flag.png" alt="Flag this item" /></td>
							<?php } ?>

							<td align="center">
								<div class="voucherText"><b>
								<?php if ($closed_silo) {} elseif (!$_SESSION['is_logged_in']) { ?>
									<a onclick="popup_show('login', 'login_drag', 'login_exit', 'screen-center', 0, 0);">offer another amount
									<?php } elseif ($addInfo_full) { ?>
									<a onclick="popup_show('addInfo_item', 'addInfo_item_drag', 'addInfo_item_exit', 'screen-center', 0, 0);">offer another amount
									<?php } elseif ($isSeller) {} elseif ($offerStatus == 'declined' || $offerStatus == 'canceled') { ?>
									<font color="red">offer <?=$offerStatus?> </font>
									<?php } elseif ($offerStatus == 'pending') { ?>
									<div class="offer"><a href="javascript:popup_show('offerp', 'offerp_drag', 'offerp_exit', 'screen-center', 0, 0);">offer pending</div>
									<?php } elseif ($offerStatus == 'accepted') { ?>
									<div style="color: green">offer accepted!</div>
									<?php } elseif ($itemOffer) { ?>
									<a href="javascript:popup_show('ioffer', 'ioffer_drag', 'ioffer_exit', 'screen-center', 0, 0);">another offer pending
									<?php } elseif (!$distBuyerSeller) { ?>
									<a href="javascript:popup_show('dist', 'dist_drag', 'dist_exit', 'screen-center', 0, 0);">offer another ammount
									<?php } else { ?>
									<a href="javascript:popup_show('offer', 'offer_drag', 'offer_exit', 'screen-center', 0, 0);">offer another amount
									<?php } ?></div></a></b><br>

								<div class="voucherText">
								<?php if ($closed_silo) { echo "The silo this item belongs to is closed. <br> Items in a closed silo are not interactive."; } elseif (!$_SESSION['is_logged_in']) { ?>
									<a onclick="popup_show('login', 'login_drag', 'login_exit', 'screen-center', 0, 0);">add to favorites
								<?php } elseif ($isSeller) { echo "You are the seller of this item. <br> Some functions are hidden."; } elseif ($fav) { ?>
									<form method="post" action="">
										<input type="hidden" name="user_id" value="<?=$user_id?>">
										<input type="hidden" name="item_id" value="<?=$item->item_id?>">
										<input style="color: red; background: #fff;" type="submit" name="fav" value="remove from favorites">
									</form>
									<?php } elseif (!$distBuyerSeller) { ?>
									<a href="javascript:popup_show('dist', 'dist_drag', 'dist_exit', 'screen-center', 0, 0);">add to favorites
									<?php } else { ?>
									<form method="post" action="">
										<input type="hidden" name="user_id" value="<?=$user_id?>">
										<input type="hidden" name="item_id" value="<?=$item->item_id?>">
										<input class="voucherText" type="submit" name="fav" value="add to favorites">
									</form>
									<?php } ?>
								</div></a><br>
							</td>
						</tr>
					</table>
							</div>						
							</div>

						</td>						
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td colspan="2">
							<b>Seller Availability:</b> <?php if($item->avail == "") { echo "The seller did not list specific availability times"; }
							else { echo "<i>\"$item->avail\"</i>"; } ?><br><br>
							<div id="map_canvas" style="width: 600px; height: 345px;" class="map-canvas"></div>
							<div id='fb-root'></div>
							<script src='http://connect.facebook.net/en_US/all.js'></script>
							<?php
								$url = ACTIVE_URL."index.php?task=view_item&id=".$item->id;
								$photo_url = ACTIVE_URL.'uploads/items/'.$item->photo_file_1.'?'.$item->last_update;
								$name = $item->title.": $".$item->price;
								$caption = "Help Silo: ".$silo->name;
								$description = substr($item->description, 0, 200)."...";
							?>

							<script> 
							 	FB.init({
							            appId      : <?php echo "'".FACEBOOK_ID."'"; ?>,
							            status     : true, 
							            cookie     : true,
							            xfbml      : true
							          });
							  	function postToFeed() {
							    	FB.ui({
							      		method: 'feed',
							      		link: "<?php echo $url; ?>",
							      		picture: "<?php echo $photo_url; ?>",
							      		name: "<?php echo $name; ?>",
										caption: "<?php echo $caption; ?>",
							      		description: "<?php echo $description; ?>"
							    	});
							  	}
							</script>																
						</td>
					</tr>				
				</table>
				<table>
					<tr>
						<td><?php include("include/UI/flag_box.php"); ?></td>
					</tr>
				</table>	
			</div>
		</td>
		<td style='width: 10px'>
		</td>
		<td width="340px" align="left">
				<div class="voucherText" style="margin-top: 7px; margin-left: 5px; font-size: 11pt;" align="left">You purchasing this item helps:</div><br>
<table class='siloInfo<?=$closed_silo?>' style="margin-top: -7px;" >
	<tr>
		<td class="titleHeading">
			<a href='index.php?task=view_silo&id=<?=$silo->id;?>'><?=$silo->name?><?php if($closed_silo) { echo " (Closed)"; }?></a>
		</td>
	<tr>
	<tr class="infoSpacer"></tr>
	<tr>
		<td>
					<?php
						$admin = $silo->getAdmin();
						$admin_name = $admin->fname;
						$collected = $silo->getCollectedAmount();
						$pct = round($collected*100.0/floatval($silo->goal));
						if ($pct == 100) { $radius = "border-radius: 4px;"; } else { $radius = "border-top-left-radius: 4px; border-bottom-left-radius: 4px"; }
						
						$c_user_id = $current_user['user_id'];
					?>
			<a href='index.php?task=view_silo&id=<?=$silo->id;?>'><img src="<?php echo 'uploads/silos/'.$silo->photo_file.'?'.$silo->last_update;?>" width='250px' class="siloImg"/>
			<div class="siloImgOverlay">
			<div class="progress-bg"><div class="progress-bar" style="width: <?=$pct?>%; <?=$radius?>"></div></div>
			goal: $<?=number_format($silo->goal)?> (<?=$pct?>%)
			</div></a>
		</td>
	</tr>
	<tr class="infoSpacer"></tr>
	<tr>
		<td class="siloInnerInfo<?=$closed_silo?>">
			<a href='index.php?task=view_silo&view=members&id=<?=$silo->id;?>'><?=$silo->getTotalMembers();?></a>
			<a href='index.php?task=view_silo&view=items&id=<?=$silo->id;?>'><?=$silo->getTotalItems();?></a>
			<?=$silo->getDaysLeft();?>
			<div style="padding-top: 10px;"></div>
		<?php if (!$tax_ded) { $tax = "<b><u>not</u></b>"; } ?>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt; text-align: left"><b>Purpose:</b> <?=$silo->getPurpose();?></div>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt; text-align: left">This Administrator has <?=$tax?> provided an EIN number for this fundraiser, and donations are <?=$tax?> tax-deductable.</div>
		</td>
	</tr>
	<tr class="infoSpacer"></tr>
	<tr>
		<td class="siloInnerInfo<?=$closed_silo?>">
			<span class="floatL">
				<img src="<?php echo 'uploads/members/'.$admin->photo_file.'?'.$admin->last_update;?>" class="siloImg" width='100px'/><br>
				<a style="color: #2f8dcb;" class='buttonEmail' href="<?php if($closed_silo) { echo "javascript:popup_show('closed_silo', 'closed_silo_drag', 'closed_silo_exit', 'screen-center', 0, 0);"; } else { echo "javascript:popup_show('contact_admin', 'contact_admin_drag', 'contact_admin_exit', 'screen-center', 0, 0);"; }?>">Email Admin.</a>
			</span>
			<div align="left">
			<span class="infoDetails">
				Administrator:<br>
				<span class="notBold"><?=$admin_name?></span><br>
				Official Address:<br>
				<span class="notBold"><?=$silo->address?></span><br>
				Telephone:<br>
				<span class="notBold"><?=$silo->phone_number?></span>
			</span>
			</div>
		</td>
	</tr>
	<tr class="infoSpacer"></tr>
	<tr>
		<td class="siloInnerInfo<?=$closed_silo?>">
			<div align="left">
			<span class='voucher'>Donate only to local causes that you know or have researched!</span><br><br>
			<?php include('include/UI/flag_box_silo.php'); ?>
			<center>Silo ID: <?=$silo->id?></center>
		</div>
		</td>
	</tr>
</table>

		</td>
	</tr>
</table>
					
<?php
	}
?>

<script  type="text/javascript">

function initialize() {
var styles = [
	{
		featureType: 'water',
		elementType: 'all',
		stylers: [
			{ hue: '#84BFE5' },
			{ saturation: 37 },
			{ lightness: -7 },
			{ visibility: 'on' }
		]
	},{
		featureType: 'landscape.man_made',
		elementType: 'all',
		stylers: [
			{ hue: '#FFFFFF' },
			{ saturation: -100 },
			{ lightness: 100 },
			{ visibility: 'on' }
		]
	},{
		featureType: 'road.highway',
		elementType: 'all',
		stylers: [
			{ hue: '#FFC92F' },
			{ saturation: 100 },
			{ lightness: -7 },
			{ visibility: 'on' }
		]
	},{
		featureType: 'road.arterial',
		elementType: 'all',
		stylers: [
			{ hue: '#FFE18C' },
			{ saturation: 100 },
			{ lightness: 2 },
			{ visibility: 'on' }
		]
	}
];
lat = <?=$item->latitude?>;
long = <?=$item->longitude?>;

var myLocation = new google.maps.LatLng(lat, long);
var options = {
	mapTypeControlOptions: {
		mapTypeIds: [ 'Styled']
	},
	center: myLocation,
	zoom: 11,
	maxZoom: 13,
	mapTypeId: 'Styled'
};

var div = document.getElementById('map_canvas');
var map = new google.maps.Map(div, options);
var styledMapType = new google.maps.StyledMapType(styles, { name: 'Item Location' });
map.mapTypes.set('Styled', styledMapType);


infoWindow = new google.maps.InfoWindow();
    infoWindow.setOptions({
        content: "<div align='center'><img src='uploads/items/<?=$item->photo_file_1?>?<?=$item->last_update?>' width=100px id='current_item_photo'/></div>",
        position: myLocation,
    });

infoWindow.open(map);
}

function loadScript() {
  var script = document.createElement("script");
  script.type = "text/javascript";
  script.src = "http://maps.googleapis.com/maps/api/js?key=AIzaSyAPWSU0w9OpPxv60eKx70x3MM5b7TtK9Og&sensor=false&callback=initialize";
  document.body.appendChild(script);
}

window.onload = loadScript;

</script>

<div class="login" id="addInfo_item" style="width: 300px;">
	<div id="addInfo_item_drag" style="float:right">
		<img id="addInfo_item_exit" src="images/close.png"/>
	</div>
	<div>
		<h2>Please complete your profile.</h2>
		You have some information in your profile that has not been filled out yet. Please complete your profile. This will allow you to use the rest of <?=SITE_NAME?>.com <br><br>
		<button type="button" onclick="document.location='index.php?task=my_account&redirect=view_item&id=<?=$item->id?>'">Finish it now</button>
		<button type="button" onclick="document.getElementById('overlay').style.display='none';document.getElementById('addInfo_item').style.display='none';">Later</button>
	</div>
</div>