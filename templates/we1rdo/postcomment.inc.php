<?php
if (!empty($postresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($postresult, $formmessages, $tplHelper);
} 

if (empty($postresult)) {
	if (isset($formmessages)) {
		include "includes/form-messages.inc.php"; 
	} # if
?>

<form class="postcommentform" name="postcommentform" action="<?php echo $tplHelper->makePostCommentAction(); ?>" method="post">
	<input type="hidden" name="postcommentform[submit]" value="Post">
	<input type="hidden" name="postcommentform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('postcommentform'); ?>">
	<input type="hidden" name="postcommentform[inreplyto]" value="<?php echo htmlspecialchars($spot['messageid']); ?>">
	<input type="hidden" name="postcommentform[newmessageid]" value="">
	<input type="hidden" name="postcommentform[randomstr]" value="<?php echo $tplHelper->getCleanRandomString(4); ?>">
	<fieldset>
		<dl>
			<dd class="rating"><input type="hidden" name="postcommentform[rating]" value="0"></dd>
			<dd><textarea name="postcommentform[body]" id="postcommentform[body]"></textarea></dd>
			<dd><input class="greyButton" type="submit" name="dummysubmit" title="Reactie toevoegen" value="Post"></dd>
			<dd>
<?php
	$smileyList = $tplHelper->getSmileyList();
	foreach ($smileyList as $name => $image) {
		echo "<img onclick=\"addText(' [img=" . $name . "]', 'postcommentform[body]'); return false;\" src=\"" . $image . "\" alt=\"" . $name . "\" name=\"" . $name . "\"> ";
	}
?>
			</dd>
		</dl>
	</fieldset>
</form>
<?php
	}
?>