<?php if ($currentSession['user']['userid'] != $settings->get('nonauthenticated_userid')): ?>
&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;
<?php echo $currentSession['user']['username']; ?>&nbsp;
(<a href='#' id="anchorLoginControl">uitloggen</a>)
<?php endif; ?>
