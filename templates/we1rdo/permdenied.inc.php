<?php
    require_once __DIR__.'/includes/basic-html-header.inc.php';
?>
<?php
        if ($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid')) {
            ?>
			<div class='permdenied'>
				<p>
					<?php echo sprintf(_('Access denied for [%s (%d::%s)]'), $tplHelper->permToString($exception->getPermId()), $exception->getPermId(), $exception->getObject()); ?> </strong>
				</p>
			</div>
		</div>
	
	<br>
<?php
        }
?>
	
	<?php
        /*
         * If this user hasn't been logged in yet, and the user currently has the permission to actually
         * login, we show a login page.
         */
        if ($tplHelper->allowed(SpotSecurity::spotsec_perform_login, '') && ($currentSession['user']['userid'] == $settings->get('nonauthenticated_userid'))) {
            /*
             * The login form actually expects these variables to be provided by SpotPage_login,
             * so we fake them
             */

            /*
             * If the htmlheaderssent variable is set, this means we have already outputted
             * a HTML <head> tag, so we should not sent it again.
             */
            $data['htmlheaderssent'] = true;

            /*
             * If set, we should redirect the user (after successful login), to the
             * page as specified by the 'http_referer' data variable.
             */
            $data['performredirect'] = true;

            /*
             * If set, this means that we expect a full HTML render of the error
             * messages instead of a plain 'json' response. The json response is the
             * moest common case (when the login form is embedded in a dialog),
             * but sometimes we just plain render the HTML form, so we don't want
             * this.
             */
            $data['renderhtml'] = true;

            /*
             * Create a dummy login form so we can render the form
             */
            $result = new Dto_FormResult('notsubmitted');
            $loginform = ['username' => '', 'password' => ''];
            $http_referer = $tplHelper->makeSelfUrl('');

            require __DIR__.'/login.inc.php';
        } else {
            echo '</body></html>';
        } // else
