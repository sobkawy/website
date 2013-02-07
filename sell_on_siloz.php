<?php
if ($_SESSION['is_logged_in'] != 1) {
	echo "<script>create_silo_need_login();</script>";
}	
else {
	$user_id = $_SESSION['user_id'];	
	$user = new User($user_id);
	$title = "";
	$avail = "";
	$price = "";
	$item_cat_id = "0";
	$description = "";

	$err = "";
	if (param_post('submit') == 'Finish') {	
		$silo_id = param_post('silo_id');
		$silo = new Silo($silo_id);
		$title = param_post('title');
		$avail = param_post('avail');
		$price = param_post('price');
		$item_cat_id = param_post('item_cat_id');
		$description = param_post('description');
		$vouch_type_id = param_post('vouch');
		$address = param_post('address');
		$zip = param_post('zip');
		
		//test for form errors //comment added sept 8th 2012 james kenny
		if (strlen(trim($title)) == 0) {
			$err .= "Item title must not be empty. <br/>";
		}
		if (strlen(trim($price)) == 0) {
			$err .= "Item's price must not be empty. <br/>";
		}
		else if (!is_numeric($price)) {
			$err .= "Item's price is not a valid number.<br/>";
		}
		else if (floatval($price) < 0) {
			$err .= "Item's price is negative.<br/>";
		}
		else if (floatval($price) > 100000000) {
			$err .= "Item's price exceeds the allowed maximum. For more information, contact siloz through the link in our footer. <br/>";
		}
		if (strlen(trim($avail)) > 30) {
			$err .= "Please limit your availablitiy to 30 characters.";
		}
		if (strlen(trim($description)) > 500) {
			$err .= "Please limit your description to 500 characters.";
		}
		if ( ($_FILES['item_photo_1']['name']) && ($_FILES['item_photo_3']['name']) && (!$_FILES['item_photo_2']['name']) ) {
			$err .= "Please submit a second image for your item or remove the third image.";
		}
		if ( ($_FILES['item_photo_1']['name']) && ($_FILES['item_photo_4']['name']) && ((!$_FILES['item_photo_2']['name']) || (!$_FILES['item_photo_3']['name']))  ) {
			$err .= "Please submit a second and third image for your item or remove the fourth image.";
		}
		if ( (!$_FILES['item_photo_1']['name']) && (($_FILES['item_photo_2']['name']) || ($_FILES['item_photo_3']['name']) || ($_FILES['item_photo_4']['name']))  ) {
			$err .= "Please submit your image in the first slot before adding more images.";
		}

		$adr = urlencode($address);
		$zip_code = urlencode($zip);
		$url = "http://maps.google.com/maps/geo?q=".$adr."".$zip_code;
		$xml = file_get_contents($url);
		$geo_json = json_decode($xml, TRUE);
		if ($geo_json['Status']['code'] == '200') {
			$precision = $geo_json['Placemark'][0]['AddressDetails']['Accuracy'];
			$new_adr = $geo_json['Placemark'][0]['address'];
			$long = $geo_json['Placemark'][0]['Point']['coordinates'][0];
			$lat = $geo_json['Placemark'][0]['Point']['coordinates'][1];
		} else {
			$err .= 'Invalid address.<br/>';
		}
		
		$joined = true;
		//added sept 8th 2012 james kenny
		//make sure they selct their association with the silo
		if(!$vouch_type_id){
			$err .= "Please use tell us how you are associated with this Silo<br />";
		}
		if (strlen($err) == 0) {

			//added vouch sept 8th, 2012 james kenny
			if(!$Vouch){$Vouch = new Vouch();}
			$Vouch->Save($silo->silo_id,$user_id,$vouch_type_id);
			
			$sql = "INSERT INTO items(silo_id, user_id, title, price, item_cat_id, description, status) VALUES (?,?,?,?,?,?,?);";
			$stmt->prepare($sql);			
			$status = "Pledged";
			$stmt->bind_param("sssssss", $silo->silo_id, $user_id, $title, $price,$item_cat_id, htmlentities($description, ENT_QUOTES), $status);
			$stmt->execute();
			$stmt->close();
			$allowedExts = array("png", "jpg", "jpeg", "gif");
			$actual_id = $db->insert_id;
			$id = "02".time()."0".$actual_id;
			
			$sql = "UPDATE items SET id = '$id', avail = '$avail', longitude = '$long', latitude = '$lat' WHERE item_id = $actual_id";
			mysql_query($sql);

			$sqladr = "UPDATE users SET address = '$new_adr' WHERE user_id = $user_id";
			mysql_query($sqladr);

			$Feed = new Feed();
			$Feed->silo_id = $silo->silo_id;
			$Feed->user_id = $user_id;
			$Feed->item_id = $actual_id;
			$Feed->status = $status;
			$Feed->Save();
						
			for ($i=1; $i<=4; ++$i) {
				if ($_FILES['item_photo_'.$i]['name'] != '') {
					$ext = end(explode('.', strtolower($_FILES['item_photo_'.$i]['name'])));
					if (!in_array($ext, $allowedExts)) {
						$err .= $_FILES['item_photo_'.$i]['name']." is invalid file type.<br/>";
						break;
					}
					else {
						$filename = $_FILES['item_photo_'.$i]['name'];
						$temporary_name = $_FILES['item_photo_'.$i]['tmp_name'];
						$mimetype = $_FILES['item_photo_'.$i]['type'];
						$filesize = $_FILES['item_photo_'.$i]['size'];
						$uploaded = $i;

						switch($mimetype) {

    							case "image/jpg":

    							case "image/jpeg":

        						$img = imagecreatefromjpeg($temporary_name);

       						break;

    							case "image/gif":

        						$img = imagecreatefromgif($temporary_name);

        						break;

    							case "image/png":

        						$img = imagecreatefrompng($temporary_name);

        						break;
						}

						unlink($temporary_name);
						imagejpeg($img,"uploads/".$id."_".$i.".jpg",80);

						$sql = "SELECT * FROM silo_membership WHERE silo_id = $silo_id AND user_id = $user_id";
						if (mysql_num_rows(mysql_query($sql)) == 0) {
							$joined = false;
							$sql = "INSERT INTO silo_membership(silo_id, user_id) VALUES (".$silo->silo_id.",".$user_id.")";
							mysql_query($sql);
						}
					}
				}							
			}
			if (strlen($err) > 0) {
				$sql = "DELETE FROM items WHERE id = $id";
				mysql_query($sql);
			}			
		}
		if (strlen($err) == 0) {
			if ($joined) { //Already joined
				$subject = "Thank you for pledging for ".$silo->name;
				$message = "<br/>You have pledged on silo <b>".$silo->name."</b> with your item - <b>$title</b> ($price$).<br/><br/>";
				$message .= "Remember: you can share the silo you joined/pledged on <b>Facebook</b>, or, use our address book tool to generate an email to your frequent contacts to notify them of your fund-raiser’s need for member.  Click <a href='".ACTIVE_URL."/index.php?task=view_silo&id=".$silo->id."'>here</a> to notify your contacts.<br/><br/>";
				$message .= "We thank you for participating in silo ".$silo->name." and for using siloz.com.<br/><br/>
							Sincerely,<br/><br/>
							Siloz Staff.";			
			    email_with_template($user->email, $subject, $message);
			}
			else {
				$subject = "Thank you for joining ".$silo->name;
				$message = "<br/>Congratulations on joining silo <b>".$silo->name."</b> with your item - <b>$title</b> ($price$).<br/><br/>";
				$message .= "<h3>Getting Started</h3>";
				$message .= "Remember: you can share the silo you joined on <b>Facebook</b>, or, use our address book tool to generate an email to your frequent contacts to notify them of your fund-raiser’s need for member.  Click <a href='".ACTIVE_URL."/index.php?task=view_silo&id=".$silo->id."'>here</a> to notify your contacts.<br/><br/>";
				$message .= "We thank you for participating in your silo and for using siloz.com.<br/><br/>
							Sincerely,<br/><br/>
							Siloz Staff.";			
			    email_with_template($user->email, $subject, $message);
				
			}
			if (strlen($err) == 0) {
				$success = "true";
			}
		}
	}
	else {
		$id = param_get('id');
		if ($id == '') {
			echo "<script>window.location = 'index.php';</script>";		
		}
		else {
			$silo = new Silo($id);
		}
	}

	if (param_post('crop') == 'Crop1') {
		$id = trim(param_post('item_id'));
		$targ_w = 300;
		$targ_h = 225;
		$jpeg_quality = 90;

		$src = 'uploads/'.$id.'_1.jpg';
		$name = 'uploads/items/'.$id.'_1.jpg';
		$img_r = imagecreatefromjpeg($src);
		$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

		imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],
		$targ_w,$targ_h,$_POST['w'],$_POST['h']);

		imagejpeg($dst_r, $name, $jpeg_quality);
		unlink($src);

		mysql_query("UPDATE items SET photo_file_1 = '".$id."_1.jpg' WHERE id = '$id'");

		if ($_POST['upload2']) { $crop = "2"; } else { $crop = "true"; }
		if ($_POST['upload3']) { $upl3 = "3"; }
		if ($_POST['upload4']) { $upl4 = "4"; }

	}
	elseif (param_post('crop') == 'Crop2') {
		$id = trim(param_post('item_id'));
		$targ_w = 300;
		$targ_h = 225;
		$jpeg_quality = 90;

		$src = 'uploads/'.$id.'_2.jpg';
		$name = 'uploads/items/'.$id.'_2.jpg';
		$img_r = imagecreatefromjpeg($src);
		$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

		imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],
		$targ_w,$targ_h,$_POST['w'],$_POST['h']);

		imagejpeg($dst_r, $name, $jpeg_quality);
		unlink($src);

		mysql_query("UPDATE items SET photo_file_2 = '".$id."_2.jpg' WHERE id = '$id'");

		if ($_POST['upload3']) { $crop = "3"; } else { $crop = "true"; }
		if ($_POST['upload4']) { $upl4 = "4"; }
	}
	elseif (param_post('crop') == 'Crop3') {
		$id = trim(param_post('item_id'));
		$targ_w = 300;
		$targ_h = 225;
		$jpeg_quality = 90;

		$src = 'uploads/'.$id.'_3.jpg';
		$name = 'uploads/items/'.$id.'_3.jpg';
		$img_r = imagecreatefromjpeg($src);
		$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

		imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],
		$targ_w,$targ_h,$_POST['w'],$_POST['h']);

		imagejpeg($dst_r, $name, $jpeg_quality);
		unlink($src);

		mysql_query("UPDATE items SET photo_file_3 = '".$id."_3.jpg' WHERE id = '$id'");

		if ($_POST['upload4']) { $crop = "4"; } else { $crop = "true"; }
	}
	elseif (param_post('crop') == 'Crop4') {
		$id = trim(param_post('item_id'));
		$targ_w = 300;
		$targ_h = 225;
		$jpeg_quality = 90;

		$src = 'uploads/'.$id.'_4.jpg';
		$name = 'uploads/items/'.$id.'_4.jpg';
		$img_r = imagecreatefromjpeg($src);
		$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

		imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],
		$targ_w,$targ_h,$_POST['w'],$_POST['h']);

		imagejpeg($dst_r, $name, $jpeg_quality);
		unlink($src);

		mysql_query("UPDATE items SET photo_file_4 = '".$id."_4.jpg' WHERE id = '$id'");

		$crop = "true";
	}

	if ($crop == "true") {
		echo "<script>window.location = 'index.php?task=view_item&id=".$id."';</script>";
	}	
