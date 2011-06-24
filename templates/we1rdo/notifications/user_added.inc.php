Spotweb registratie

Hallo <?php echo $user['firstname'] . ' ' . $user['lastname'] ?>,

Er is zojuist een account voor je aangemaakt op <?php echo $settings->get('spotweburl'); ?>.

Je kunt inloggen met de volgende gegevens:

Gebruikersnaam:		<?php echo $user['username']; ?>
Wachtwoord:			<?php echo $user['newpassword1']; ?>

Met vriendelijke groet,
<?php echo $adminUser['firstname'] . ' ' . $adminUser['lastname']; ?>.
