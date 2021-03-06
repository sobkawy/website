<?php

$userArray = array('5','7','8','2');
if ($_SESSION['is_logged_in'] != 1 || !in_array($user_id, $userArray)) {
	echo "<script>window.location = 'index.php';</script>";
} elseif (!$_SESSION['admin_access']) {
	echo "<script>window.location = 'index.php';</script>";
} else {
	$err = "";
	$address = "";
	$filename = "";
	$today = date('Y-m-d')."";
	if (mysql_num_rows(mysql_query("SELECT * FROM silos WHERE admin_id = ".$_SESSION['user_id']." AND end_date >= '".$today."'")) > 0) {
		echo "<script>window.location = 'index.php?task=manage_silo';</script>";		
	}	
	if (param_post('publish') == 'Publish') {
		
		$silo_type = "public";
		$admin_id = $user_id;
		$name = param_post('name');
		$shortname = trim(param_post('shortname'));
		$ein = param_post('ein');
		$verified = param_post('verified');
		$issue_receipts = 1;		
		$org_name = param_post('org_name');		
		$title = param_post('title');
		$phone_number = param_post('phone_number');
		$address = param_post('address');
		$silo_cat_id = 99;
		$start_date = $today;
		$end_date = param_post('end_date');				
		$goal = param_post('goal');
		$org_purpose = param_post('org_purpose');
		$silo_purpose = param_post('silo_purpose');

		$ein = ($verified == 1) ? $ein : '0';
		$issue_receipts = 1;

		if (strlen(trim($name)) == 0) {
			$err .= 'Silo name must not be empty.<br/>';		
		}
		if (strlen(trim($name)) > 50) {
			$err .= 'Your silo name is too long. Please shorten it.<br/>';		
		}
		if (strlen($shortname) == 0) {
			$err .= "Silo's short name must not be empty.<br/>";
		}
		if (strpos('|'.$shortname, ' ') > 0) {
			$err .= "Silo's short name must not contain space.<br/>";
		}
		else {
			if (strlen($shortname) > 30) {
				$err .= "Silo's short name cannot be more than 30 characters.<br/>";
			}
		}
		$sql = "SELECT * FROM silos WHERE shortname = '$shortname'";
		if (mysql_num_rows(mysql_query($sql)) > 0) {
			$err .= "Silo's short name is already used by another silo. <br/>";
		}	
		if (strlen(trim($org_name)) == 0) {
			$err .= "Silo's organization name must not be empty. <br/>";
		}

		if (strlen(trim($address)) == 0) {
			$err .= 'Address must not be empty.<br/>';		
		}
		if (strlen(trim($title)) == 0) {
			$err .= "Your title must not be empty. <br/>";
		}
		if (strlen(trim($phone_number)) == 0) {
			$err .= "Phone number must not be empty. <br/>";
		}
		if ($org_purpose == '' && $silo_type == "public") {
			$err .= "Please enter an organization purpose.<br/>";
		}
		elseif (strlen(trim($org_purpose)) > 250) {
			$err .= "The organization purpose is more than 250 characters.<br/>";
		}
		if ($silo_purpose == '') {
			$err .= "Please enter a silo purpose.<br/>";
		}
		elseif (strlen(trim($silo_purpose)) > 250) {
			$err .= "The silo purpose is more than 250 characters.<br/>";
		}
		if (strlen(trim($end_date)) == 0) {
			$err .= "End date must not be empty. <br/>";
		}
		elseif (strtotime($end_date) - strtotime($start_date) < 0) {
			$err .= "End date has to be after start date. <br/>";
			
		}
		if (strlen(trim($goal)) == 0) {
			$err .= "Silo's goal must be set.<br/>";		
		}	
		else if (!is_numeric($goal)) {
			$err .= "Silo's goal is not a valid number.<br/>";
		}
		else if (floatval($goal) < 0) {
			$err .= "Silo's goal is negative.<br/>";
		}
		else if (floatval($goal) > 100000000) {
			$err .= "Silo's goal exceeds the allowed maximum.<br/>";
		}

		if (!$_FILES['silo_photo']['name']) {
			$err .= "You must upload an image for your silo.<br/>";
		}

		$adr = urlencode($address);
		$json = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=".$adr."&sensor=false");
		$loc = json_decode($json);

		if ($loc->status == 'OK') {
			$address = $loc->results[0]->formatted_address;
			$latitude = $loc->results[0]->geometry->location->lat;
			$longitude = $loc->results[0]->geometry->location->lng;
		}
		else { $err .= "Invalid business address!"; }
			
		
		if (strlen($err) == 0) {
			$status = "active";
			$Silo = new Silo();
			$Silo->admin_id = $admin_id;
			
			include("include/set_silo_params.php");
			$actual_id = $silo_id;
			
			$allowedExts = array("png", "jpg", "jpeg", "gif");
			
				$ext = end(explode('.', strtolower($_FILES['silo_photo']['name'])));
				if (!in_array($ext, $allowedExts)) {
					$err .= $_FILES['silo_photo']['name']." is invalid file.<br/>";
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
		
		if (strlen($err) == 0) {
			$success = "true";
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
	
	$user = mysql_fetch_array(mysql_query("SELECT * FROM users WHERE user_id=".$_SESSION['user_id']));
	//die(print_r($user));

	if ($crop == "true") {
		header("Location: 'index.php?task=manage_silo'");
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

<div class="spacer"></div>
<div class="spacer"></div>

<?php
if ($success && $filename) {
?>
	<center>
				<h1>Create an Official Silo</h1>
		To finish creating your silo, please crop the image you uploaded below:<br><br>
		<!-- This is the image we're attaching Jcrop to -->
		<img src="uploads/<?=$Silo->id?>.jpg" id="cropbox" />
		
		<br>

		<!-- This is the form that our event handler fills -->
		<form action="" method="post" onsubmit="return checkCoords();">
			<input type="hidden" id="x" name="x" />
			<input type="hidden" id="y" name="y" />
			<input type="hidden" id="w" name="w" />
			<input type="hidden" id="h" name="h" />
			<input type="hidden" name="silo_id" value="<?=$Silo->id?>" />
			<button type="submit" name="crop" value="Crop">Crop</button>
		</form>
	</center>
<?php
die;
}
elseif ($success) { echo "<script>window.location = 'index.php?task=manage_silo'</script>"; }
?>

<form enctype="multipart/form-data"  name="create_silo" class="create_account_form" method="POST">
	<input type="hidden" name="task" value="create_silo"/>
		
	<div class="create_account_form" style="width: 500px">
		<table style="margin:auto">
			<tr>
				<td colspan="2" align="center">
					<h1>Create an Official Silo</h1>
				</td>
			</tr>
			<tr>
				<td colspan=2 align="left">
					<?php
						if (strlen($err) > 0) {
							echo "<font color='red'><b>".$err."</b></font>";
						}
					?>
				</td>
			</tr>		
			<tr>
				<td>Silo name <font color='red'>*</font></td>
				<td><input type="text" name="name" style="width : 300px" value='<?php echo $name; ?>'/></td>			
			</tr>
			<tr>
				<td>Silo short name <font color='red'>*</font></td>
				<td><input type="text" name="shortname" style="width : 300px" value='<?php echo $shortname; ?>'/></td>			
			</tr>										
			<tr>
				<td>EIN (Emp. ID) for Tax-Ded.</td>
				<td>
					<input type="text" name="ein" id="ein" style="width : 200px" value='<?php echo $ein; ?>'/>
					<button type="button" onclick="verify_ein();">Verify EIN</button>
					<script>
					function verify_ein() {
						$.post(<?php echo "'".API_URL."'"; ?>, 
							{	
								request: 'verify_ein',
								ein: document.getElementById('ein').value
							}, 
							function (data) {
								if ($(data).find('status').text() == 'ok') {
									alert("Success! The EIN number has been found and the company information has been filled for you.");
									document.getElementById('org_name').value = $(data).find('CompanyName').text();
									document.getElementById('address').value = $(data).find('Address').text() + ", " +  $(data).find('City').text() + ", " +  $(data).find('State').text() + " " +  $(data).find('Zip').text();
									document.getElementById('verified').value = 1; 
								}
								else {
									alert("We could not find a company with that EIN number. Please try again.");
								}
							}
						);
						
					}
					</script>
				</td>			
			</tr>
<!--
			<tr>
				<td colspan="2" class="create-silo">
					<input type="hidden" name="verified" id="verified">
					Issue tax-deductible receipts with donations?**
					&nbsp;&nbsp;&nbsp;
					<label>Yes</label> <input type="radio" name="issue_receipts" value="1" checked />
					<label>No</label> <input type="radio" name="issue_receipts" value="0"/><br>
				</td>
			</tr>
-->
			<tr>
				<td>Name of the organization <font color='red'>*</font></td>
				<td><input type="text" name="org_name" id="org_name" style="width : 300px" value='<?php echo $org_name; ?>'/></td>			
			</tr>
			<tr>
				<td>Official address <font color='red'>*</font></td>
				<td><input type="text" name="address" id="address" style="width : 300px" value='<?=$address?>'/></td>			
			</tr>
				
			<tr>
				<td>Your title <font color='red'>*</font></td>
				<td><input type="text" name="title" id="title" style="width : 150px" value='<?php echo $title; ?>'/></td>			
			</tr>
		
			<tr>
				<td>Official telephone number <font color='red'>*</font></td>
				<td><input type="text" name="phone_number" id="phone_number" style="width : 150px" value='<?php echo $phone_number; ?>'/></td>			
			</tr>						
			<tr>
				<td>End Date: <font color='red'>*</font></td>
				<td class="create-silo"><input type="text" id="end_date" name="end_date" value="<?=$end_date?>" /></td>
			</tr>
			<tr>
				<td>Goal <font color='red'>*</font></td>
				<td><input type="text" name="goal" style="width : 150px" value='<?php echo $goal; ?>'/> USD</td>			
			</tr>
			<tr>
				<td>Organization purpose <font color='red'>*</font></td>
				<td><textarea name="org_purpose" style="width : 300px; height: 50px"/><?php echo $org_purpose; ?></textarea></td>			
			</tr>
			<tr>
				<td>Silo purpose <font color='red'>*</font></td>
				<td><textarea name="silo_purpose" style="width : 300px; height: 50px"/><?php echo $silo_purpose; ?></textarea></td>			
			</tr>
			<tr>			
				<td>Photo </td>
				<td><input name="silo_photo" type="file"  style="height: 24px; width: 300px"/></td>
			</tr>		
			<tr>
				<td colspan="2"><br/></td>
			</tr>				
			<tr>
				<td colspan="2" align="center">
					<button type="submit" value="Publish" name="publish">Publish this Silo</button>
				</td>
			</tr>
		</table>
	</div>
</form>
<?php
}
?>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script src="js/jquery-ui-timepicker-addon.js"></script>
<script>
$(function() {
	$('#end_date').datetimepicker({
   	 	dateFormat: 'yy-mm-dd',
		timeFormat: "HH:mm:ss"
	});
});
</script>

