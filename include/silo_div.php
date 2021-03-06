<?php 
if ($silo->silo_cat_id == "99") { $disaster = "true"; }
if ($silo->issue_receipts == 1) { $tax_ded = "true"; }

if ($silo->silo_type == "private" && !$showDiv) { ?>

<table class='siloInfo<?=$closed_silo?>' style="height: 730px">
	<tr>
		<td class="titleHeading">
			This silo is private. <br><br> You must be invited to this silo in order to pledge items towards it. Private silos are hidden from the general public.
		</td>
	</tr>
</table>

<?php } else { ?>

<table class='siloInfo<?=$closed_silo?>' style="height: 600px">

<?php if (param_get('task') == "view_item") { ?>
	<tr>
		<td class="titleHeading">
			<?=$silo->getShortTitle(35); ?>
		</td>
	</tr>
<?php } ?>

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
			<a href='silos/<?=$silo->shortname?>'><img src="<?php echo ACTIVE_URL.'uploads/silos/'.$silo->photo_file.'?'.$silo->last_update;?>" width='250px' class="siloImg"/>
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
		<?php if ($silo->silo_type == "public") { ?>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt; text-align: left"><b>Organization purpose: <?=$silo->org_purpose?></div></b>
		<?php } ?>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt; text-align: left"><b>Silo purpose: <?=$silo->silo_purpose?></div></b>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt; text-align: left"><b>This Administrator has <?=$tax?> provided an EIN number for this fundraiser, and donations are <?=$tax?> tax-deductable.</div></b>
		</td>
	</tr>
	<tr class="infoSpacer"></tr>
	<tr>
		<td class="siloInnerInfo<?=$closed_silo?>">
			<table width="100%"><tr>
			<?php if ($silo->silo_type == "public") { ?>
				<td style="padding-bottom: 10px;">Organization name: <?=$silo->org_name?></td>
			<?php } else { ?>
				<td style="padding-bottom: 10px;">Relationship to beneficiary: <?=$silo->title?></td>
			<?php } ?>
			</tr>

			<tr><td <?php if ($silo->website) { echo 'style="padding-bottom: 10px;"'; } ?>>
			<span class="floatL">
				<img src="<?php echo ACTIVE_URL.'uploads/members/'.$admin->photo_file.'?'.$admin->last_update;?>" class="siloImg" width='100px'/><br>
				<a style="color: #2f8dcb;" <?php if($closed_silo) { echo "class='fancybox buttonEmail' href='#closed_silo'"; } else { echo "class='fancybox buttonEmail' href='#contact_admin'"; }?>>Email Admin.</a>
			</span>
			<div align="left">
			<span class="infoDetails">
				Administrator:<br>
				<a href="<?=ACTIVE_URL?>index.php?task=view_user&id=<?=$admin->id?>"><?=$admin_name?></a><br>
				Official Address:<br>
				<?=$silo->address?><br>
				Telephone:<br>
				<?=$silo->phone_number?>
			</span>
			</div>
			</td></tr>

			<?php if ($silo->website) { ?>
			<tr><td>
				<a href="http://<?=$silo->website?>" target="_blank"><?=$silo->website?></a>
			</td></tr>
			<?php } ?>
			</table>
		</td>
	</tr>
	<tr class="infoSpacer"></tr>
	<tr>
		<td class="siloInnerInfo<?=$closed_silo?>">
			<div align="left">
		<span class='voucher'>
		<?php if ($disaster) { ?>
			This silo was sanctioned by the benefiting organization. The amount raised (shown on this page), represents 90% of actual funds raised. Of the 10% not represented in the status bar, roughly 7.5% goes to <?=SHORT_URL?>, and rougly 2.5% goes to our payment gateway.</span><br><br>
		<?php } else { ?>
			Donate only to local causes that you know or have researched!</span><br><br>
		<?php
 			$Vouch = new Vouch();
 			$Flag = new Flag();
 			$flag_ids = $Flag->GetIds();
			$vouchTotal = $Vouch->GetHasPersonallyKnownCount($silo->silo_id) + $Vouch->GetHasResearchedCount($silo->silo_id);
			$pctV = $vouchTotal/($silo->getTotalMembers());
			$pctVouch = round($pctV * 100);
			$friend_count = $silo->getAdminFCount();
		?>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt">A 'Familiarity Index' is a visual representation of a silo's likely legitimacy. <a class="fancybox" href="#learn-fam-index">(learn more)</a></div>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt"><?php if ($friend_count) { echo "This Administrator is Facebook Connected with ".$friend_count." friends."; } else { echo "This Administrator is <b><u>not</u></b> connected with Facebook."; } ?></div>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 10pt">This Administrator is <?php if(!$linkedin_con){ echo "<b><u>not</u></b>"; }?> connected with LinkedIn.</div>
			<div class="voucherText<?=$closed_silo?>" style="font-size: 6pt">The 'Familiarity Index' is not a guarantee this silo is legitimate, and <?=SHORT_URL?> is not responsible, and is harmless and not liable, for fraud, misrepresentation, or other legal infractions committed by silo administrators. Read our Terms of Use and FAQ for more information.</div>
		<?php include('include/UI/flag_box_silo.php'); } ?>
			<center>Silo ID: <?=$silo->id?></center>
		</div>
		</td>
	</tr>
</table>

<?php } ?>