?>

		<script language="Javascript">

			$(function(){

				$('#cropbox').Jcrop({
					aspectRatio: 4/3,
					onSelect: updateCoords
				});

			});

			function updateCoords(c)
			{
				$('#x').val(c.x);
				$('#y').val(c.y);
				$('#w').val(c.w);
				$('#h').val(c.h);
			};

			function checkCoords()
			{
				if (parseInt($('#w').val())) return true;
				alert('Please select a crop region then press submit.');
				return false;
			};

		</script>

		<div class="heading">
			<b>Join this Silo: <?php echo "<a href=".ACTIVE_URL."/index.php?task=view_silo&id=".$silo->id.">".$silo->name."</a></b> (".$silo->type." Silo)";?>
		</div>

<?php
if ($success && $_FILES['item_photo_1']['name']) {
?>
		<center>
				<h1>Create a Silo</h1>
		To finish pledging your item, please crop all of the images you uploaded below (Image 1):<br><br>
		<!-- This is the image we're attaching Jcrop to -->
		<img src="uploads/<?=$id?>_1.jpg" id="cropbox" />
		
		<br>

		<!-- This is the form that our event handler fills -->
		<form action="" method="post" onsubmit="return checkCoords();">
			<input type="hidden" id="x" name="x" />
			<input type="hidden" id="y" name="y" />
			<input type="hidden" id="w" name="w" />
			<input type="hidden" id="h" name="h" />
			<input type="hidden" name="item_id" value="<?=$id?>" />

		<?php if ($_FILES['item_photo_2']['name']) { echo '<input type="hidden" name="upload2" value="2" />'; } ?>
		<?php if ($_FILES['item_photo_3']['name']) { echo '<input type="hidden" name="upload3" value="3" />'; } ?>
		<?php if ($_FILES['item_photo_4']['name']) { echo '<input type="hidden" name="upload4" value="4" />'; } ?>

			<button type="submit" name="crop" value="Crop1">Crop</button>
		</form>
		</center>
		<br><br>
<?php
die;
}
elseif ($success && !$filename) { echo "<script>window.location = 'index.php?task=view_item&id=".$id."';</script>"; }
?>

