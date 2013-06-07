<?php if ($silo->silo_type == "private" && !$showDiv) { ?>

<table class='siloInfo<?=$closed_silo?>' style="height: 730px">
	<tr>
		<td class="titleHeading">
			This silo is private. <br><br> You must be invited to this silo in order to pledge items towards it. Private silos are hidden from the general public.
		</td>
	</tr>
</table>

<?php } else { ?>

<table class='siloInfo<?=$closed_silo?>' >
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
			<a href='index.php?task=view_silo&id=<?=$silo->id;?>'><img src="<?php echo ACTIVE_URL.'uploads/silos/'.$silo->photo_file.'?'.$silo->last_update;?>" width='250px' class="siloImg"/>
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
				<img src="<?php echo ACTIVE_URL.'uploads/members/'.$admin->photo_file.'?'.$admin->last_update;?>" class="siloImg" width='100px'/><br>
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

<?php } ?>