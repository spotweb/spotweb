<?php 
	require_once "includes/header.inc.php";
	
	$getUrl = $tplHelper->getQueryParams(); 
?>
		<div class="infopane">
			<table class="spotslistusers" summary="Users">
				<thead>
					<tr class="head">
						<th>Username</th> 
						<th>Voornaam</th>
						<th>Achternaam</th>
						<th>Mail</th>
						<th>Laatste bezoek</th>
						<th>Remove</th>
					</tr>
				</thead>
				<tbody id="userlist">
				
<?php
	foreach($userlist as $user) {
?>
					<tr> 
						<td> 
<?php 
	# We kunnen de anonymous user niet editten
	if ($user['userid'] == 1) {
		echo $user['username'];
	} else {
		echo '<a href="' . $tplHelper->makeEditUserUrl($user['userid']) . '">' . $user['username'] . '</a>'; 
	} # else
?> 
						</td>
						<td> <?php echo $user['firstname']; ?> </td>
						<td> <?php echo $user['lastname']; ?> </td>
						<td> <?php echo $user['mail']; ?> </td>
						<td> <?php echo $tplHelper->formatDate($user['lastvisit'], 'userlist'); ?> </td>
<?php 
	# We kunnen de anonymous user niet wissen
	if ($user['userid'] == 1) {
		echo $user['username'];
	} else {
		echo '<a href="' . $tplHelper->makeEditUserUrl($user['userid']) . '">' . $user['username'] . '</a>'; 
	} # else
?> 
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</div>
		
		<div class="clear"></div>

<?php
	require_once "includes/footer.inc.php";
?>