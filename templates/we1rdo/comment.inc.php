<?php
	# First retrieve the needed parameters
	$messageId = $tplHelper->getParam('messageid');
	$pageNr = $tplHelper->getParam('pagenr');
	
	# we halen 5 spots per request op
	$comments = $tplHelper->getSpotComments($messageId, ($pageNr * 5), 5);
	$comments = $tplHelper->formatComments($comments);
	
	foreach($comments as $comment) {
			if ($comment['verified']) {
?>
					<li class="<?php $count++; echo ($count % 2 ? "odd" : "even"); ?>"> <strong> Gepost door <span class="user"><?php echo $comment['fromhdr']; ?></span> (<a class="userid" target = "_parent" href="<?php echo $tplHelper->makeUserIdUrl($comment); ?>" title='Zoek naar spots van "<?php echo $comment['from']; ?>"'><?php echo $comment['userid']; ?></a>) @ <?php echo $tplHelper->formatDate($comment['stamp'], 'comment'); ?> </strong> <br>
						<?php echo join("<br>", $comment['body']); ?>
					</li>
<?php	
			} # if
	} # for
