<?php
	# First retrieve the needed parameters
	isset($messageId) ? : $messageId = $tplHelper->getParam('messageid');
	isset($pageNr) ? : $pageNr = $tplHelper->getParam('pagenr');
	isset($perPage) ? : $perPage = $tplHelper->getParam('perpage');
	
	/*
	 * We retrieve the fullspot as well because we want to compare the spotterids.
	 * This operation is rather cheap because we already have the fullspot cached
	 * in the database
	 */
	isset($spot) ? : $spot = $tplHelper->getFullSpot($messageId, true);
	
	# Get the spot comments for each $perPage comments
	$comments = $tplHelper->getSpotComments($messageId, ($pageNr * $perPage), $perPage);
	$comments = $tplHelper->formatComments($comments);

	# Does the user want to see avatars?
	$show_avatars = $currentSession['user']['prefs']['show_avatars'];
	
	
	foreach($comments as $comment) {
		if ($comment['verified']) {
			$commenterIsPoster = ($comment['spotterid'] == $spot['spotterid']);

			if($comment['spotrating'] == 0) {
				$rating = '';
			} elseif($comment['spotrating'] == 1) {
				$rating = '<span class="rating" title="'.$comment['fromhdr'].' gaf deze spot '.$comment['spotrating'].' ster"><span style="width:' . $comment['spotrating'] * 4 . 'px;"></span></span>';
			} else {
				$rating = '<span class="rating" title="'.$comment['fromhdr'].' gaf deze spot '.$comment['spotrating'].' sterren"><span style="width:' . $comment['spotrating'] * 4 . 'px;"></span></span>';
			}
?>

					<li<?php if ($commenterIsPoster) { echo ' class="poster"'; } ?>><?php if ($show_avatars) { ?><img class="commentavatar" src='<?php echo $tplHelper->makeCommenterImageUrl($comment); ?>'><?php } ?><strong> <?php echo $rating; ?><?php echo sprintf(_('Posted by %s'), '<span class="user">' . $comment['fromhdr'] . '</span>'); ?>
					(<a class="spotterid" target = "_parent" href="<?php echo $tplHelper->makeSpotterIdUrl($comment); ?>" title='<?php echo sprintf(_('Find spots from %s'), $comment['fromhdr']); ?>'><?php echo $comment['spotterid']; ?></a>) @ <?php echo $tplHelper->formatDate($comment['stamp'], 'comment'); ?> </strong> <br />
					<?php echo join("<br>", $comment['body']); ?>
					</li>
<?php	
			} # if
	} # for