<?php
if ($crop == "2") {
?>
		<center>
				<h1>Create a Silo</h1>
		To finish pledging your item, please crop the image you uploaded below (Image 2):<br><br>
		<!-- This is the image we're attaching Jcrop to -->
		<img src="uploads/<?=$id?>_2.jpg" id="cropbox" />
		
		<br>

		<!-- This is the form that our event handler fills -->
		<form action="" method="post" onsubmit="return checkCoords();">
			<input type="hidden" id="x" name="x" />
			<input type="hidden" id="y" name="y" />
			<input type="hidden" id="w" name="w" />
			<input type="hidden" id="h" name="h" />
			<input type="hidden" name="item_id" value="<?=$id?>" />
		<?php if ($upl3) { echo '<input type="hidden" name="upload3" value="3" />'; } ?>
		<?php if ($upl4) { echo '<input type="hidden" name="upload4" value="4" />'; } ?>
			<button type="submit" name="crop" value="Crop2">Crop</button>
		</form>
		</center>
		<br><br>
<?php
die;
}
?>

<?php
if ($crop == "3") {
?>
		<center>
				<h1>Create a Silo</h1>
		To finish pledging your item, please crop the image you uploaded below (Image 3):<br><br>
		<!-- This is the image we're attaching Jcrop to -->
		<img src="uploads/<?=$id?>_3.jpg" id="cropbox" />
		
		<br>

		<!-- This is the form that our event handler fills -->
		<form action="" method="post" onsubmit="return checkCoords();">
			<input type="hidden" id="x" name="x" />
			<input type="hidden" id="y" name="y" />
			<input type="hidden" id="w" name="w" />
			<input type="hidden" id="h" name="h" />
			<input type="hidden" name="item_id" value="<?=$id?>" />
		<?php if ($upl4) { echo '<input type="hidden" name="upload4" value="4" />'; } ?>
			<button type="submit" name="crop" value="Crop3">Crop</button>
		</form>
		</center>
		<br><br>
<?php
die;
}
?>

