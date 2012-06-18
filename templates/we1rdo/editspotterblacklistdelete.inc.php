<?php
	# vraag de opgegeven securitygroup op
	$blSpotter = $tplHelper->getBlacklistForSpotterId($data['spotterid']);

	# bereid alvast een UL voor voor de errors e.d., worden er later
	# via AJAX ingegooid
	include "includes/form-messages.inc.php";
?>

	<!-- Security group wissen -->
	<fieldset>
		<form class="blacklistspotterform" name="blacklistspotterform" action="<?php echo $tplHelper->makeEditBlacklistAction(); ?>" method="post">
			<input type="hidden" name="blacklistspotterform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('blacklistspotterform'); ?>">
			<input type="hidden" name="blacklistspotterform[spotterid]" value="<?php echo $blSpotter['spotterid']; ?>">
			<input type="hidden" name="blacklistspotterform[idtype]" value="1">

			<dt>
				Bevestig
			</td>
			<dd>
				<input class="smallGreyButton" type="submit" name="blacklistspotterform[submitremovespotterid]" value="<?php echo _('Delete'); ?>">
			</dd>
		</form>
	</fieldset>
