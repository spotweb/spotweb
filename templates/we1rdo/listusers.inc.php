<?php 
	$getUrl = $tplHelper->getQueryParams(); 
?>
		<div class="spots">
			<table class="spots" summary="Spots">
				<thead>
					<tr class="head">
						<th>Username</th> 
						<th>Voornaam</th>
						<th>Achternaam</th>
						<th>Mail</th>
						<th>Laatste bezoek</th>
					</tr>
				</thead>
				<tbody id="userlist">
				
<?php
	foreach($userlist as $user) {
?>
					<tr> 
						<td> <?php echo $user['username']; ?> </td>
						<td> <?php echo $user['firstname']; ?> </td>
						<td> <?php echo $user['lastname']; ?> </td>
						<td> <?php echo $user['mail']; ?> </td>
						<td> <?php echo $tplHelper->formatDate($user['lastvisit'], 'userlist'); ?> </td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
		</div>
		
		<div class="clear"></div>
