<?php
	require_once __DIR__ . '/includes/header.inc.php';
	$spot = $tplHelper->formatSpot($spot);

	// We definieeren hier een aantal settings zodat we niet steeds dezelfde check hoeven uit te voeren
	$show_nzb_button = ( (!empty($spot['nzb'])) && 
						 ($spot['stamp'] > 1290578400) && 
						 ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, ''))
						);
	$show_watchlist_button = ($currentSession['user']['prefs']['keep_watchlist'] && $tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, ''));
	$allowedToPost = $tplHelper->allowedToPost();
	$isBlacklisted = ($spot['listidtype'] == 1);
	$isWhitelisted = ($spot['listidtype'] == 2); 
	$allow_blackList = (($tplHelper->allowed(SpotSecurity::spotsec_blacklist_spotter, '')) && ($allowedToPost) && (!$isBlacklisted) && (!empty($spot['spotterid'])));
	$allow_whiteList = (($tplHelper->allowed(SpotSecurity::spotsec_blacklist_spotter, '')) && ($allowedToPost) && (!$isBlacklisted) && (!$isWhitelisted) && (!empty($spot['spotterid'])));
	$show_spot_edit = $tplHelper->allowed(SpotSecurity::spotsec_show_spot_was_edited, '');
	$show_editor = $tplHelper->allowed(SpotSecurity::spotsec_view_spot_editor, '');

	/* Determine minimal width of the image, we cannot set it in the CSS because we cannot calculate it there */
	$imgMinWidth = 260;
	if (is_array($spot['image'])) {
		$imgMinWidth = min(260, $spot['image']['width']);
	} # if
	
?>

		<div id="details" class="details <?php echo $tplHelper->cat2CssClass($spot) ?>">
			<form class="blacklistspotterform" name="blacklistspotterform" action="<?php echo $tplHelper->makeListAction(); ?>" method="post">
				<input type="hidden" name="blacklistspotterform[submitaddspotterid]" value="Blacklist">
				<input type="hidden" name="blacklistspotterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('blacklistspotterform'); ?>">
				<input type="hidden" name="blacklistspotterform[spotterid]" value="<?php echo htmlspecialchars($spot['spotterid']); ?>">
				<input type="hidden" name="blacklistspotterform[origin]" value="Reported via Spotweb for spot <?php echo htmlspecialchars($spot['messageid']); ?>">
				<input type="hidden" name="blacklistspotterform[idtype]" value="1">
			</form>

			<table class="spotheader">
				<tbody>
					<tr>
						<th class="back"> <a class="closeDetails" title="<?php echo _('Back to mainview (ESC / U)'); ?>">&lt;&lt;</a> </th>
						<th class="category"><span><?php echo $spot['formatname'];?></span></th>
						<th class="title"><?php echo $spot['title'];?></th>
						<th class="rating">
<?php
	if($spot['rating'] == 0) {
		echo '<span class="rating" title="' . _('This spot has no rating yet') . '"><span style="width:0px;"></span></span>';
	} elseif($spot['rating'] > 0) {
		echo '<span class="rating" title="' . sprintf(ngettext('This spot thas %d star', 'This spot has %d stars', $spot['rating']), $spot['rating']) . '"><span style="width:' . $spot['rating'] * 4 . 'px;"></span></span>';
	}
?>
						</th>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_report_spam, '')) {
		if ($currentSession['user']['userid'] > 2) {
			if (!$tplHelper->isReportPlaced($spot['messageid'])) {
?>
						<th class="spamreport"><a onclick="openDialog('editdialogdiv', '<?php echo _('Report spam'); ?>', '?page=render&tplname=reportspot&data[messageid]=<?php echo urlencode($spot['messageid']); ?>', postReportForm, 'autoclose', null, null);" class="spamreport-button" title="<?php echo _('Report this spot as spam'); ?>"></a> </th>
<?php 		} else { ?>
						<th class="spamreport"><a onclick="return false;" class="spamreport-button success" title="<?php echo _('You already reported this spot as spam'); ?>"></a> </th>
<?php 	}	} } ?>
						<th class="nzb">
<?php if ($show_nzb_button) { ?>
							<a class="nzb<?php if ($spot['hasbeendownloaded']) { echo " downloaded"; } ?>" href="<?php echo $tplHelper->makeNzbUrl($spot); ?>" title="<?php echo _('Download NZB'); if ($spot['hasbeendownloaded']) {echo _('(this spot has already been downloaded)');} echo " (n)"; ?>"></a>
<?php } ?>				</th>
						<th class="search"><a href="<?php echo $spot['searchurl'];?>" title="<?php echo _('Find NZB');?>" rel="nofollow"></a></th>
