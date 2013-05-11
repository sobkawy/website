<?php
	$Silo = new Silo();
	$silo_id = $Silo->GetUserSiloId($_SESSION['user_id']);
	$Silo->Populate($silo_id);
		
	if ($_SESSION['is_logged_in'] != 1) {
		echo "<script>window.location = 'index.php';</script>";
	}	
		$admin_id = $_SESSION['user_id'];	
		$admin = new User($admin_id);
		$silo = $admin->getCurrentSilo();

	$user_id = $_SESSION['user_id'];

	$id = mysql_fetch_row(mysql_query("SELECT id FROM silos WHERE admin_id = '$user_id'"));

		$Silo = new Silo($id[0]);
		$silo_id = $Silo->silo_id;
		$today = date('Y-m-d')."";
		$silo_ended = $Silo->end_date < $today;
		$admin = $Silo->admin;

	$isAdmin = mysql_num_rows(mysql_query("SELECT * FROM silos WHERE admin_id = '$user_id' AND silo_id = '$silo->silo_id'"));

		$err = "";
		$admin_id = $_SESSION['user_id'];
		$admin = new User($admin_id);
		$silo = $admin->getCurrentSilo();
		if (param_post('update') == 'Update') {		
			$name = param_post('name');
			$shortname = trim(param_post('shortname'));
			$address = param_post('address');
			$org_name = param_post('org_name');
			$purpose = param_post('purpose');
			$phone_number = param_post('phone_number');
			
			if (strlen(trim($name)) == 0) {
				$err = 'Silo name must not be empty.<br/>';		
			}
			if (strlen(trim($name)) > 50) {
				$err = 'Your new silo name is too long. Please shorten it.<br/>';		
			}
			if (strlen($shortname) == 0) {
				$err = "Silo's short name must not be empty.<br/>";
			}
			if (strpos('|'.$shortname, ' ') > 0) {
				$err = "Silo's short name must not contain space.<br/>";
			}
			else {
				if (strlen($shortname) > 30) {
					$err = "Silo's short name cannot be more than 30 characters.<br/>";
				}
			}
			$sql = "SELECT * FROM silos WHERE shortname = '$shortname' AND silo_id <> $silo_id";
			if (mysql_num_rows(mysql_query($sql)) > 0) {
				$err = "Silo's short name is already used by another silo. <br/>";
			}
			
			if (strlen(trim($address)) == 0) {
				$err = 'Address must not be empty.<br/>';		
			}

			$adr = urlencode($address);
			$json = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=".$adr."&sensor=false");
			$loc = json_decode($json);

			if ($loc->status == 'OK') {
				$address = $loc->results[0]->formatted_address;
				$latitude = $loc->results[0]->geometry->location->lat;
				$longitude = $loc->results[0]->geometry->location->lng;
			}
			else { $err = "Invalid Location! <br>"; }

			if (strlen($err) == 0) {

				$success = "true";							
				include("include/set_silo_params.php");
				
				if ($_FILES['silo_photo']['name'] != '') {
					$allowedExts = array("png", "jpg", "jpeg", "gif");							
					$ext = end(explode('.', strtolower($_FILES['silo_photo']['name'])));
					if (!in_array($ext, $allowedExts)) {
						$err .= $_FILES['silo_photo']['name']." is invalid file type.";
					}
					else {
							$filename = $_FILES['silo_photo']['name'];
							$temporary_name = $_FILES['silo_photo']['tmp_name'];
							$mimetype = $_FILES['silo_photo']['type'];
							$filesize = $_FILES['silo_photo']['size'];

							switch($mimetype) {

    								case "image/jpg":

    								case "image/jpeg":

        							$i = imagecreatefromjpeg($temporary_name);

       							break;

    								case "image/gif":

        							$i = imagecreatefromgif($temporary_name);

        							break;

    								case "image/png":

        							$i = imagecreatefrompng($temporary_name);

        							break;
							}

							$name = "uploads/".$Silo->id.".jpg";
							$targ_w = "900";
							$img_w = getimagesize($temporary_name);

							if ($img_w[0] > $targ_w) {
      								$image = new Photo();
      								$image->load($temporary_name);
      								$image->resizeToWidth($targ_w);
								$image->save($name);
							} else {
								imagejpeg($i,$name,80);
							}

							unlink($temporary_name);
					}
				}				
			}
		}

	if (param_post('crop') == 'Crop') {
		$id = trim(param_post('silo_id'));
		$crop = true;
		$targ_w = 300;
		$targ_h = 225;
		$jpeg_quality = 90;

		$src = 'uploads/'.$id.'.jpg';
		$name = 'uploads/silos/'.$id.'.jpg';
		$img_r = imagecreatefromjpeg($src);
		$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

		imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],
		$targ_w,$targ_h,$_POST['w'],$_POST['h']);

		imagejpeg($dst_r, $name, $jpeg_quality);
		unlink($src);

		mysql_query("UPDATE silos SET photo_file = '$id.jpg' WHERE id = '$id'");
	}

	$updatemsg = "Your silo has been updated!";
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

