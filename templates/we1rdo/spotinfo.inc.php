<?php
	require_once "includes/header.inc.php";
	$spot = $tplHelper->formatSpot($spot);
	
	// We definieeren hier een aantal settings zodat we niet steeds dezelfde check hoeven uit te voeren
	$show_nzb_button = ( (!empty($spot['nzb'])) && 
						 ($spot['stamp'] > 1290578400) && 
						 ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, ''))
						);
	$show_watchlist_button = ($currentSession['user']['prefs']['keep_watchlist'] && $tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, ''));
	$allow_blackList = (($tplHelper->allowed(SpotSecurity::spotsec_blacklist_spotter, '')) && (!$tplHelper->isSpotterBlacklisted($spot['spotterid'])) && (!empty($spot['spotterid'])));

	/* Determine minimal width of the image, we cannot set it in the CSS because we cannot calculate it there */
	$imgMinWidth = 260;
	if (is_array($spot['image'])) {
		$imgMinWidth = min(260, $spot['image']['width']);
	} # if
	
?>

		<div id="details" class="details <?php echo $tplHelper->cat2color($spot) ?>">
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_report_spam, '')) {
		if ($currentSession['user']['userid'] > 2) { ?>
			<form class="postreportform" name="postreportform" action="<?php echo $tplHelper->makeReportAction(); ?>" method="post">
				<input type="hidden" name="postreportform[submit]" value="Post">
				<input type="hidden" name="postreportform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('postreportform'); ?>">
				<input type="hidden" name="postreportform[inreplyto]" value="<?php echo htmlspecialchars($spot['messageid']); ?>">
				<input type="hidden" name="postreportform[newmessageid]" value="">
				<input type="hidden" name="postreportform[randomstr]" value="<?php echo $tplHelper->getCleanRandomString(4); ?>">
			</form>
<?php } # if
	} # if 
?>
<?php if ($allow_blackList) { ?>
			<form class="blacklistspotterform" name="blacklistspotterform" action="<?php echo $tplHelper->makeBlacklistAction(); ?>" method="post">
				<input type="hidden" name="blacklistspotterform[submit]" value="Blacklist">
				<input type="hidden" name="blacklistspotterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('blacklistspotterform'); ?>">
				<input type="hidden" name="blacklistspotterform[spotterid]" value="<?php echo htmlspecialchars($spot['spotterid']); ?>">
				<input type="hidden" name="blacklistspotterform[origin]" value="Reported via Spotweb for spot <?php echo htmlspecialchars($spot['messageid']); ?>">
			</form>
<?php } # if ?>

			<table class="spotheader">
				<tbody>
					<tr>
						<th class="back"> <a class="closeDetails" title="<?php echo _('Ga terug naar het overzicht (esc / u)'); ?>">&lt;&lt;</a> </th>
						<th class="category"><span><?php echo $spot['formatname'];?></span></th>
						<th class="title"><?php echo $spot['title'];?></th>
						<th class="rating">
<?php
	if($spot['rating'] == 0) {
		echo '<span class="rating" title="Deze spot heeft nog geen rating"><span style="width:0px;"></span></span>';
	} elseif($spot['rating'] == 1) {
		echo '<span class="rating" title="' . _('Deze spot heeft 1 ster') . '"><span style="width:' . $spot['rating'] * 4 . 'px;"></span></span>';
	} else {
		echo '<span class="rating" title="' . sprintf(_('Deze spot heeft %d sterren'), $spot['rating']) . '"><span style="width:' . $spot['rating'] * 4 . 'px;"></span></span>';
	}
?>
						</th>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_report_spam, '')) {
		if ($currentSession['user']['userid'] > 2) {
			if (!$tplHelper->isReportPlaced($spot['messageid'])) {
?>
						<th class="spamreport"><a onclick="$('form.postreportform').submit();" class="spamreport-button" title="<?php echo _('Rapporteer deze spot als spam'); ?>"></a> </th>
<?php 		} else { ?>
						<th class="spamreport"><a onclick="return false;" class="spamreport-button success" title="<?php echo _('Deze spot heb jij als spam gerapporteerd'); ?>"></a> </th>
<?php 	}	} } ?>
						<th class="nzb">
