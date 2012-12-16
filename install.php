<?php
	error_reporting(2147483647);

	require_once "lib/SpotClassAutoload.php";
	try {
		@include('settings.php');
	}
	catch(Exception $x) {
		// ignore errors
	} # catch
	set_error_handler("ownWarning",E_WARNING);

	/*
	 * We output headers after already sending HTML, make
	 * sure output buffering is turned on.
	 */
	ob_start();
	
	/*
	 * We default to a succeeded install, let it prove
	 * otherwise
	 */
	global $_testInstall_Ok;

	$_testInstall_Ok = true;
	session_start();
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Test your Installation</title>
	<style type='text/css'>
 		* { font-family: Arial, Helvetica, sans-serif; }
 		table {margin-left:auto; margin-right:auto; font-size:12px; color:#fff; width:800px; background-color:#666; border:0px; border-collapse:collapse; border-spacing:0px;}
		table td {background-color:#CCC; color:#000; padding:4px; border:1px #fff solid;}
		table th {background-color:#666; color:#fff; padding:4px; text-align:left; border-bottom:2px #fff solid; font-size:12px; font-weight:bold;} 
		div#error {background-color:#e33b1a; border:1px solid #ab1c00; color:#fff; line-height:18px; padding:0;  text-align:center; vertical-align:top; margin:12px 15px 13px 5px; -moz-border-radius:4px; -webkit-border-radius:4px; border-radius:4px; font-weight:bold;}
		div#success {background-color:#cbffcb; border:1px solid #00ab00; color:#000; line-height:18px; padding:0;  text-align:center; vertical-align:top; margin:12px 15px 13px 5px; -moz-border-radius:4px; -webkit-border-radius:4px; border-radius:4px; font-weight:bold;}

		.button { padding: 4px; padding-left: 10px; padding-right: 10px; margin: 0px; border: 1px solid black; background-color: #fff; color: #666; text-decoration: none; }
		table.tableresult tr { height: 34px; }
	</style>
	<script type='text/javascript'>
		function toggleNntpField() {
			var sel = document.getElementById('nntpselectbox');
			var x = document.getElementById('customnntpfield');
			if (x == null) { return ; } 
			
			if (sel.options[sel.selectedIndex].value == 'custom') { 
				x.style.display = ''; 
			} else {
				x.style.display = 'none'; 
			} // else
		} // toggleNntpField
		
		toggleNntpField();
	</script>
</head>
<body>

<?php
	function performAndPrintTests() {
		global $settings;
		global $_testInstall_Ok;
?>
		<table summary="PHP settings">
			<tr> <th> PHP settings </th> <th> Value </th> <th> Result </th> </tr>
			<tr> <td> PHP version </td> <td> <?php echo phpversion(); ?> </td> <td> <?php showResult((version_compare(PHP_VERSION, '5.3.0') >= 0), true, "", "PHP 5.3 or later is recommended"); ?> </td> </tr>
			<tr> <td> timezone settings </td> <td> <?php echo ini_get("date.timezone"); ?> </td> <td> <?php showResult(ini_get("date.timezone"), true, "", "Please specify date.timezone in your PHP.ini"); ?> </td> </tr>
			<tr> <td> Open base dir </td> <td> <?php echo ini_get("open_basedir"); ?> </td> <td> <?php showResult(!ini_get("open_basedir"), true, "", "Not empty, <strong>might</strong> be a problem"); ?> </td> </tr>
			<tr> <td> Allow furl open </td> <td> <?php echo ini_get("allow_url_fopen"); ?> </td> <td> <?php showResult(ini_get("allow_url_fopen") == 1, true, "", "allow_url_fopen not on -- will cause problems to retrieve external data"); ?> </td> </tr>
			<tr> <td> PHP safe mode </td> <td> <?php echo ini_get("safe_mode"); ?> </td> <td> <?php showResult(!ini_get("safe_mode"), true, "", "Safe mode set -- will cause problems for retrieve.php"); ?> </td> </tr>
			<tr> <td> Memory limit </td> <td> <?php echo ini_get("memory_limit"); ?> </td> <td> <?php showResult(return_bytes(ini_get("memory_limit")) >= (128*1024*1024), true, "", "memory_limit below 128M"); ?> </td> </tr>
		</table>
		<br />

		<table summary="PHP extensions">
			<tr> <th colspan="2"> PHP extension </th> <th> Result </th> </tr>
			<tr> <td colspan="2"> ctype </td> <td> <?php showResult(extension_loaded('ctype'), true); ?> </td> </tr>
			<tr> <td colspan="2"> curl </td> <td> <?php showResult(extension_loaded('curl'), true); ?> </td> </tr>
			<tr> <td colspan="2"> DOM </td> <td> <?php showResult(extension_loaded('dom'), true); ?> </td> </tr>
			<tr> <td colspan="2"> gettext </td> <td> <?php showResult(extension_loaded('gettext'), false); ?> </td> </tr>
			<tr> <td colspan="2"> mbstring </td> <td> <?php showResult(extension_loaded('mbstring'), true); ?> </td> </tr>
			<tr> <td colspan="2"> json </td> <td> <?php showResult(extension_loaded('json'), true); ?> </td> </tr>
			<tr> <td colspan="2"> xml </td> <td> <?php showResult(extension_loaded('xml'), true); ?> </td> </tr>
			<tr> <td colspan="2"> zip </td> <td> <?php showResult(extension_loaded('zip'), false, "", "You need this module to select multiple NZB files"); ?> </td> </tr>
			<tr> <td colspan="2"> zlib </td> <td> <?php showResult(extension_loaded('zlib'), true); ?> </td> </tr>

			<tr> <th colspan="2"> Database support </th> <td> <?php showResult(extension_loaded('mysql') || extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql'), true); ?> </td> </tr>
			<tr> <td colspan="2"> DB::mysql </td> <td> <?php showResult(extension_loaded('mysql'), false); ?> </td> </tr>
			<tr> <td colspan="2"> DB::pdo_mysql </td> <td> <?php showResult(extension_loaded('pdo_mysql'), false); ?> </td> </tr>
			<tr> <td colspan="2"> DB::pgsql </td> <td> <?php showResult(extension_loaded('pdo_pgsql'), false); ?> </td> </tr>

		<?php if (extension_loaded('gd')) $gdInfo = gd_info(); ?>
			<tr> <th colspan="2"> GD </th> <td> <?php showResult(extension_loaded('gd'), true); ?> </td> </tr>
			<tr> <td colspan="2"> FreeType Support </td> <td> <?php showResult($gdInfo['FreeType Support'], true); ?> </td> </tr>
			<tr> <td colspan="2"> GIF Read Support </td> <td> <?php showResult($gdInfo['GIF Read Support'], true); ?> </td> </tr>
			<tr> <td colspan="2"> GIF Create Support </td> <td> <?php showResult($gdInfo['GIF Create Support'], true); ?> </td> </tr>
			<tr> <td colspan="2"> JPEG Support </td> <td> <?php showResult($gdInfo['JPEG Support'] || $gdInfo['JPG Support'], true); ?> </td> </tr> <!-- Previous to PHP 5.3.0, the JPEG Support attribute was named JPG Support. -->
			<tr> <td colspan="2"> PNG Support </td> <td> <?php showResult($gdInfo['PNG Support'], true); ?> </td> </tr>
			<tr> <th colspan="3"> OpenSSL </th> </tr>
		<?php require_once "lib/services/Signing/Services_Signing_Base.php";
			require_once "lib/services/Signing/Services_Signing_Php.php";
			require_once "lib/services/Signing/Services_Signing_Openssl.php";
			$spotSigning = Services_Signing_Base::newServiceSigning();
			$privKey = $spotSigning->createPrivateKey($settings['openssl_cnf_path']);
			
			/* We need either one of those 3 extensions, so set the error flag manually */
			if ( (!extension_loaded('openssl')) && (!extension_loaded('gmp')) && (!extension_loaded('bcmath'))) {
				$_testInstall_Ok = false;
			} # if
			
		?>	<tr> <td rowspan="3"> At least 1 of these must be OK <br />these modules are sorted from fastest to slowest</td> <td> openssl </td> <td> <?php showResult(extension_loaded('openssl'), false); ?> </td> </tr>
			<tr> <td> gmp </td> <td> <?php showResult(extension_loaded('gmp'), false); ?> </td> </tr>
			<tr> <td> bcmath </td> <td> <?php showResult(extension_loaded('bcmath'), false); ?> </td> </tr>
			<tr> <td colspan="2"> Can create private key? </td> <td> <?php showResult(isset($privKey['public']) && !empty($privKey['public']) && !empty($privKey['private']), true); ?> </td> </tr>
		</table>
		<br />

		<table summary="Include files">
			<tr> <th> Include files  </th> <th> Result </th> </tr>
			<tr> <td> Settings file </td> <td> <?php $result=testInclude("settings.php"); echo showResult($result, true, $result); ?> </td> </tr>
			<tr> <td> Own settings file </td> <td> <?php $result=testInclude("ownsettings.php"); echo showResult($result, true, $result, "optional"); ?> </td> </tr>
		</table>
		<br />

		<?php if ($_testInstall_Ok) { ?>
			<table summary="result" class="tableresult">
				<tr> 
						<th colspan="2"> Please continue to setup Spotweb </th> 
						<th> <a href="?page=1" class="button" >Next</a> </th>
				</tr>
			</table>
			<br />
		<?php } else { ?>			
			<table summary="result">
				<tr> <th> Please fix above errors before you can continue to install Spotweb </th> </tr>
			</table>
			<br />
		<?php }  ?>			

		</body>
		</html>
<?php
	} # performAndPrintTests

	function askDbSettings() {
		global $settings;
		global $_testInstall_Ok;

		$form = array('engine' => 'MySQL',
					  'host' => 'localhost',
					  'dbname' => 'spotweb',
					  'user' => 'spotweb',
					  'pass' => 'spotweb',
					  'submit' => '');
		if (isset($_POST['dbform'])) {
			$form = array_merge($form, $_POST['dbform']);
		} # if
						
		/*
		 * Dit the user press submit? If so, try to
		 * connect to the database
		 */
		$databaseCreated = false;
		if ($form['submit'] === 'Verify database') {
			try {
				$db = new SpotDb($form);
				$db->connect();
				$databaseCreated = true;
				
				/*
				 * Store the given database settings in the 
				 * SESSION object, we need it later to generate
				 * a 'ownsettings.php' file
				 */
				$_SESSION['spotsettings']['db'] = $form;			
				
				/*
				 * and call the next stage in the setup
				 */
				Header("Location: " . $_SERVER['SCRIPT_NAME'] . '?page=2');
			} 
			catch(Exception $x) {
	?>
				<div id='error'><?php echo $x->getMessage(); ?>
				<br /><br />
				Please correct the errors in below form and try again
				</div>
	<?php			
			} # exception
		} # if
		
		if (!$databaseCreated) {
	?>
			<form name='dbform' method='POST'>
			<table summary="PHP settings">
				<tr> <th> Database settings </th> <th> </th> </tr>
				<tr> <td colspan='2'> Spotweb needs an available MySQL or PostgreSQL database. The database needs to be created and you need to have an user account and password for this database. </td> </tr>
				<tr> <td> type </td> <td> <select name='dbform[engine]'> <option value='mysql'>mysql</option> <option value='pdo_pgsql'>PostgreSQL</option> </select> </td> </tr>
				<tr> <td> server </td> <td> <input type='text' length='40' name='dbform[host]' value='<?php echo htmlspecialchars($form['host']); ?>'></input> </td> </tr>
				<tr> <td> database </td> <td> <input type='text' length='40' name='dbform[dbname]' value='<?php echo htmlspecialchars($form['dbname']); ?>' ></input></td> </tr>
				<tr> <td> username </td> <td> <input type='text' length='40' name='dbform[user]' value='<?php echo htmlspecialchars($form['user']); ?>'></input> </td> </tr>
				<tr> <td> password </td> <td> <input type='password' length='40' name='dbform[pass]' value='<?php echo htmlspecialchars($form['pass']); ?>'></input> </td> </tr>
				<tr> <td colspan='2'> <input type='submit' name='dbform[submit]' value='Verify database'> </td> </tr>
			</table>
			</form>
			<br />
	<?php
		} # else
	} # askDbSettings

	function askNntpSettings() {
		global $settings;
		global $_testInstall_Ok;

		$serverList = simplexml_load_file('usenetservers.xml');
		$form = array('name' => 'custom',
					  'host' => '',
					  'user' => '',
					  'pass' => '',
					  'port' => 119,
					  'enc' => false,
					  'submit' => '');
		if (isset($_POST['nntpform'])) {
			$form = array_merge($form, $_POST['nntpform']);
		} # if

		/*
		 * Dit the user press submit? If so, try to
		 * connect to the database
		 */
		$nntpVerified = false;
		if ($form['submit'] === 'Verify usenet server') {
			try {
				/*
				 * Convert the selected NNTP name to an actual
				 * server record.
				 */
				if ($form['name'] == 'custom') {
						$form['buggy'] = false;


						$form['hdr'] = $form;
						$form['nzb'] = $form;
						$form['post'] = $form;
				} else {
					foreach($serverList->usenetservers->server as $provider) {
						if (extension_loaded('openssl') && isset($provider->ssl)) {
							$server = $provider->ssl;
						} else {
							$server = $provider->plain;
						} # if

						if ( (string) $provider['name'] == $form['name'] ) {
							# Header usenet server
							$form['hdr']['host'] = (string) $server->header;
							$form['hdr']['user'] = $form['user'];
							$form['hdr']['pass'] = $form['pass'];
							if ( (string) $server->header['ssl'] == 'yes') {
								$form['hdr']['enc'] = 'ssl';
							} else {
								$form['hdr']['enc'] = false;
							} # else
							$form['hdr']['port'] = (int) $server->header['port'];
							$form['hdr']['buggy'] = (boolean) $server['buggy'];

							# NZB usenet server
							$form['nzb']['host'] = (string) $server->nzb;
							$form['nzb']['user'] = $form['user'];
							$form['nzb']['pass'] = $form['pass'];
							if ( (string) $server->nzb['ssl'] == 'yes') {
								$form['nzb']['enc'] = 'ssl';
							} else {
								$form['nzb']['enc'] = false;
							} # else
							$form['nzb']['port'] = (int) $server->nzb['port'];
							$form['nzb']['buggy'] = (boolean) $server['buggy'];

							# Posting usenet server
							$form['post']['host'] = (string) $server->post;
							$form['post']['user'] = $form['user'];
							$form['post']['pass'] = $form['pass'];
							if ( (string) $server->post['ssl'] == 'yes') {
								$form['post']['enc'] = 'ssl';
							} else {
								$form['post']['enc'] = false;
							} # else
							$form['post']['port'] = (int) $server->post['port'];
							$form['post']['buggy'] = (boolean) $server['buggy'];
						} # if
					} # foreach
				} # else 
				
				/* and try to connect to the usenet server */
				$nntp = new SpotNntp($form['hdr']);
				$nntp->validateServer();

				$nntpVerified = true;
				/*
				 * Store the given NNTP settings in the 
				 * SESSION object, we need it later to update
				 * the settings in the database
				 */
				$_SESSION['spotsettings']['nntp'] = $form;
				
				/*
				 * and call the next stage in the setup
				 */
				Header("Location: " . $_SERVER['SCRIPT_NAME'] . '?page=3');
			} 
			catch(Exception $x) {
	?>
				<div id='error'><?php echo $x->getMessage(); ?>
				<br /><br />
				Please correct the errors in below form and try again
				</div>
	<?php			
			} # exception
		} # if
		
		if (!$nntpVerified) {
	?>
			<form name='nntpform' method='POST'>
			<table summary="PHP settings">
				<tr> <th> Usenet server settings </th> <th> </th> </tr>
				<tr> <td colspan='2'> Spotweb needs an usenet server. We have several usenet server profiles defined from which you can choose. If your server is not listed, please choose 'custom', more advanced options can be set from within Spotweb itself. </td> </tr>
				<tr> <td> Usenet server </td> 
				<td> 
					<select id='nntpselectbox' name='nntpform[name]' onchange='toggleNntpField();'> 
	<?php
					foreach($serverList->usenetservers->server as $provider) {
						$server = '';

						/* Make sure the server is supported, eg filter out ssl only servers when openssl is not loaded */
						if (extension_loaded('openssl') && isset($provider->ssl)) {
							$server = $provider->ssl;
						} elseif (isset($provider->plain)) {
							$server = $provider->plain;
						} # if

						if (!empty($server)) {
							echo "<option value='{$provider['name']}'" . (($provider['name'] == $form['name']) ? "selected='selected'" : '') . ">{$provider['name']}</option>";
						} # if
					} # foreach
	?>
						<option value='custom'>Custom</option>
					</select> 
				</td> </tr>
				<tr id='customnntpfield' style='display: none;'> <td> server </td> <td> <input type='text' length='40' name='nntpform[host]' value='<?php echo htmlspecialchars($form['host']); ?>'></input> </td> </tr>
				<tr> <td> username </td> <td> <input type='text' length='40' name='nntpform[user]' value='<?php echo htmlspecialchars($form['user']); ?>'></input> </td> </tr>
				<tr> <td> password </td> <td> <input type='password' length='40' name='nntpform[pass]' value='<?php echo htmlspecialchars($form['pass']); ?>'></input> </td> </tr>
				<tr> <td colspan='2'> <input type='submit' name='nntpform[submit]' value='Verify usenet server'> </td> </tr>
			</table>
			</form>
			<br />
	<?php
		} # else
	} # askNntpSettings
	
	function askSpotwebSettings() {
		global $settings;
		global $_testInstall_Ok;

		$form = array('systemtype' => 'public',
					  'username' => '', 'newpassword1' => '', 'newpassword2' => '', 'firstname' => '',
					  'lastname' => '', 'mail' => '', 'userid' => -1);
		if (isset($_POST['settingsform'])) {
			$form = array_merge($form, $_POST['settingsform']);
		} # if

		/*
		 * Dit the user press submit? If so, try to
		 * connect to the database
		 */
		$userVerified = false;
		if ((isset($form['submit'])) && ($form['submit'] === 'Create system')) {			
			try {
				/*
				 * Store the given user settings in the 
				 * SESSION object, we need it later to update
				 * the settings in the database
				 */
				$_SESSION['spotsettings']['adminuser'] = $form;
			
				/*
				 * Very ugly hack. We create an empty SpotSettings class
				 * so this will satisfy the constructor in the system.
				 * It's ugly, i know.
				 */
				class SpotSettings { } ;

				/*
				 * Override the SpotDb class so we can override userEmailExists()
				 * to not require database access.
				 */
				class DbLessSpotDb extends SpotDb {
					function userEmailExists($s) {
						return (($s == 'john@example.com') || ($s == 'spotwebadmin@example.com'));
					} # userEmailExists
				} #  class DbLessSpotDb
				  

				/*
				 * Create an DbLessSpotDb object to satisfy the user subsystem
				 */
				$db = new DbLessSpotDb($_SESSION['spotsettings']['db']);
				$db->connect();

				/*
				 * And initiate the user system, this allows us to use
				 * validateUserRecord() 
				 */
				$spotUserSystem = new SpotUserSystem($db, new SpotSettings(array()));				
				$errorList = $spotUserSystem->validateUserRecord($form, false);

				if (!empty($errorList)) {
					throw new Exception($errorList[0]);
				} # if
			
				/*
				 * and call the next stage in the setup
				 */
				Header("Location: " . $_SERVER['SCRIPT_NAME'] . '?page=99');
			} 
			catch(Exception $x) {
	?>
				<div id='error'><?php echo $x->getMessage(); ?>
				<br /><br />
				Please correct the errors in below form and try again
				</div>
	<?php			
			} # exception
		} # if
		
		if (!$userVerified) {
	?>
			<form name='settingsform' method='POST'>
			<table summary="PHP settings">
				<tr> <th colspan='2'> Spotweb type </th> </tr>
				<tr> <td colspan='2'> Spotweb has several usages - it can be either run as a personal system, a shared system among friends or a completely public system. <br /> <br /> Please select the most appropriate usage below. </td> </tr>
				<tr> <td nowrap="nowrap"> <input type="radio" name="settingsform[systemtype]" value="single">Single user</td> <td> Single user systems are one-user systems, not shared with friends or family members. Spotweb wil always be logged on using the below defined user and Spotweb will never ask for authentication. </td> </tr>
				<tr> <td nowrap="nowrap"> <input type="radio" name="settingsform[systemtype]" value="shared">Shared</td> <td> Shared systems are Spotweb installations shared among friends or family members. You do have to logon using an useraccount, but the users who do log on are trusted to have no malicious intentions. </tr>
				<tr> <td nowrap="nowrap"> <input type="radio" name="settingsform[systemtype]" value="public" checked="checked">Public</td> <td> Public systems are Spotweb installations fully open to the public. Because the installation is fully open, regular users do not have all the features available in Spotweb to help defend against certain malicious users.</tr>
				<tr> <th colspan='2'> Administrative user </th> </tr>
				<tr> <td colspan='2'> Spotweb will use below user information to create a user for use by Spotweb. The defined password will also be set as the password for the built-in 'admin' account. Please make sure to remember this password. </td> </tr>
				<tr> <td> Username </td> <td> <input type='text' length='40' name='settingsform[username]' value='<?php echo htmlspecialchars($form['username']); ?>'></input> </td> </tr>
				<tr> <td> Password </td> <td> <input type='password' length='40' name='settingsform[newpassword1]' value='<?php echo htmlspecialchars($form['newpassword1']); ?>'></input> </td> </tr>
				<tr> <td> Password (confirm) </td> <td> <input type='password' length='40' name='settingsform[newpassword2]' value='<?php echo htmlspecialchars($form['newpassword2']); ?>'></input> </td> </tr>
				<tr> <td> First name </td> <td> <input type='text' length='40' name='settingsform[firstname]' value='<?php echo htmlspecialchars($form['firstname']); ?>'></input> </td> </tr>
				<tr> <td> Last name </td> <td> <input type='text' length='40' name='settingsform[lastname]' value='<?php echo htmlspecialchars($form['lastname']); ?>'></input> </td> </tr>
				<tr> <td> Email address </td> <td> <input type='text' length='40' name='settingsform[mail]' value='<?php echo htmlspecialchars($form['mail']); ?>'></input> </td> </tr>
				<tr> <td colspan='2'> <input type='submit' name='settingsform[submit]' value='Create system'> </td> </tr>
			</table>
			</form>
			<br />
	<?php
		} # else
	} # askSpotwebSettings

	function createSystem() {
		global $settings;
		global $_testInstall_Ok;

		try {

			/*
			 * The settings system is used to create a lot of output,
			 * we swallow it all
			 */
			ob_start();

			/*
			 * Now create the database ...
			 */
			$settings['db'] = $_SESSION['spotsettings']['db'];
			$spotUpgrader = new SpotUpgrader($settings['db'], $settings);
			$spotUpgrader->database();

			/*
			 * and create all the different settings (only the default) ones
			 */
			$spotUpgrader->settings();

			/*
			 * Create the users
			 */
			$spotUpgrader->users();

			/*
			 * print all the output as HTML comment for debugging
			 */
			$dbCreateOutput = ob_get_contents();
			ob_end_clean();

			/*
			 * Now it is time to do something with
			 * the information the user has given to us
			 */
			$db = new SpotDb($_SESSION['spotsettings']['db']);
			$db->connect();

			/* 
			 * add the database settings to the main settings array for now
			 */
			$settings['db'] = $_SESSION['spotsettings']['db'];;

			/* and create the database settings */
			$spotSettings = SpotSettings::singleton($db, $settings);

			/*
			 * Update the NNTP settings in the databas
			 */
			$spotSettings->set('nntp_nzb', $_SESSION['spotsettings']['nntp']['nzb']);
			$spotSettings->set('nntp_hdr', $_SESSION['spotsettings']['nntp']['hdr']);
			$spotSettings->set('nntp_post', $_SESSION['spotsettings']['nntp']['post']);
			
			/*
			 * Create the given user
			 */
			$spotUserSystem = new SpotUserSystem($db, $spotSettings);
			$spotUser = $_SESSION['spotsettings']['adminuser'];

			/*
			 * Create a private/public key pair for this user
			 */
			$spotSigning = Services_Signing_Base::newServiceSigning();
			$userKey = $spotSigning->createPrivateKey($spotSettings->get('openssl_cnf_path'));
			$spotUser['publickey'] = $userKey['public'];
			$spotUser['privatekey'] = $userKey['private'];

			/*
			 * and actually add the user
			 */
			$userId = $spotUserSystem->addUser($spotUser);

			# Change the administrators' account password to that of this created user
			$adminUser = $spotUserSystem->getUser(SPOTWEB_ADMIN_USERID);
			$adminUser['newpassword1'] = $spotUser['newpassword1'];
			$spotUserSystem->setUserPassword($adminUser);

			# update the settings with our system type and our admin id
			$spotSettings->set('custom_admin_userid', $userId);
			$spotSettings->set('systemtype', $spotUser['systemtype']);

			# Set the system type
			$spotUpgrader->resetSystemType($spotUser['systemtype']);

			/* 
			 * Create the necessary database connection information
			 */
			$dbConnectionString = '';
			switch ($_SESSION['spotsettings']['db']['engine']) {
				case 'mysql' 	: {
					$dbConnectionString .= "\$dbsettings['engine'] = 'mysql';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['host'] = '" . $_SESSION['spotsettings']['db']['host'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['dbname'] = '" . $_SESSION['spotsettings']['db']['dbname'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['user'] = '" . $_SESSION['spotsettings']['db']['user'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['pass'] = '" . $_SESSION['spotsettings']['db']['pass'] . "';" . PHP_EOL;

					break;
				} # mysql

				case 'pdo_pgsql' : {
					$dbConnectionString .= "\$dbsettings['engine'] = 'pdo_pgsql';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['host'] = '" . $_SESSION['spotsettings']['db']['host'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['dbname'] = '" . $_SESSION['spotsettings']['db']['dbname'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['user'] = '" . $_SESSION['spotsettings']['db']['user'] . "';" . PHP_EOL;
					$dbConnectionString .= "\$dbsettings['pass'] = '" . $_SESSION['spotsettings']['db']['pass'] . "';" . PHP_EOL;

					break;
				} # pdo_pgsql 
			} # switch

			# Try to create the dbsettings.inc.php file for the user
			@file_put_contents("dbsettings.inc.php", "<?php" . PHP_EOL . $dbConnectionString);
			$createdDbSettings = file_exists("dbsettings.inc.php");

?>

			<table summary="PHP settings">
				<tr> <th colspan='2'> Installation succesful </th> </tr>
				<tr> <td colspan='2'> Spotweb has been installed succesfuly! </td> </tr>
				<tr> <td colspan='2'> &nbsp; </td> </tr>
<?php if (!$createdDbSettings) { ?>
				<tr> 
						<td> &rarr; </td>
						<td> 
								You need to create a textfile with the database settings in it. Please copy & paste the below
							exactly in a file called <i>dbsettings.inc.php</i>.
							<pre><?php echo "&lt;?php " . PHP_EOL . $dbConnectionString; ?>
							</pre>
				 		</td> 
				</tr>
<?php } ?>
				<tr> 
						<td> &rarr; </td>
						<td> 
							Spotweb retrieves its information from the newsservers, this is called "retrieving" or retrieval of Spots.
							You need to schedule a retrieval job to run <i>retrieve.php</i> on a regular basis. The first time retrieval
							is run this can take up to several hours before completion.
				 		</td> 
				</tr>
			</table>

			<?php echo '<!-- ' . $dbCreateOutput . ' -->'; ?>
<?php		
		}  # try
		catch(Exception $x) {
	?>
			<div id='error'><?php echo $x->getMessage(); ?>
				<?php echo $x->getTraceAsString(); ?>
			<br /><br />
			</div>
	<?php			
		} # exception
	} # createSystem
	


	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	} # return_bytes

	function showResult($b, $isRequired, $okMsg="", $nokMsg="") {
		global $_testInstall_Ok;
		
		if ($b) {
			echo "OK";
			if (!empty($okMsg)) {
				echo ' (' . $okMsg . ')';
			} 
		} else {
			echo "NOT OK";
			if (!empty($nokMsg)) {
				echo ' (' . $nokMsg . ')';
			}
			
			if ($isRequired) {
				$_testInstall_Ok = true;
			} # if
		} # else
		
		return null;
	} # showResult

	function ownWarning($errno, $errstr) {
		$GLOBALS['iserror'] = true;
		#echo $errstr;
	} # ownWarning

	function testInclude($fname) {
		@include_once($fname);
		foreach (get_included_files() as $filename) {
			if (strpos($filename, $fname, strlen($filename) - strlen($fname)) !== false) {
				return dirname($filename);
			}
		}
	} # testInclude

	/*
	 * Only run the wizard when no database settings have been entered yet, to prevent
	 * any information disclosure
	 */
	if ((isset($settings['db'])) && (isset($_GET['page']))) {
		die("Spotweb has already been setup. If you want to run this wizard again, please remove the file 'dbsettings.inc.php'");
	} # if

	/*
	 * determine what page of the wizzard we are on, and display that one
	 */
	$pageNumber = (isset($_GET['page']) ? $_GET['page'] : 0);
	
	switch($pageNumber) {
		case 1			: askDbSettings(); break; 
		case 2			: askNntpSettings(); break; 
		case 3			: askSpotwebSettings(); break;
		case 99			: createSystem(); break;
		
		default			: performAndPrintTests(); break;
	} # switch

	ob_end_flush();