<?php if ($show_watchlist_button) {
echo "<th class='watch'>";
echo "<a class='remove watchremove_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].")\""; if($spot['isbeingwatched'] == false) { echo " style='display: none;'"; } echo " title='" . _('Delete from watchlist (w)') . "'> </a>";
echo "<a class='add watchadd_".$spot['id']."' onclick=\"toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].")\""; if($spot['isbeingwatched'] == true) { echo " style='display: none;'"; } echo " title='" . _('Place in watchlist (w)') . "'> </a>";
echo "</th>";
} ?>
<?php if ((!empty($spot['nzb'])) && (!empty($spot['sabnzbdurl']))) { ?>
<?php if ($spot['hasbeendownloaded']) { ?>
						<th class="sabnzbd"><a onclick="downloadSabnzbd(<?php echo "'".$spot['id']."','".$spot['sabnzbdurl']."','" . $spot['nzbhandlertype'] . "'"; ?>)" class="<?php echo "sab_".$spot['id'].""; ?> sabnzbd-button succes" title="<?php echo _('Add NZB to SABnzbd queue (you already downloaded this spot) (s)'); ?>"> </a></th>
<?php } else { ?>
						<th class="sabnzbd"><a onclick="downloadSabnzbd(<?php echo "'".$spot['id']."','".$spot['sabnzbdurl']."','" . $spot['nzbhandlertype'] . "'"; ?>)" class="<?php echo "sab_".$spot['id'].""; ?> sabnzbd-button" title="<?php echo _('Add NZB to SABnzbd queue (s)'); ?>"> </a></th>
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
<?php if (!$spot['verified'] || $tplHelper->isModerated($spot) || $isBlacklisted) {
	echo "<div class='warning'>";
	if (!$spot['verified']) {
		echo _('This spot is not verified, the name of the sender has not been confirmed') . "<br>";
	}
	if ($tplHelper->isModerated($spot)) {
		echo _('This spot is marked as potentional spam') . "<br>";
	}
	if ($isBlacklisted) {
		echo _('This spotter is already blacklisted') . "<br>";
	}
	echo "</div>";
} ?>
<?php if ($isWhitelisted) {
	echo "<div class='announce'>";
	echo _('This spotter is already whitelisted') . "<br>";
	echo "</div>";
} ?>
						<table class="spotinfo">
							<tbody>
								<tr><th> <?php echo _('Category'); ?> </th> <td><a href="<?php echo $tplHelper->makeCatUrl($spot); ?>" title='<?php echo _('Find spots in this category'); ?> "<?php echo $spot['catname']; ?>"'><?php echo $spot['catname']; ?></a></td> </tr>
<?php
		foreach(array('a', 'b', 'c', 'd', 'z') as $subcatType) {
            $subList = explode('|', $spot['subcat' . $subcatType]);
            foreach($subList as $sub) {
                if (!empty($sub)) {
                    echo "\t\t\t\t\t\t<tr><th> " . SpotCategories::SubcatDescription($spot['category'], $subcatType) .  "</th>";
                    echo "<td><a href='" . $tplHelper->makeSubCatUrl($spot, $sub) . "' title='" . _('Find spots in this category') . ' ' . SpotCategories::Cat2Desc($spot['category'], $sub) . "'>" . SpotCategories::Cat2Desc($spot['category'], $sub) . "</a></td> </tr>\r\n";
                } # if
            } # if
		} # foreach
?>
								<tr><th> <?php echo _('Date'); ?> </th> <td title='<?php echo $tplHelper->formatDate($spot['stamp'], 'force_spotlist'); ?>'> <?php echo $tplHelper->formatDate($spot['stamp'], 'spotdetail'); ?> </td> </tr>
								<tr><th> <?php echo _('Size'); ?> </th> <td> <?php echo $tplHelper->format_size($spot['filesize']); ?> </td> </tr>
                                <?php if (!empty($spot['newsreader'])) {?>
								   <tr><th> <?php echo _('Spotted with'); ?> </th> <td> <?php echo htmlspecialchars($spot['newsreader']);?> </td> </tr>
                                <?php } ?>
								<tr><td class="break" colspan="2">&nbsp;</td> </tr>
								<tr><th> <?php echo _('Website'); ?> </th> <td> <a href='<?php echo $spot['website']; ?>' rel="nofollow"><?php echo $spot['website'];?></a> </td> </tr>
								<tr> <td class="break" colspan="2">&nbsp;</td> </tr>
								<tr> <th> <?php echo _('Sender'); ?> </th> <td> <a href="<?php echo $tplHelper->makePosterUrl($spot); ?>" title='<?php echo sprintf(_('Find spots from %s'), $spot['poster']); ?>'><?php echo $spot['poster']; ?></a>
								<?php if (!empty($spot['spotterid'])) { ?> (<a href="<?php echo $tplHelper->makeSpotterIdUrl($spot); ?>" title='<?php echo sprintf(_('Find spots from %s'), $spot['spotterid']);?>'><?php echo $spot['spotterid']; ?></a>)<?php } ?>
								<?php if ($allow_blackList) { ?> <a class="delete blacklistuserlink_<?php echo htmlspecialchars($spot['spotterid']); ?>" title="<?php echo _('Blacklist this sender'); ?>" onclick="blacklistSpotterId('<?php echo htmlspecialchars($spot['spotterid']); ?>');">&nbsp;&nbsp;&nbsp;</a><?php } ?>
								<?php if ($allow_whiteList) { ?> <a class="whitelist blacklistuserlink_<?php echo htmlspecialchars($spot['spotterid']); ?>" title="<?php echo _('Whitelist this sender'); ?>" onclick="whitelistSpotterId('<?php echo htmlspecialchars($spot['spotterid']); ?>');">&nbsp;&nbsp;&nbsp;</a><?php } ?>
								<?php if ((!empty($spot['spotterid'])) && ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, ''))) { ?> <a href="" class="addspotterasfilter" title="<?php echo _("Add filter for this spotter"); ?>" onclick="addSpotFilter('<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>', 'SpotterID', '<?php echo urlencode($spot['spotterid']); ?>', 'Zoek spots van &quot;<?php echo urlencode($spot['poster']); ?>&quot;', 'addspotterasfilter'); return false; ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a> <?php } ?> 
								</td> </tr>
								<tr> <th> <?php echo _('Tag'); ?> </th> <td> <a href="<?php echo $tplHelper->makeTagUrl($spot); ?>" title='<?php echo sprintf(_('Search spots with the tag: %s'), $spot['tag']); ?>'><?php echo $spot['tag']; ?></a> 
								<?php if ((!empty($spot['tag'])) && ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, ''))) { ?> <a href="#" class="addtagasfilter" title="<?php echo _("Add filter for this tag"); ?>" onclick="addSpotFilter('<?php echo $tplHelper->generateXsrfCookie('editfilterform'); ?>', 'Tag', '<?php echo urlencode($spot['tag']); ?>', 'Zoek op tag &quot;<?php echo urlencode($spot['tag']); ?>&quot;', 'addtagasfilter'); return false; ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a> <?php } ?> 
								</td> </tr>
								<tr> <td class="break" colspan="2">&nbsp;</td> </tr>
								<tr> <th> <?php echo _('Searchengine'); ?></th> <td> <a href='<?php echo $spot['searchurl']; ?>'><?php echo _('Search'); ?></a> </td> </tr>
<?php if ($show_nzb_button) { ?>		
								<tr> <th> <?php echo _('NZB'); ?></th> <td> <a href='<?php echo $tplHelper->makeNzbUrl($spot); ?>' title='<?php echo _('Download NZB (n)'); ?>'><?php echo _('<img src="data:image/png;base64,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" alt="NZB" \>'); ?></a> </td> </tr>
<?php } ?>

								<tr> <td class="break" colspan="2">&nbsp;</td> </tr>
								<tr> <th> <?php echo _('Number of spamreports'); ?> </th> <td> <?php echo $spot['reportcount']; ?> </td> </tr>
<?php if ($show_spot_edit && $spot['editstamp']) { ?>
								<tr> <th> <?php echo _('Spot edited'); ?> </th> <td title='<?php echo $tplHelper->formatDate($spot['editstamp'], 'force_spotlist'); ?>'> <?php echo $tplHelper->formatDate($spot['editstamp'], 'spotdetail'); ?> <?php if ($show_editor) echo "(" . $spot['editor'] . ")"?></td> </tr>
<?php } ?>
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
		echo "<a class='togglePostComment' title='" . _('Add comment (open/close windows)') . "'>" . _('Add comment') . "<span></span></a><div><div></div>";
		require __DIR__ . '/postcomment.inc.php'; 
		echo "</div></li>";
	}
} ?>
				</ul>
			</div>
<?php } ?>
		</div>
		
		<input type="hidden" id="messageid" value="<?php echo $spot['messageid'] ?>" />
<?php
require_once __DIR__ . '/includes/footer.inc.php';
?>

<script type="text/javascript">
    $(document).ready(function(){
        // Attach an onLoad() listener to the image so we can bring the image into view
        loadSpotImage();

        $("#details").addClass("external");

        $("a[href^='http']").attr('target','_blank');

        $("a.closeDetails").click(function(){
            window.close();
        });

        var messageid = $('#messageid').val();
        postCommentsForm();
        postBlacklistForm();
        if (spotweb_retrieve_commentsperpage > 0) {
            loadComments(messageid,spotweb_retrieve_commentsperpage,'0');
        } // if
    });

    function addText(text,element_id) {
        document.getElementById(element_id).value += text;
    }
</script>
