<?php
	require_once "includes/basic-html-header.inc.php";
?>
			<div class='permdenied'>
				<p>
					<?php echo sprintf(_('Access denied for [%s (%d::%s)]'), $tplHelper->permToString($exception->getPermId()), $exception->getPermId(), $exception->getObject()); ?> </strong>
				</p>
			</div>
		</div>
	
	<br>
	
	<?php
		/* Als de user nog niet ingelogged is, geven we hem - mits hij dat recht heeft - de mogelijkheid in te loggen */
		if ($tplHelper->allowed(SpotSecurity::spotsec_perform_login, '') && ($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid'))) {
			# loginform verwacht deze drie variables door de renderer, dus die faken we
			$data['performredirect'] = true;
			$data['renderhtml'] = true;
			$data['htmlheaderssent'] = true;
			$loginform = array('username' => '', 'password' => '');
			$http_referer = $tplHelper->makeSelfUrl('');
						
			require "login.inc.php";
		} else {
			echo "</body></html>";
		} # else
	?>
