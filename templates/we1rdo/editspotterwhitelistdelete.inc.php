<?php
	# vraag de opgegeven securitygroup op
	$wlSpotter = $tplHelper->getWhitelistForSpotterId($data['spotterid']);

	# bereid alvast een UL voor voor de errors e.d., worden er later
	# via AJAX ingegooid
	include "includes/form-messages.inc.php";
?>

	<!-- Security group wissen -->
	<fieldset>
		<form class="whitelistspotterform" name="whitelistspotterform" action="<?php echo $tplHelper->makeEditWhitelistAction(); ?>" method="post">
			<input type="hidden" name="whitelistspotterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('whitelistspotterform'); ?>">
			<input type="hidden" name="whitelistspotterform[spotterid]" value="<?php echo $wlSpotter['spotterid']; ?>">

			<dt>
				Bevestig
			</td>
			<dd>
				<input class="smallGreyButton" type="submit" name="whitelistspotterform[submitremovespotterid]" value="<?php echo _('Delete'); ?>">
			</dd>
		</form>
	</fieldset>
