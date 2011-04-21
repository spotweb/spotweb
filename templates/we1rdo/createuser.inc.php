<<<<<<< HEAD
<?php include "includes/form-messages.inc.php"; ?>
   
<form class="createuserform" name="createuserform" action="<?php echo $tplHelper->makeCreateUserAction(); ?>" method="post">
=======
<?php 

if ((!empty($createresult)) || (!empty($formmessages))) {
	include 'includes/form-xmlresult.inc.php';
	
	echo formResult2Xml($createresult, $formmessages);
} # if

if (empty($createresult)) {
	include "includes/form-messages.inc.php";

?>
<form name="createuserform" action="<?php echo $tplHelper->makeCreateUserAction(); ?>" method="post">
>>>>>>> upstream/master
<input type="hidden" name="createuserform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('createuserform'); ?>">
    <fieldset>
        <dl>
            <dt><label for="createuserform[username]">Username</label></dt>
            <dd><input type="text" name="createuserform[username]" value="<?php echo htmlspecialchars($createuserform['username']); ?>"></dd>

            <dt><label for="createuserform[firstname]">First name</label></dt>
            <dd><input type="text" name="createuserform[firstname]" value="<?php echo htmlspecialchars($createuserform['firstname']); ?>"></dd>

            <dt><label for="createuserform[lastname]">Last name</label></dt>
            <dd><input type="text" name="createuserform[lastname]"  value="<?php echo htmlspecialchars($createuserform['lastname']); ?>"></dd>
            
            <dt><label for="createuserform[mail]">Mailaddress</label></dt>
            <dd><input type="text" name="createuserform[mail]"  value="<?php echo htmlspecialchars($createuserform['mail']); ?>"></dd>

<<<<<<< HEAD
            <dd><input class="greyButton" type="submit" name="createuserform[submit]" value="Toevoegen"></dd>
        </dl>
    </fieldset>
</form>
=======
			<dd><input type="submit" name="createuserform[submit]" value="Add"></dd>
		</dl>
	</fieldset>
</form>
<?php
	}
?>
>>>>>>> upstream/master