<?php
		$checkUser = mysql_num_rows(mysql_query("SELECT * FROM silo_membership WHERE silo_id = '$silo_id' AND user_id = '$user_id' AND removed_date > 0"));
		$showU = mysql_num_rows(mysql_query("SELECT * FROM silo_membership WHERE silo_id = '$silo_id' AND user_id = '$user_id' AND removed_date = 0"));
?>

<div class="headingPad"></div>

<div class="siloHeading_manage">
	<table width="100%" style="border-spacing: 0px;">
		<tr>
			<td>
				<?php echo $silo->getTitle(); ?>
			</td>
			<td align="center" style="font-size: 8pt; font-weight: bold">
				<?php
					if (strlen($err) > 0) {
						echo "<span id='success' class='error'>".$err."</span>";
					}

					if ((param_post('update') == 'Update') && !$filename && strlen($err) == 0) { 
						echo "<span id='success' class='error'>".$updatemsg."</span>";
					}
					elseif ($crop == "true") { 
						echo "<span id='success' class='error'>".$updatemsg."</span>";
					}

				?>
			</td>
			<td width="450px" style="font-size: 10pt; font-weight: bold" align="right">
			<a href="index.php?task=manage_silo" class="<?php if (param_get('task') == 'manage_silo') { echo "orange"; } else { echo "blue"; } ?>">manage members and items</a>
			<span style="padding: 0 5px;">|</span>
			<a href="index.php?task=manage_silo_admin" class="<?php if (param_get('task') == 'manage_silo_admin') { echo "orange"; } else { echo "blue"; } ?>">view statistics and promote</a>
			<span style="padding: 0 5px;">|</span>
			<a onclick="popup_show('edit_silo', 'edit_silo_drag', 'edit_silo_exit', 'screen-center', 0, 0);populate_silo_info('<?=$silo_id?>');" class="blue">edit silo</a>
			</td>
		</tr>
	</table>
</div>

<div class="headingPad"></div>

<table width="100%" style="padding-right: 50px;">
<tr>
<td rowspan="2">
<table class='siloInfo'>
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
		<td class="siloInnerInfo">
			<a href='index.php?task=view_silo&view=members&id=<?=$silo->id;?>'><?=$silo->getTotalMembers();?></a>
			<a href='index.php?task=view_silo&view=items&id=<?=$silo->id;?>'><?=$silo->getTotalItems();?></a>
			<?=$silo->getDaysLeft();?>
			<div style="padding-top: 10px;"></div>
		<?php if (!$tax_ded) { $tax = "<b><u>not</u></b>"; } ?>
			<div class="voucherText" style="font-size: 10pt; text-align: left"><b>Purpose:</b> <?=$silo->getPurpose();?></div>
			<div class="voucherText" style="font-size: 10pt; text-align: left">This Administrator has <?=$tax?> provided an EIN number for this fundraiser, and donations are <?=$tax?> tax-deductable.</div>
		</td>
	</tr>
	<tr class="infoSpacer"></tr>
	<tr>
		<td class="siloInnerInfo">
			<span class="floatL">
				<img src="<?php echo 'uploads/members/'.$admin->photo_file.'?'.$admin->last_update;?>" class="siloImg" width='100px'/><br>
				<a class="buttonEmail">Email Admin.</a>
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
		<td class="siloInnerInfo">
			<div align="left">
			<span class='voucher'>Donate only to local causes that you know or have researched!</span><br><br>
			<?php include('include/UI/flag_box_silo.php'); ?>
		</div>
		Silo ID: <?=$silo->id?>
		</td>
	</tr>
</table>
</td>
<td align="center" valign="top" style="padding-top: 10px;">

