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

<form class="postcommentform" name="postcommentform" action="<?php echo $tplHelper->makePostCommentAction(); ?>" method="post" onsubmit="new spotPosting().postComment(this,postCommentUiStart,postCommentUiDone); return false;">
    <input type="hidden" name="postcommentform[submit]" value="Post">
    <input type="hidden" name="postcommentform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('postcommentform'); ?>">
    <input type="hidden" name="postcommentform[inreplyto]" value="<?php echo htmlspecialchars($spot['messageid']); ?>">
    <input type="hidden" name="postcommentform[newmessageid]" value="">
    <input type="hidden" name="postcommentform[randomstr]" value="<?php echo $tplHelper->getCleanRandomString(4); ?>">
	<fieldset>
		<dl>
			<dt><label for="postcommentform[rating]">Rating</label></dt>
			<dd><input type="text" name="postcommentform[rating]" value="0"></dd>

			<dt><label for="postcommentform[body]">Text</label></dt>
			<dd><textarea name="postcommentform[body]"></textarea></dd>

			<dd><input type="submit" name="dummysubmit" value="Post"></dd>
		</dl>
	</fieldset>
</form>
<?php
	}
?>