<?php
if ($crop == "4") {
?>
		<center>
				<h1>Create a Silo</h1>
		To finish pledging your item, please crop the image you uploaded below (Image 4):<br><br>
		<!-- This is the image we're attaching Jcrop to -->
		<img src="uploads/<?=$id?>_4.jpg" id="cropbox" />
		
		<br>

		<!-- This is the form that our event handler fills -->
		<form action="" method="post" onsubmit="return checkCoords();">
			<input type="hidden" id="x" name="x" />
			<input type="hidden" id="y" name="y" />
			<input type="hidden" id="w" name="w" />
			<input type="hidden" id="h" name="h" />
			<input type="hidden" name="item_id" value="<?=$id?>" />
			<button type="submit" name="crop" value="Crop4">Crop</button>
		</form>
		</center>
		<br><br>
<?php
die;
}
?>

		<p>Please enter your item details below, and upload up to 4 images for your item.</p>
		<form enctype="multipart/form-data"  name="sell_on_siloz" class="my_account_form" method="POST">
			<input type="hidden" name="task" value="sell_on_siloz"/>
			<input type="hidden" name="silo_id" value="<?php echo $silo->id;?>"/>
			<input type="hidden" name="address" value="<?php echo $user->address;?>"/>					
			<input type="hidden" name="zip" value="<?php echo $user->zip_code;?>"/>					
			<table width="80%" cellpadding="10px" align="center">
				<tr>
					<td colspan="2" align="center">
						<?php
							if (strlen($err) > 0) {
								echo "<font color='red'><b>".$err."</b></font>";
							}
						?>						
					</td>
				</tr>		
				<tr>
					<td valign="top">
						<table>
							<tr>
								<td><b>Listing Title</b> </td>
								<td><input type="text" name="title" style="width : 300px" value='<?php echo $title; ?>'/></td>
							</tr>
							<tr>
								<td><b>Seller Availability</b> </td>
								<td><input type="text" name="avail" style="width : 300px" value='<?php echo $avail; ?>'/></td>
							</tr>
							<tr>
								<td><b>Price</b> </td>
								<td><input type="text" name="price" style="width : 100px" value='<?php echo $price; ?>'/></td>
							</tr>
							<tr>
								<td><b>Category</b> </td>
								<td>
									<select name="item_cat_id" style="width: 300px">
										<?php
											$sql = "SELECT * FROM item_categories";
											$res = mysql_query($sql);
											while ($ic = mysql_fetch_array($res)) {
												if ($ic['item_cat_id'] == $item_cat_id) {
													echo "<option value='".$ic['item_cat_id']."' selected>".$ic['category']."</option>";
												}
												else {
													if ($item_cat_id == 0 && $ic['category'] == 'Everything (default)')
														echo "<option value='".$ic['item_cat_id']."' selected>".$ic['category']."</option>";
													else
														echo "<option value='".$ic['item_cat_id']."'>".$ic['category']."</option>";
												}
											}							
										?>							
									</select>
								</td>
							</tr>
							<tr>
								<td><b>Description</b> </td>
								<td><textarea name="description" style="width: 300px; height: 50px"><?php echo $description; ?></textarea></td>
							</tr>
						</table>
					</td>
					<td valign="top">
						<table>
							<tr>
								<td><b>Photo file 1</b> </td>
								<td><input name="item_photo_1" type="file" style="width: 200px; height: 20px;"/></td>
							</tr>		
							<tr>
								<td><b>Photo file 2</b> </td>
								<td><input name="item_photo_2" type="file" style="width: 200px;height: 20px;"/></td>
							</tr>		
							<tr>
								<td><b>Photo file 3</b> </td>
								<td><input name="item_photo_3" type="file" style="width: 200px;height: 20px;"/></td>
							</tr>		
							<tr>
								<td><b>Photo file 4</b> </td>
								<td><input name="item_photo_4" type="file" style="width: 200px;height: 20px;"/></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
				<td>
					<p style="line-height:1.0em; margin:0; padding:0;"><b>The data from the survey (below) will be compiled and displayed on a silo's page, and is only an index to the silo's probable legitimacy.</b></p><br>
				</td>
				</tr>

				<?php
					//php block added september 8th, 2012 james kenny
					if(!$VouchType){$VouchType = new VouchType();}
					$vouchTypeIds = $VouchType->GetIds();
					if(!$Vouch){$Vouch = new Vouch();}
					$userLastVouchType = $Vouch->GetUsersLastVouchId($user_id,$silo->silo_id);
				?>
				<tr>
					<td style="line-height:1.0em; margin:0; padding:0;">
						<?php 
							foreach($vouchTypeIds as $vid){
								$VouchType->Populate($vid); 
								if($userLastVouchType === $vid){$selected = "checked='checked' ";}
								else{$selected = '';}
								?>
								<input type="radio" name="vouch" value="<?php echo $vid ;?>" <?php echo $selected ;?>/><?php echo $VouchType->text; ?><br />
							<?php }
						?>
					</td>
				</tr>
				<tr>
				<td><br>
						<p style="line-height:1.0em; margin:0; padding:0;"><strong>Disclaimer:</strong> siloz makes no representation as to, and offers no guarantee of, the legitimacy of any organization or cause, the veracity of information posted on our site, or the fitness of a silo administrator to collect funds on behalf of the organization or cause.  Read our Terms of Use and FAQ for more information.  By using siloz, members you agree to hold siloz harmless and not liable for  fraud, misrepresentation, tortious acts committed by a silo administrator, and crimes incidental to the sale of items.</p>
				</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<button type="submit" name="submit" value="Finish">Finish</button>
					</td>
				</tr>			
			</table>	
		</form>
		<!-- <div style='width: 75%; margin: auto'>
			<br/>
			<b>Note: </b> While we think we're the easiest site to use, regrettably, we don't always have the traffic to sell items.  We invite you to - in addition to our site - list and sell your item on any website or through off-line means (yard sale, etc.) you wish!  
			<ul>
				<li>If your item sells here first, remove those ads!</li>
				<li>If it sells elsewhere, change the status to 'Item Sold' or 'Payment Sent', as the case may be!</li>
			</ul>
		</div> -->
<?php
}
?>
