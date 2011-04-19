<div id="auth">
<?php include "includes/form-messages.inc.php"; ?>

<?php
if ($currentSession['user']['userid'] == SPOTWEB_ANONYMOUS_USERID) {
?>
    <form class="loginform" name="loginform" action="<?php echo $tplHelper->makeLoginAction(); ?>" method="post">
    	<input type="hidden" name="loginform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('loginform'); ?>">
        <fieldset>
            <dl>
                <dt><label for="loginform[username]">Username</label></dt>
                <dd><input type="text" name="loginform[username]" value="<?php echo htmlspecialchars($loginform['username']); ?>"></dd>
    
                <dt><label for="loginform[firstname]">Password</label></dt>
                <dd><input type="password" name="loginform[password]" value=""></dd>
    
                <dd><input class="greyButton" type="submit" name="loginform[submit]" value="Login"></dd>
            </dl>
        </fieldset>
    </form>
<?php
	}
?>
</div>