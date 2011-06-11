<?php
	# vraag de opgegeven securitygroup op
	$securitygroup = $tplHelper->getSecGroup($data['groupid']);

	# bereid alvast een UL voor voor de errors e.d., worden er later
	# via AJAX ingegooid
	include "includes/form-messages.inc.php";
?>

	<!-- Security group wissen -->
	<fieldset>
		<form class="editsecgroupform" name="editsecgroupform" action="<?php echo $tplHelper->makeEditSecGroupAction(); ?>" method="post">
			<input type="hidden" name="editsecgroupform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsecgroupform'); ?>">
			<input type="hidden" name="editsecgroupform[http_referer]" value="<?php echo $http_referer; ?>">
			<input type="hidden" name="groupid" value="<?php echo $securitygroup['id']; ?>">

			<dt>
				Bevestig
			</td>
			<dd>
				<input class="smallGreyButton" type="submit" name="editsecgroupform[submitremovegroup]" value="Wissen">
			</dd>
		</form>
	</fieldset>
