<p>
    <?php echo _('Please specify the reason why this Spot should be marked as spam'); ?>
</p>
<form class="postreportform" name="postreportform" action="<?php echo $tplHelper->makeReportAction(); ?>" method="post">
    <div>
        <input type="hidden" name="postreportform[submitpost]" value="Post">
        <input type="hidden" name="postreportform[xsrfid]"
               value="<?php echo $tplHelper->generateXsrfCookie('postreportform'); ?>">
        <input type="hidden" name="postreportform[inreplyto]"
               value="<?php echo htmlspecialchars($data['messageid']); ?>">
        <input type="hidden" name="postreportform[newmessageid]" value="">
        <input type="hidden" name="postreportform[randomstr]"
               value="<?php echo $tplHelper->getCleanRandomString(4); ?>">
        <select name="postreportform[body]">
            <option
                value="This user is spamming for a website"><?php echo _('This user is spamming for a website'); ?></option>
            <option value="This upload is broken"><?php echo _('This upload is broken'); ?></option>
            <option value="Wrong categories (accidentally)"><?php echo _('Wrong categories (accidentally)'); ?></option>
            <option
                value="Wrong categories (on purpose, eg porn posted as a childrens book)"><?php echo _('Wrong categories (on purpose, eg porn posted as a childrens book)'); ?></option>
            <option value="Malware"><?php echo _('Malware'); ?></option>
            <option value="Troll"><?php echo _('Troll'); ?></option>
        </select>

        <p>
            <input class="smallGreyButton" id="postReportFormSubmitButton" type="submit" name="postreportform[submit]"
                   value="<?php echo _('Report'); ?>">
        </p>
    </div>
</form>