<?php if ($show_nzb_button) { ?>
							<a class="nzb<?php if ($spot['hasbeendownloaded']) { echo " downloaded"; } ?>" href="<?php echo $tplHelper->makeNzbUrl($spot); ?>" title="<?php echo _('Download NZB'); if ($spot['hasbeendownloaded']) {echo _('(deze spot is al gedownload)');} echo " (n)"; ?>"></a>
<?php } ?>				</th>
						<th class="search"><a href="<?php echo $spot['searchurl'];?>" title="<?php echo _('NZB zoeken');?>"></a></th>
<?php if ($show_watchlist_button) {
echo "<th class='watch'>";
echo "<a class='remove watchremove_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].")\""; if($spot['isbeingwatched'] == false) { echo " style='display: none;'"; } echo " title='" . _('Verwijder uit watchlist (w)') . "'> </a>";
echo "<a class='add watchadd_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].")\""; if($spot['isbeingwatched'] == true) { echo " style='display: none;'"; } echo " title='" . _('Plaats in watchlist (w)') . "'> </a>";
echo "</th>";
} ?>
<?php if ((!empty($spot['nzb'])) && (!empty($spot['sabnzbdurl']))) { ?>
<?php if ($spot['hasbeendownloaded']) { ?>
						<th class="sabnzbd"><a onclick="downloadSabnzbd(<?php echo "'".$spot['id']."','".$spot['sabnzbdurl']."'"; ?>)" class="<?php echo "sab_".$spot['id'].""; ?> sabnzbd-button succes" title="<?php echo _('Add NZB to SabNZBd queue (you already downloaded this spot) (s)'); ?>"> </a></th>
<?php } else { ?>
						<th class="sabnzbd"><a onclick="downloadSabnzbd(<?php echo "'".$spot['id']."','".$spot['sabnzbdurl']."'"; ?>)" class="<?php echo "sab_".$spot['id'].""; ?> sabnzbd-button" title="<?php echo _('Add NZB to SabNZBd queue (s)'); ?>"> </a></th>
<?php } } ?>
					</tr>
				</tbody>
			</table>
			<table class="spotdetails">
				<tr>
					<td class="img" style="min-width:<?php echo $imgMinWidth; ?>px;">
						<a onclick="toggleImageSize()" class="postimage">
							<img class="spotinfoimage" src="<?php echo $tplHelper->makeImageUrl($spot, 260, 260); ?>" alt="<?php echo $spot['title'];?>">
						</a>
					</td>
					<td class="info">
<?php if (!$spot['verified'] || $tplHelper->isModerated($spot)) {
	echo "<div class='warning'>";
	if (!$spot['verified']) {
		echo _("Deze spot is niet geverifi&euml;erd, de naam van de poster is niet bevestigd!") . "<br>";
	}
	if ($tplHelper->isModerated($spot)) {
		echo _("Deze spot is als mogelijk onwenselijk gemodereerd!") . "<br>";
	}
	if ($tplHelper->isSpotterBlacklisted($spot['spotterid'])) {
		echo _("De spotter staat op een blacklist!") . "<br>";
	}
	echo "</div>";
} ?>
						<table class="spotinfo">
							<tbody>
								<tr><th> <?php echo _('Categorie'); ?> </th> <td><a href="<?php echo $tplHelper->makeCatUrl($spot); ?>" title='<?php echo _('Zoek spots in de categorie'); ?> "<?php echo $spot['catname']; ?>"'><?php echo $spot['catname']; ?></a></td> </tr>
<?php
	if (!empty($spot['subcatlist'])) {
		foreach($spot['subcatlist'] as $sub) {
			$subcatType = substr($sub, 0, 1);
			echo "\t\t\t\t\t\t<tr><th> " . SpotCategories::SubcatDescription($spot['category'], $subcatType) .  "</th>";
			echo "<td><a href='" . $tplHelper->makeSubCatUrl($spot, $sub) . "' title='" . _('Zoek spots in de categorie') . ' ' . SpotCategories::Cat2Desc($spot['category'], $sub) . "'>" . SpotCategories::Cat2Desc($spot['category'], $sub) . "</a></td> </tr>\r\n";
		} # foreach
	} # if
