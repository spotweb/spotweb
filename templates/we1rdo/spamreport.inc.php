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

<form class="postreportform" name="postreportform" action="<?php echo $tplHelper->makeReportAction(); ?>" method="post">
	<input type="hidden" name="postreportform[submit]" value="Post">
	<input type="hidden" name="postreportform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('postreportform'); ?>">
	<input type="hidden" name="postreportform[inreplyto]" value="<?php echo htmlspecialchars($spot['messageid']); ?>">
	<input type="hidden" name="postreportform[newmessageid]" value="">
	<input type="hidden" name="postreportform[randomstr]" value="<?php echo $tplHelper->getCleanRandomString(4); ?>">
<!--	
	<fieldset>
		<dl>
			<dd><input class="greyButton" type="submit" name="dummysubmit" title="Markeer als spam" value="Markeer als spam"></dd>
		</dl>
	</fieldset>
-->	
</form>

<?php
	}
?>