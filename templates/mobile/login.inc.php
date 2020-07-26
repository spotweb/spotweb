<?php
    require __DIR__.'/includes/form-messages.inc.php';

    /*
     * Do we need to redirect on success? If so, perform this
     */
    if (isset($data['performredirect']) && ($result->isSuccess())) {
        $tplHelper->redirect($loginform['http_referer']);

        return;
    } // if

    $didSubmitForm = showResults($result, $data);

    /*
     * If the form submission was successful, all output
     * we wanted has already been sent (either the JSON
     * or the redirect).
     *
     * If not, we try to re-render the form again
     */
    if (($didSubmitForm) && (!isset($data['renderhtml']))) {
        return;
    } // if

    /*
     * If no HTML headers are sent just yet, make sure
     * we send them to the client
     */
    if (!isset($data['htmlheaderssent'])) {
        require_once __DIR__.'/includes/basic-html-header.inc.php';

        $data['renderhtml'] = true;
    } // if
?>

<div class='login'>
    <form class="loginform" name="loginform" action="<?php echo $tplHelper->getPageUrl('login'); ?>" method="post">
        
        <input type="hidden" name="loginform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('loginform'); ?>">
	    <input type="hidden" name="loginform[http_referer]" value="<?php echo $http_referer; ?>">
	    <?php if (isset($data['performredirect'])) {?>
		    <input type="hidden" name="data[performredirect]" value="<?php echo $data['performredirect']; ?>">
	    <?php } ?>
	    <?php if (isset($data['renderhtml'])) {?>
		    <input type="hidden" name="data[renderhtml]" value="<?php echo $data['renderhtml']; ?>">
	    <?php } ?>
         
        <h3 id="titel">Please login:</h3>
		<label for="loginform[username]"><?php echo _('Username:'); ?></label><br>
		<input type="text" name="loginform[username]" value="<?php echo htmlspecialchars($loginform['username']); ?>"><br>
        <label for="loginform[password]"><?php echo _('Password:'); ?></label><br>
		<input type="password" name="loginform[password]" value=""><br>
		<br>
        <input id="dologin" class="greyButton" type="submit" name="loginform[submitlogin]" value="<?php echo _('Login'); ?>"><br>
   </form>
</div>
<?php

    if (isset($data['renderhtml'])) {
        echo '</div></body></html>';
    } // if