?>
								<tr><th> <?php echo _('Omvang'); ?> </th> <td> <?php echo $tplHelper->format_size($spot['filesize']); ?> </td> </tr>
								<tr><td class="break" colspan="2">&nbsp;</td> </tr>
								<tr><th> <?php echo _('Website'); ?> </th> <td> <a href='<?php echo $spot['website']; ?>'><?php echo $spot['website'];?></a> </td> </tr>
								<tr> <td class="break" colspan="2">&nbsp;</td> </tr>
								<tr> <th> <?php echo _('Afzender'); ?> </th> <td> <a href="<?php echo $tplHelper->makePosterUrl($spot); ?>" title='<?php echo sprintf(_('Zoek naar spots van "%s"'), $spot['poster']); ?>'><?php echo $spot['poster']; ?></a>
								<?php if (!empty($spot['spotterid'])) { ?> (<a href="<?php echo $tplHelper->makeSpotterIdUrl($spot); ?>" title='<?php echo sprintf(_('Zoek naar spots van "%s"'), $spot['spotterid']);?>'><?php echo $spot['spotterid']; ?></a>)<?php } ?>
								<?php if ($allow_blackList) { ?> <a class="delete" id="blacklistuserlink" title="<?php echo _('Deze spotter blacklisten'); ?>" onclick="$('form.blacklistspotterform').submit();">&nbsp;&nbsp;&nbsp;</a><?php } ?>
								</td> </tr>
								<tr> <th> <?php echo _('Tag'); ?> </th> <td> <a href="<?php echo $tplHelper->makeTagUrl($spot); ?>" title='<?php echo sprintf(_('Zoek naar spots met de tag "%s"'), $spot['tag']); ?>'><?php echo $spot['tag']; ?></a> </td> </tr>
								<tr> <td class="break" colspan="2">&nbsp;</td> </tr>
								<tr> <th> <?php echo _('Zoekmachine'); ?></th> <td> <a href='<?php echo $spot['searchurl']; ?>'><?php echo _('Zoek'); ?></a> </td> </tr>
<?php if ($show_nzb_button) { ?>		
								<tr> <th> <?php echo _('NZB'); ?></th> <td> <a href='<?php echo $tplHelper->makeNzbUrl($spot); ?>' title='<?php echo _('Download NZB (n)'); ?>'><?php echo _('NZB'); ?></a> </td> </tr>
<?php } ?>

								<tr> <td class="break" colspan="2">&nbsp;</td> </tr>
								<tr> <th> <?php echo _('Aantal spam reports'); ?> </th> <td> <?php echo $spot['reportcount']; ?> </td> </tr>
							</tbody>
						</table>
					</td>
				</tr>
			</table>
			<div class="description">
				<h4><?php echo _('Post Description'); ?></h4>
				<pre><?php echo $spot['description']; ?></pre>
			</div>
			
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_comments, '')) { ?>
			<div class="comments" id="comments">
				<h4><?php echo _('Comments'); ?> <span class="commentcount"># 0</span></h4>
				<ul id="commentslist">
<?php 
if ($tplHelper->allowed(SpotSecurity::spotsec_post_comment, '')) { 
	if ($currentSession['user']['userid'] > 2) { 
		echo "<li class='addComment'>";
		echo "<a class='togglePostComment' title='" . _('Reactie toevoegen (uitklappen)') . "'>" . _('Reactie toevoegen') . "<span></span></a><div><div></div>";
		include "postcomment.inc.php"; 
		echo "</div></li>";
	}
} ?>
				</ul>
			</div>
<?php } ?>
		</div>
		
		<input type="hidden" id="messageid" value="<?php echo $spot['messageid'] ?>" />
		<script type="text/javascript">
			$(document).ready(function(){
				$("#details").addClass("external");

				$("a[href^='http']").attr('target','_blank');

				$("a.closeDetails").click(function(){ 
					window.close();
				});

				var messageid = $('#messageid').val();
				postCommentsForm();
				postReportForm();
				postBlacklistForm();
				loadSpotImage();
				loadComments(messageid,spotweb_retrieve_commentsperpage,'0');
			});

			function addText(text,element_id) {
				document.getElementById(element_id).value += text;
			}
		</script>
<?
require_once "includes/footer.inc.php";
