<?php
require_once "templates/we1rdo/header.inc.php";

if (!empty($postresult)) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($postresult, $formmessages);
} # if

if (empty($postresult)) {
	include "includes/form-messages.inc.php"; 

?>
<form name="postcommentform" action="<?php echo $tplHelper->makePostCommentAction(); ?>" method="post" onsubmit="new spotPosting().postComment(this); return false;">
<input type="hidden" name="postcommentform[submit]" value="Post">
<input type="hidden" name="postcommentform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('postcommentform'); ?>">
<input type="hidden" name="postcommentform[inreplyto]" value="<?php echo htmlspecialchars($postcommentform['inreplyto']); ?>">
<input type="hidden" name="postcommentform[newmessageid]" value="<?php echo htmlspecialchars($postcommentform['newmessageid']); ?>">
<input type="hidden" name="postcommentform[randomstr]" value="<?php echo htmlspecialchars($postcommentform['randomstr']); ?>">
	<fieldset>
		<dl>
			<dt><label for="postcommentform[rating]">Rating</label></dt>
			<dd><input type="text" name="postcommentform[rating]" value="<?php echo htmlspecialchars($postcommentform['rating']); ?>"></dd>

			<dt><label for="postcommentform[body]">Text</label></dt>
			<dd><textarea name="postcommentform[body]"><?php echo htmlspecialchars($postcommentform['body']); ?></textarea></dd>

			<dd><input type="submit" name="dummysubmit" value="Post"></dd>
		</dl>
	</fieldset>
</form>
<?php
	}
?>