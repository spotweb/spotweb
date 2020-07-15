<?php if ($currentSession['user']['userid'] != $settings->get('nonauthenticated_userid')) { ?>&nbsp;-&nbsp;<?php echo $currentSession['user']['username']; ?>&nbsp;<?php } ?>
