<div class='versioncheck'>
<?php 
	if (!$uptodate) {
?>
		<div class='warning'><?php echo _('An updated version of Spotweb is available'); ?></div>	
<?php 
	}
?>

	<ul>
<?php
	$itemCount = 0;
	foreach($items as $item) {
		$itemCount++;
?>
					<li class="<?php if ($itemCount % 2 == 1) echo " even"; ?>"><strong><?php echo sprintf(_('Posted by %s'), '<span class="user">' . $item['author'] . '</span>'); ?>
					<?php if ($item['is_newer_than_installed']) { echo '(!)'; } ?>
					@ <?php echo $tplHelper->formatDate((int) strtotime($item['pubDate']), 'comment'); ?> </strong> <br />
					<?php echo $item['description']; ?>
					</li>
					
<?php
	}
?>
	</ul>
</div>
