<?php
	# First retrieve the needed parameters
	$messageId = $tplHelper->getParam('messageid');
	$pageNr = $tplHelper->getParam('pagenr');
	
	# we halen 5 spots per request op
	$comments = $tplHelper->getSpotComments($messageId, ($pageNr * 5), 5);
	$comments = $tplHelper->formatComments($comments);
	
	# we moeten ook de spot zelf hebben zodat we de userid's kunnen vergelijken, dit
	# is op zich geen 'dure' operatie omdat de spot in de database zit.
	$spot = $tplHelper->getFullSpot($messageId);
	
	foreach($comments as $comment) {
			if ($comment['verified']) {
					$commenterIsPoster = ($comment['userid'] == $spot['userid']);
?>
					<li<?php if ($commenterIsPoster) { echo ' class="poster"'; } ?>> <strong> Gepost door <span class="user"><?php echo $comment['fromhdr']; ?></span> (<a class="userid" target = "_parent" href="<?php echo $tplHelper->makeUserIdUrl($comment); ?>" title='Zoek naar spots van "<?php echo $comment['fromhdr']; ?>"'><?php echo $comment['userid']; ?></a>) @ <?php echo $tplHelper->formatDate($comment['stamp'], 'comment'); ?> </strong> <br>
						<?php echo utf8_encode(join("<br>", $comment['body'])); ?>
					</li>
<?php	
			} # if
	} # for
