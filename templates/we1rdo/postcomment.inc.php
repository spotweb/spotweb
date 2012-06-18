<?php
if (!empty($postresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	$this->sendContentTypeHeader('xml');
	echo formResult2Xml($postresult, $formmessages, $tplHelper);
} 

if (empty($postresult)) {
	if (isset($formmessages)) {
		include "includes/form-messages.inc.php"; 
	} # if
?>

<form class="postcommentform" name="postcommentform" action="<?php echo $tplHelper->makePostCommentAction(); ?>" method="post">
	<input type="hidden" name="postcommentform[submitpost]" value="Post">
	<input type="hidden" name="postcommentform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('postcommentform'); ?>">
	<input type="hidden" name="postcommentform[inreplyto]" value="<?php echo htmlspecialchars($spot['messageid']); ?>">
	<input type="hidden" name="postcommentform[newmessageid]" value="">
	<input type="hidden" name="postcommentform[randomstr]" value="<?php echo $tplHelper->getCleanRandomString(12); ?>">
	<fieldset>
		<dl>
			<dd class="rating"><input type="hidden" name="postcommentform[rating]" value="0"></dd>
			<dd><textarea name="postcommentform[body]" id="postcommentform[body]"></textarea></dd>
			<dd><input class="greyButton" type="submit" name="dummysubmit" title="<?php echo _('Add comment'); ?>" value="<?php echo _('Post'); ?>"></dd>
			<dd>
<?php
	$smileyList = $tplHelper->getSmileyList();
	foreach ($smileyList as $name => $image) {
		echo "<a onclick=\"addText(' [img=" . $name . "]', 'postcommentform[body]'); return false;\"><img src=\"" . $image . "\" alt=\"" . $name . "\" name=\"" . $name . "\"></a> ";
	}
?>
			</dd>
		</dl>
	</fieldset>
</form>
<?php
	}
?>