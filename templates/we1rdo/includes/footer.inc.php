	</div>

    <?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_statics, '')) { ?>
        <script type='text/javascript'>
            // Define some global variables showing or hiding specific parts of the UI
            // based on users' security rights
            var spotweb_security_allow_spotdetail = <?php echo (int) $tplHelper->allowed(SpotSecurity::spotsec_view_spotdetail, ''); ?>;
            var spotweb_security_allow_view_spotimage = <?php echo (int) $tplHelper->allowed(SpotSecurity::spotsec_view_spotimage, ''); ?>;
            var spotweb_security_allow_view_comments = <?php echo (int) $tplHelper->allowed(SpotSecurity::spotsec_view_comments, ''); ?>;
            var spotweb_currentfilter_params = "<?php echo str_replace('&amp;', '&', $tplHelper->convertFilterToQueryParams()); ?>";
            var spotweb_retrieve_commentsperpage = <?php if ($settings->get('retrieve_full_comments')) { echo 250; } else { echo 10; } ?>;
            var spotweb_nzbhandler_type = '<?php echo $tplHelper->getNzbHandlerType(); ?>';
        </script>
        <script src='?page=statics&amp;type=js&amp;lang=<?php echo urlencode($currentSession['user']['prefs']['user_language']); ?>&amp;mod=<?php echo $tplHelper->getStaticModTime('js'); ?>' type='text/javascript'></script>

        <script type='text/javascript'>
            <?php echo "initSpotwebJs('";  echo _('Between ') ; echo "','"; echo _(' and ') ; echo "')" ?>;
            //initSpotwebJs();
            <?php if (!empty($toRunJsCode)) {
                echo $toRunJsCode;
            } # if
            ?>
        </script>
    <?php } ?>

	</body>
</html>