<table class="aconsole-metrics">
<tr>
<td rowspan="2" align="center">
<b><font size="4">Metrics</font></b>
</td>
<td valign="top">
<div class='floatL'><div class='padding'><b>Progress:</b> &nbsp; <div style='float: right; width: 100px; height: 15px; border: 1px solid #000;'><div style='float: left; width: <?=$pct;?>%; height:15px; background: #000;'></div></div></div></div>
</td>
<td rowspan="2" align="center">
Average Item Price: <?php $avgprice = $silo->getAvgItemPrice(); echo money_format('%(#10n', floatval($avgprice));?><br><br>
Listings per Member: <?php echo $silo->getAvgListings();?>
</td>
<td width="100px" rowspan="3">
</td>
</tr>
<tr>
<td align="center">
<div class='floatL'><div class='padding'><b>Deadline:</b> &nbsp;&nbsp; <div style='float: right; width: 100px; height: 15px; border: 1px solid #000;'><div style='float: left; width: <?=$pct_day;?>%; height:15px; background: #000;'></div></div></div></div>
</td>
</tr>
<tr>
<td align="center">
<b>Goal:</b> <?php echo money_format('%(#10n', floatval($silo->goal));?>
</td>
<td align="center">
<b>Pledged:</b> <?php echo money_format('%(#10n', $silo->getPledgedAmount());?>
</td>
<td align="center">
<b>Collected:</b> <?php $collected = $silo->getCollectedAmount(); $pct = round($collected*100.0/floatval($silo->goal),1);	echo money_format('%(#10n', $collected);?>
</td>
</tr>
<tr>
<td colspan="3">
<?php
include "include/charts/charts.php";
echo InsertChart ( "include/charts/charts.swf", "include/charts/charts_library", "include/charts/aconsole-chart.php", 380, 250 );
?>
</td>
<td valign="top">
View:<br><br>
<div class="metrics-text-blue">
Members<br><br>
Money Raised
</div>
</td>
</tr>
</table>

<br>

<table class="aconsole-promote">
<tr>
<td width="15%" align="center">
<font size="4">Promote</font>
</td>
<td width="50%" align="center">
<?=ACTIVE_URL?><?=$silo->shortname;?>
</td>
<td rowspan="3" align="center" valign="top" class="promote-text-blue" style="padding-top: 30px;">
Share your silo on Facebook<br><br>
<img src="images/f_share.gif">
</td>
</tr>
<tr>
<td colspan="2" align="center" class="promote-text-blue" style="padding: 0px 60px;">
<form action="business_card.php" method="post" target="_blank">
	<input type="hidden" name="id" value="<?=$silo->id?>" />
	<input type="submit" class="promote-text-blue" style="font-size: 12px; font-weight: bold; background: transparent; border: 0px;" value="Print Promotional Business Cards" />
</form>
</td>
</tr>
<tr>
<td colspan="2" align="center" class="promote-text-blue" style="padding: 0px 60px;">
<a href="index.php?task=invite_promote&id=<?php echo $silo->id; ?>" target="_blank">
Link to your e-mail address book via OpenInviter (to tell your friends)<br><br>
<img src="images/mail-icon.png"></a>
</td>
</tr>
</table>

</td>
</tr>
</table>


<div style="padding-bottom: 10px;"></div>

<div class="edit_item" id="edit_silo">
	<div id="edit_silo_drag" style="float: right">
		<img id="edit_silo_exit" src="images/close.png"/>
	</div>

	<div>

<form enctype="multipart/form-data"  name="manage_silo_form" class="manage_silo_form" method="POST">
		<input type="hidden" name="task" value="manage_silo"/>
		
		<table cellpadding="10px">
			<tr>
				<td align="center" valign="top" width="650px">
					<img src="<?php echo 'uploads/silos/'.$silo->photo_file.'?'.$silo->last_update;?>" width="300px"/>
					<br/><br/>
					<b>Upload new photo: </b><input name="silo_photo" type="file" style="height: 24px" />
					<br/><br/>

					<table>
						<tr>
							<td valign="center" style="width: 120px;"><b>Silo Full Name: </b></td>
							<td><input type="text" name="name" style="width : 300px" value='<?php echo $silo->name; ?>'/></td>
						</tr>
						<tr>
							<td valign="center"><b>Silo Short Name: </b></td>
							<td><input type="text" name="shortname" style="width : 300px" value='<?php echo $silo->shortname; ?>'/></td>
						</tr>						
						<tr>
							<td>
								<b>Address:</b>
							</td>
							<td>
								<input type="text" name="address" style="width : 300px" value='<?php echo $silo->address; ?>'/>
							</td>
						</tr>
						<tr>
							<td>
								<b>Organization:</b><br/>
							</td>
							<td>
								<input type="text" name="org_name" style="width : 300px" value='<?php echo $silo->org_name; ?>'/>
							</td>
						</tr>						
						<tr>
							<td>
								<b>Phone Number:</b>
							</td>
							<td>
								<input type="text" name="phone_number" style="width : 150px" value='<?php echo $silo->phone_number; ?>'/>
							</td>
						</tr>

						<tr>
							<td colspan=2><br/></td>
						</tr>
						
						<tr>
							<td colspan=2><b>Organization and fundraiser purpose: </b>
							<?php
								echo $silo->purpose;
							?>
							</td>
						</tr>
					</table>
					<br><br>

					<button type="submit" name="update" value="Update">Update Silo</button>				
				</td>				
			</tr>
		</table>
	</form>

	</div>
</div>
