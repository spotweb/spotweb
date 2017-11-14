<?php
    if ($result->isSubmitted()) {
        if ($result->isSuccess()) {
            $tplHelper->redirect($http_referer);

            return ;
        } else {
            showResults($result, array('renderhtml' => 1));
        } # else
    } # if

    require __DIR__ . '/includes/header.inc.php';
    require __DIR__ . '/includes/form-messages.inc.php';
    
    $nntp_nzb = $this->_settings->get('nntp_nzb');
    $nntp_hdr = $this->_settings->get('nntp_hdr');
    $nntp_post = $this->_settings->get('nntp_post');

    $tmpArDiff = array_diff_assoc($nntp_hdr, $nntp_nzb);
    if ((empty($tmpArDiff)) || (empty($nntp_hdr['host']))) {
        $nntp_hdr['isadummy'] = true;
    } # if

    $tmpArDiff = array_diff_assoc($nntp_post, $nntp_nzb);
    if ((empty($tmpArDiff)) || (empty($nntp_post['host']))) {
        $nntp_post['isadummy'] = true;
    } # if

    $retrieve_newer_than = $this->_settings->get('retrieve_newer_than');
    if ($retrieve_newer_than < 1254373200) {
        $retrieve_newer_than = 1254373200; // 2009-11-01
    } # if
    echo "<script type='text/javascript'>var retrieveNewerThanDate = '" . strftime('%d-%m-%Y', $retrieve_newer_than) . "';</script>";
?>
</div>
	<div id='toolbar'>
		<div class="closeeditsettings"><p><a class='toggle' href='<?php echo $tplHelper->makeBaseUrl('path');?>'><?php echo _('Back to mainview'); ?></a></p></div>
	</div>