<div class="edit_item" id="edit_silo">
<form enctype="multipart/form-data"  name="manage_silo_form" class="manage_silo_form" method="POST">
		<input type="hidden" name="task" value="manage_silo"/>
		
		<table cellpadding="10px">
			<tr>
				<td align="center" valign="top" width="650px">
					<img src="<?php echo 'uploads/silos/'.$Silo->photo_file.'?'.$Silo->last_update;?>" width="300px"/>
					<br/><br/>
					<b>Upload new photo: </b><input name="silo_photo" type="file" style="height: 24px" />
					<br/><br/>

					<table>
						<tr>
							<td valign="center" style="width: 120px;"><b>Silo Full Name: </b></td>
							<td><input type="text" name="name" style="width : 300px" value='<?php echo $Silo->name; ?>'/></td>
						</tr>
						<tr>
							<td valign="center"><b>Silo Short Name: </b></td>
							<td><input type="text" name="shortname" style="width : 300px" value='<?php echo $Silo->shortname; ?>'/></td>
						</tr>						
						<tr>
							<td>
								<b>Address:</b>
							</td>
							<td>
								<input type="text" name="address" style="width : 300px" value='<?php echo $Silo->address; ?>'/>
							</td>
						</tr>
					<?php if ($silo->silo_type == "public") { ?>
						<tr>
							<td>
								<b>Organization:</b><br/>
							</td>
							<td>
								<input type="text" name="org_name" style="width : 300px" value='<?php echo $Silo->org_name; ?>'/>
							</td>
						</tr>
					<?php } ?>					
						<tr>
							<td>
								<b>Phone Number:</b>
							</td>
							<td>
								<input type="text" name="phone_number" style="width : 150px" value='<?php echo $Silo->phone_number; ?>'/>
							</td>
						</tr>
						<tr>
							<td>
								<b>Website:</b>
							</td>
							<td>
								<input type="text" name="website" style="width : 150px" value='<?php echo $Silo->website; ?>'/>
							</td>
						</tr>

						<tr>
							<td colspan=2><br/></td>
						</tr>
			<?php if ($Silo->silo_type == "public") { ?>
						
						<tr>
							<td colspan=2><b>Organization purpose: </b>
							<?php
								echo $Silo->org_purpose;
							?>
							</td>
						</tr>
			<?php } ?>
						<tr>
							<td colspan=2><b>Silo purpose: </b>
							<?php
								echo $Silo->silo_purpose;
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

<div style="display: none; width: 400px; font-size: 10pt;" id="learn-fam-index">
	<h3>How do we determine a silo's Familiarity Index?</h3>
	We use a proprietary formula, based on the percentage of people who know or who have researched a silo, whether a silo administrator is Facebook or LinkedIn connected, and a variety of other factors we prefer to keep secret (so they cannot be circumvented), to compute our 'Familiarity Index'.  
</div>