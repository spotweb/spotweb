<?php echo $settings->get('spotweburl'); ?> registration.

Hello <?php echo $user['firstname'].' '.$user['lastname'] ?>,

An account been created for you on: <?php echo $settings->get('spotweburl'); ?>.

You may login using the following credentials:

Username:		<?php echo $user['username']; ?> 
Password:		<?php echo $user['newpassword1']; ?> 

Kind regards,
<?php echo $adminUser['firstname'].' '.$adminUser['lastname']; ?>.