<form class="editsettingsform" name="editsettingsform" action="<?php echo $tplHelper->makeEditSettingsAction(); ?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="editsettingsform[xsrfid]" value="<?php echo $tplHelper->generateXsrfCookie('editsettingsform'); ?>">
	<input type="hidden" name="editsettingsform[http_referer]" value="<?php echo $http_referer; ?>">
	
	<div id="editsettingstab" class="ui-tabs">
		<ul>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_spotweb_updates, '')) { ?>
			<li><a href="?page=versioncheck" title="<?php echo _('Spotweb updates'); ?>"><span><?php echo _('Spotweb updates');?></span></a></li>
<?php }
if ($tplHelper->allowed(SpotSecurity::spotsec_edit_settings, '')) { ?>
			<li><a href="#editsettingstab-1"><span><?php echo _('General'); ?></span></a></li>
			<li><a href="#editsettingstab-2"><span><?php echo _('Newsservers'); ?></span></a></li>
			<li><a href="#editsettingstab-3"><span><?php echo _('Retrieve'); ?></span></a></li>
			<li><a href="#editsettingstab-4"><span><?php echo _('Performance'); ?></span></a></li>
			<li><a href="#editsettingstab-5"><span><?php echo _("Custom CSS"); ?></span></a></li>
<?php } ?>
		</ul>
			
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_settings, '')) { ?>		
		<div id="editsettingstab-1" class="ui-tabs-hide">
			<fieldset>
				<dl>
					<dt><label for="editsettingsform[deny_robots]"><?php echo _('Try to prevent robots from indexing this installation'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[deny_robots]" <?php if ($this->_settings->get('deny_robots')) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="editsettingsform[systemfrommail]"><?php echo _('Sender email address'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[systemfrommail]" value="<?php echo htmlspecialchars($this->_settings->get('systemfrommail'), ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[sendwelcomemail]"><?php echo _('Always send welcome e-mail to new users'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[sendwelcomemail]" <?php if ($this->_settings->get('sendwelcomemail')) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="editsettingsform[cookie_expires]"><?php echo _('Cookie expires after (in days)'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[cookie_expires]" value="<?php echo htmlspecialchars($this->_settings->get('cookie_expires'), ENT_QUOTES); ?>"></dd>

                    <!-- Add some explanation about the MS translator API -->
                    <p>
                        <?php echo _('Spotweb can use the Microsoft Translator API to translate comments and Spot description to the users native language. This requires either a subscription key from Microsoft Cognitive Services in the Azure Portal. You can find instructions at <a href="http://docs.microsofttranslator.com/text-translate.html">http://docs.microsofttranslator.com/text-translate.html</a>. Please enter the subscription key in the field below.'); ?>
                    </p>
                    <dt><label for="editsettingsform[ms_translator_subscriptionkey]"><?php echo _('Microsoft Cognitive Services - Subscription Key'); ?></label></dt>
                    <dd><input type="text" name="editsettingsform[ms_translator_subscriptionkey]" value="<?php echo htmlspecialchars($this->_settings->get('ms_translator_subscriptionkey'), ENT_QUOTES); ?>"></dd>

				</dl>

			</fieldset>
		</div>

		<div id="editsettingstab-2" class="ui-tabs-hide newsservers">
			<fieldset>
				<dt><label for="use_nntp_hdr"><?php echo _('Default newsserver'); ?></label></dt>
			</fieldset>
			<fieldset class="serverSettings">
				<dl>
					<dt><label for="editsettingsform[nntp_nzb][host]"><?php echo _('Hostname'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_nzb][host]" value="<?php echo htmlspecialchars($nntp_nzb['host'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_nzb][user]"><?php echo _('Username'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_nzb][user]" value="<?php echo htmlspecialchars($nntp_nzb['user'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_nzb][pass]"><?php echo _('Password'); ?></label></dt>
					<dd><input type="password" name="editsettingsform[nntp_nzb][pass]" value="<?php echo htmlspecialchars($nntp_nzb['pass'], ENT_QUOTES); ?>"></dd>

					<dt><label for="use_encryption_nzb"><?php echo _('Encryption'); ?></label></dt>
					<dd><input type="checkbox" class="enabler" name="editsettingsform[nntp_nzb][enc][switch]" id="use_encryption_nzb" <?php if ($nntp_nzb['enc']) { echo 'checked="checked"'; } ?>></dd>
					<fieldset id="content_use_encryption_nzb">
						<select name="editsettingsform[nntp_nzb][enc][select]">
							<option <?php if ($nntp_nzb['enc'] == 'ssl') { echo 'selected="selected"'; } ?> value="ssl">SSL</option>
							<option <?php if ($nntp_nzb['enc'] == 'tls') { echo 'selected="selected"'; } ?> value="tls">TLS</option>
						</select>					
					</fieldset>

					<dt><label for="editsettingsform[nntp_nzb][port]"><?php echo _('Port'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_nzb][port]" value="<?php echo htmlspecialchars($nntp_nzb['port'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_nzb][buggy]"><?php echo _('Buggy (Some newsservers lose messages once in a while)'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[nntp_nzb][buggy]" <?php if ($nntp_nzb['buggy']) { echo 'checked="checked"'; } ?>></dd>
				</dl>
			</fieldset>

			<fieldset>
				<dt><label for="use_nntp_hdr"><?php echo _('Use different server for headers?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="editsettingsform[nntp_hdr][use]" id="use_nntp_hdr" <?php if (!isset($nntp_hdr['isadummy'])) { echo 'checked="checked"'; } ?>></dd>
			</fieldset>
			<fieldset id="content_use_nntp_hdr" class="serverSettings">
				<dl>
					<dt><label for="editsettingsform[nntp_hdr][host]"><?php echo _('Hostname'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_hdr][host]" value="<?php echo htmlspecialchars($nntp_hdr['host'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_hdr][user]"><?php echo _('Username'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_hdr][user]" value="<?php echo htmlspecialchars($nntp_hdr['user'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_hdr][pass]"><?php echo _('Password'); ?></label></dt>
					<dd><input type="password" name="editsettingsform[nntp_hdr][pass]" value="<?php echo htmlspecialchars($nntp_hdr['pass'], ENT_QUOTES); ?>"></dd>

					<dt><label for="use_encryption_hdr"><?php echo _('Encryption'); ?></label></dt>
					<dd><input type="checkbox" class="enabler" name="editsettingsform[nntp_hdr][enc][switch]" id="use_encryption_hdr" <?php if ($nntp_hdr['enc']) { echo 'checked="checked"'; } ?>></dd>
					<fieldset id="content_use_encryption_hdr">
						<select name="editsettingsform[nntp_hdr][enc][select]">
							<option <?php if ($nntp_hdr['enc'] == 'ssl') { echo 'selected="selected"'; } ?> value="ssl">SSL</option>
							<option <?php if ($nntp_hdr['enc'] == 'tls') { echo 'selected="selected"'; } ?> value="tls">TLS</option>
						</select>					
					</fieldset>

					<dt><label for="editsettingsform[nntp_hdr][port]"><?php echo _('Port'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_hdr][port]" value="<?php echo htmlspecialchars($nntp_hdr['port'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_hdr][buggy]"><?php echo _('Buggy (Some newsservers lose messages once in a while)'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[nntp_hdr][buggy]" <?php if ($nntp_hdr['buggy']) { echo 'checked="checked"'; } ?>></dd>
				</dl>
			</fieldset>

			<fieldset>
				<dt><label for="use_nntp_post"><?php echo _('Use different server for posting?'); ?></label></dt>
				<dd><input type="checkbox" class="enabler" name="editsettingsform[nntp_post][use]" id="use_nntp_post" <?php if (!isset($nntp_post['isadummy'])) { echo 'checked="checked"'; } ?>></dd>
			</fieldset>
			<fieldset id="content_use_nntp_post" class="serverSettings">
				<dl>
					<dt><label for="editsettingsform[nntp_post][host]"><?php echo _('Hostname'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_post][host]" value="<?php echo htmlspecialchars($nntp_post['host'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_post][user]"><?php echo _('Username'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_post][user]" value="<?php echo htmlspecialchars($nntp_post['user'], ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[nntp_post][pass]"><?php echo _('Password'); ?></label></dt>
					<dd><input type="password" name="editsettingsform[nntp_post][pass]" value="<?php echo htmlspecialchars($nntp_post['pass'], ENT_QUOTES); ?>"></dd>

					<dt><label for="use_encryption_post"><?php echo _('Encryption'); ?></label></dt>
					<dd><input type="checkbox" class="enabler" name="editsettingsform[nntp_post][enc][switch]" id="use_encryption_post" <?php if ($nntp_post['enc']) { echo 'checked="checked"'; } ?>></dd>
					<fieldset id="content_use_encryption_post">
						<select name="editsettingsform[nntp_post][enc][select]">
							<option <?php if ($nntp_post['enc'] == 'ssl') { echo 'selected="selected"'; } ?> value="ssl">SSL</option>
							<option <?php if ($nntp_post['enc'] == 'tls') { echo 'selected="selected"'; } ?> value="tls">TLS</option>
						</select>					
					</fieldset>

					<dt><label for="editsettingsform[nntp_post][port]"><?php echo _('Port'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[nntp_post][port]" value="<?php echo htmlspecialchars($nntp_post['port'], ENT_QUOTES); ?>"></dd>

					<input type="hidden" name="editsettingsform[nntp_post][buggy]" value="">
				</dl>
			</fieldset>
			</div>

		<div id="editsettingstab-3" class="ui-tabs-hide">
			<fieldset>
				<dl>
					<dt><label for="editsettingsform[retention]"><?php echo _('Retention on spots (in days). Older spots will be erased. Select 0 to keep all spots.'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[retention]" value="<?php echo htmlspecialchars($this->_settings->get('retention'), ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[retentiontype]"><?php echo _('Retention is for either everything or only the cached data'); ?></label></dt>
						<select name="editsettingsform[retentiontype]">
							<option <?php if ($this->_settings->get('retentiontype') == 'everything') { echo 'selected="selected"'; } ?> value="everything"><?php echo _('Remove everything'); ?></option>
							<option <?php if ($this->_settings->get('retentiontype') == 'fullonly') { echo 'selected="selected"'; } ?> value="fullonly"><?php echo _('Only extra data but keep spots'); ?></option>
						</select>					

					<dt><label for="editsettingsform[retrieve_newer_than]"><?php echo _('Retrieve spots after... Select November 1, 2009 to fetch all spots'); ?><br /><?php echo _('To skip all FTD spots select November 24, 2010'); ?></label></dt>
					<dd><div id="datepicker"></div><input type="hidden" id="retrieve_newer_than" name="editsettingsform[retrieve_newer_than]"></dd>

					<dt><label for="editsettingsform[retrieve_full]"><?php echo _('Retrieve full spots'); ?></label></dt>
					<dd><input type="checkbox" class="enabler" name="editsettingsform[retrieve_full]" id="use_retrieve_full" <?php if ($this->_settings->get('retrieve_full')) { echo 'checked="checked"'; } ?>></dd>
					<fieldset id="content_use_retrieve_full">
						<dt><label for="editsettingsform[prefetch_image]"><?php echo _('Prefetch images'); ?></label></dt>
						<dd><input type="checkbox" name="editsettingsform[prefetch_image]" <?php if ($this->_settings->get('prefetch_image')) { echo 'checked="checked"'; } ?>></dd>

						<dt><label for="editsettingsform[prefetch_nzb]"><?php echo _('Prefetch NZB files'); ?></label></dt>
						<dd><input type="checkbox" name="editsettingsform[prefetch_nzb]" <?php if ($this->_settings->get('prefetch_nzb')) { echo 'checked="checked"'; } ?>></dd>
					</fieldset>

					<dt><label for="editsettingsform[retrieve_comments]"><?php echo _('Retrieve comments'); ?></label></dt>
					<dd><input type="checkbox" class="enabler" name="editsettingsform[retrieve_comments]" id="use_retrieve_comments" <?php if ($this->_settings->get('retrieve_comments')) { echo 'checked="checked"'; } ?>></dd>
					<fieldset id="content_use_retrieve_comments">
						<dt><label for="editsettingsform[retrieve_full_comments]"><?php echo _('Retrieve full comments'); ?></label></dt>
						<dd><input type="checkbox" name="editsettingsform[retrieve_full_comments]" <?php if ($this->_settings->get('retrieve_full_comments')) { echo 'checked="checked"'; } ?>></dd>
					</fieldset>

					<dt><label for="editsettingsform[retrieve_reports]"><?php echo _('Retrieve reports'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[retrieve_reports]" <?php if ($this->_settings->get('retrieve_reports')) { echo 'checked="checked"'; } ?>></dd>
				</dl>
			</fieldset>
		</div>

		<div id="editsettingstab-4" class="ui-tabs-hide">
			<fieldset>
				<dl>
					<dt><label for="editsettingsform[enable_timing]"><?php echo _('Enable timing'); ?><br /><?php echo _('Use this only to identify speed problems within Spotweb.'); ?> <?php echo _('Not suitable for public installations'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[enable_timing]" <?php if ($this->_settings->get('enable_timing')) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="editsettingsform[enable_stacktrace]"><?php echo _('Enable stacktrace'); ?><br /><?php echo _('Stacktraces make identifying problems easy, but may contain sensitive information.'); ?> <?php echo _('Not suitable for public installations'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[enable_stacktrace]" <?php if ($this->_settings->get('enable_stacktrace')) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="editsettingsform[retrieve_increment]"><?php echo _('Retrieve increment'); ?><br /><?php echo _('Lower this if you get timeouts during retrieve'); ?></label></dt>
					<dd><input type="text" name="editsettingsform[retrieve_increment]" value="<?php echo htmlspecialchars($this->_settings->get('retrieve_increment'), ENT_QUOTES); ?>"></dd>

					<dt><label for="editsettingsform[spot_moderation]"><?php echo _('Handling of moderation messages'); ?></label></dt>
					<dd><select name="editsettingsform[spot_moderation]">
						<option <?php if ($this->_settings->get('spot_moderation') == 'disable') { echo 'selected="selected"'; } ?> value="disable"><?php echo _('Do nothing'); ?></option>
						<option <?php if ($this->_settings->get('spot_moderation') == 'act') { echo 'selected="selected"'; } ?> value="act"><?php echo _('Delete moderated spots'); ?></option>
						<option <?php if ($this->_settings->get('spot_moderation') == 'markspot') { echo 'selected="selected"'; } ?> value="markspot"><?php echo _('Mark moderated spots as moderated'); ?></option>
					</select></dd>
					
					<dt><label for="editsettingsform[imageover_subcats]"><?php echo _('Enable imagepreview in spot overview'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[imageover_subcats]" <?php if ($this->_settings->get('imageover_subcats')) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="editsettingsform[prepare_statistics]"><?php echo _('Prepare statistics during retrieve'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[prepare_statistics]" <?php if ($this->_settings->get('prepare_statistics')) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="editsettingsform[external_blacklist]"><?php echo _('Fetch the external blacklist during retrieve'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[external_blacklist]" <?php if ($this->_settings->get('external_blacklist')) { echo 'checked="checked"'; } ?>></dd>

					<dt><label for="editsettingsform[external_whitelist]"><?php echo _('Fetch the external whitelist during retrieve'); ?></label></dt>
					<dd><input type="checkbox" name="editsettingsform[external_whitelist]" <?php if ($this->_settings->get('external_whitelist')) { echo 'checked="checked"'; } ?>></dd>
				</dl>
			</fieldset>
		</div>

<!-- Custom Stylesheet -->
		<div id="editsettingstab-5" class="ui-tabs-hide">
			<fieldset>
				<dt>
					<label for="editsettingsform[customcss]"><?php echo _('Use custom CSS?'); ?></label>
				</dt>
				<dd>
					<textarea name="editsettingsform[customcss]" rows="15" cols="120"><?php echo htmlspecialchars($this->_settings->get('customcss')); ?></textarea>
				</dd>
			</fieldset>
		</div>
<!-- Einde Custom Stylesheet -->

<?php } ?>

		<div class="editSettingsButtons">
			<input class="greyButton" type="submit" name="editsettingsform[submitedit]" value="<?php echo _('Change'); ?>">
			<input class="greyButton" type="submit" name="editsettingsform[submitcancel]" value="<?php echo _('Cancel'); ?>">
			<div class="clear"></div>
		</div>
	</div>
</form>
<?php
    $toRunJsCode = 'initializeSettingsPage();';
	require_once __DIR__ . '/includes/footer.inc.php';
