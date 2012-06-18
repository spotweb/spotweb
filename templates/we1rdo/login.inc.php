<?php
if (!empty($loginresult)) {
	if ((!isset($data['performredirect'])) || (($loginresult['result'] != 'success'))) {
		if (!isset($data['renderhtml'])) {
			include 'includes/form-xmlresult.inc.php';
		
			$this->sendContentTypeHeader('xml');
			echo formResult2Xml($loginresult, $formmessages, $tplHelper);
		} # if
	} else {
		$tplHelper->redirect($loginform['http_referer']);
	} # if
} # if

if (($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid')) && (empty($loginresult)) || (isset($data['renderhtml']))) {
	if (!isset($data['htmlheaderssent'])) {
		include "includes/basic-html-header.inc.php";

		$data['renderhtml'] = true;
	} # if
	include "includes/form-messages.inc.php"; 
?>
<form class="loginform" name="loginform" action="<?php echo $tplHelper->getPageUrl('login'); ?>" method="post">
	<input type="hidden" name="loginform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('loginform'); ?>">
	<input type="hidden" name="loginform[http_referer]" value="<?php echo $http_referer; ?>">
	<?php if (isset($data['performredirect'])) {?>
		<input type="hidden" name="data[performredirect]" value="<?php echo $data['performredirect']; ?>">
	<?php } ?>
	<?php if (isset($data['renderhtml'])) {?>
		<input type="hidden" name="data[renderhtml]" value="<?php echo $data['renderhtml']; ?>">
	<?php } ?>
	<fieldset>
		<dl>
			<dt><label for="loginform[username]"><?php echo _('Username'); ?></label></dt>
			<dd><input type="text" name="loginform[username]" value="<?php echo htmlspecialchars($loginform['username']); ?>"></dd>

			<dt><label for="loginform[password]"><?php echo _('Password'); ?></label></dt>
			<dd><input type="password" name="loginform[password]" value=""></dd>

			<dd><input class="greyButton" type="submit" name="loginform[submitlogin]" value="<?php echo _('Login'); ?>"></dd>
		</dl>
	</fieldset>
</form>
<?php
	}

	if (isset($data['renderhtml'])) {
		echo "</div></body></html>";
	} # if
?>

