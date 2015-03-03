Spotweb registration

Hello <?php echo $user['firstname'] . ' ' . $user['lastname'] ?>,

An account hads been created for you at <?php echo $settings->get('spotweburl'); ?>.

You can login using the following credentials:

Username:		<?php echo $user['username']; ?> 
Password:		<?php echo $user['newpassword1']; ?> 

Kind regards,
<?php echo $adminUser['firstname'] . ' ' . $adminUser['lastname']; ?>.
