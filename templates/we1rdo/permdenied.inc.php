<html>
	<head>
		<style type='text/css'>
			div.permdenied {
				border: 1px solid red;
				background-color: #fef1fc;
				color: #cd0a0a;

				font-size: 0.9em;
				
				margin-top: 4px;
				margin-left: auto;
				margin-right: auto;
				
				border-top-left-radius: 4px;
				border-top-right-radius: 4px;
				border-bottom-right-radius: 4px;
				border-bottom-left-radius: 4px;
			} 
			
			div.permdenied p {
				text-align: center;
			} 
		</style>
	</head>

	<body>
		<div class='container'>
			<div class='permdenied'>
				<p>
					<strong>Toegang geweigerd</strong> voor [<strong><?php echo $tplHelper->permToString($exception->getPermId()); ?> (<?php echo $exception->getPermId(); ?>)</strong>]::[<strong><?php echo $exception->getObject(); ?></strong>]
				</p>
			</div>
		</div>
	
	<br>
	
	<?php
		/* Als de user nog niet ingelogged is, geven we hem - mits hij dat recht heeft - de mogelijkheid in te loggen */
		if ($tplHelper->allowed(SpotSecurity::spotsec_perform_login, '') && ($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid'))) {
			# loginform verwacht deze twee variables door de renderer, dus die faken we
			$data['performredirect'] = true;
			$loginform = array('username' => '', 'password' => '');
			
			require_once "login.inc.php";
		} # if
	?>
	</body>
</html>
