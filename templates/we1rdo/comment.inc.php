<?php
	# First retrieve the needed parameters
	isset($messageId) ? false : $messageId = $tplHelper->getParam('messageid');
	isset($pageNr) ? false : $pageNr = $tplHelper->getParam('pagenr');
	isset($perPage) ? false : $perPage = $tplHelper->getParam('perpage');
	
	/*
	 * We retrieve the fullspot as well because we want to compare the spotterids.
	 * This operation is rather cheap because we already have the fullspot cached
	 * in the database
	 */
	isset($spot) ? false : $spot = $tplHelper->getFullSpot($messageId, true);

	# is the user allowed to blacklist spotters?
	$perm_allow_blackList = ($tplHelper->allowed(SpotSecurity::spotsec_blacklist_spotter, ''));
	
	# Get the spot comments for each $perPage comments
	$comments = $tplHelper->getSpotComments($messageId, ($pageNr * $perPage), $perPage);
	$comments = $tplHelper->formatComments($comments);

	# Does the user want to see avatars?
	$show_avatars = $currentSession['user']['prefs']['show_avatars'];
	
	foreach($comments as $comment) {
		if ($comment['verified']) {
			$commenterIsPoster = ($comment['spotterid'] == $spot['spotterid']);
			$commentIsModerated = ($comment['moderated']);
			$allow_blackList = (($perm_allow_blackList) && (!empty($comment['spotterid'])) && ($comment['idtype'] != 1));

			if($comment['spotrating'] == 0) {
				$rating = '';
			} elseif($comment['spotrating'] > 0) {
				$rating = '<span class="rating" title="' . sprintf(ngettext("%s gave this spot %d star", "%s gave this spot %d stars", $comment['spotrating']), $comment['fromhdr'], $comment['spotrating']) . '"><span style="width:' . $comment['spotrating'] * 4 . 'px;"></span></span>';
			}
?>

					<li<?php if ($commenterIsPoster) { echo ' class="poster"'; } ?>><?php if ($show_avatars) { ?><img class="commentavatar" src='<?php echo $tplHelper->makeCommenterImageUrl($comment); ?>'><?php } ?><strong> <?php echo $rating; ?><?php echo sprintf(_('Posted by %s'), '<span class="user">' . $comment['fromhdr'] . '</span>'); ?>
					(<a class="spotterid" target = "_parent" href="<?php echo $tplHelper->makeSpotterIdUrl($comment); ?>" title='<?php echo sprintf(_('Find spots from %s'), $comment['fromhdr']); ?>'><?php echo $comment['spotterid']; ?></a>
					<?php if ($allow_blackList) { ?> <a class="delete blacklistuserlink_<?php echo htmlspecialchars($comment['spotterid']); ?>" title="<?php echo _('Blacklist this sender'); ?>" onclick="blacklistSpotterId('<?php echo htmlspecialchars($comment['spotterid']); ?>');">&nbsp;&nbsp;&nbsp;</a><?php } ?>
					) @ <?php echo $tplHelper->formatDate($comment['stamp'], 'comment'); ?> </strong> 
					<br />
					<?php if ($commentIsModerated) { echo _('This comment is moderated') . '<br /><br />'; } ?>
					<?php echo join("<br>", $comment['body']); ?>
					</li>
<?php	
			} # if
	} # for
