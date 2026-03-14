<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
		<title>SpotWeb - <?php echo $pagetitle?></title>
		<meta name="generator" content="SpotWeb v<?php echo SPOTWEB_VERSION; ?>">
<?php if ($settings->get('deny_robots')) {
    echo "\t\t<meta name=\"robots\" content=\"noindex, nofollow\">\r\n";
} ?>
		<base href='<?php echo $tplHelper->makeBaseUrl('full'); ?>'>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_rssfeed, '')) { ?>
		<link rel='alternate' type='application/rss+xml' href='<?php echo $tplHelper->makeRssUrl(); ?>'>
<?php } ?>

		<script type="text/javascript">
		// Guards for legacy globals some plugins expect
		try { if (typeof window.templates === 'undefined') { window.templates = {}; } } catch (e) { }
		try { if (typeof window.modern === 'undefined') { window.modern = {}; } } catch (e) { }
		(function(){
		  try {
		    var pref = localStorage.getItem('spotweb_theme') || 'auto';
		    var mode = pref === 'auto' ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : pref;
		    document.documentElement.setAttribute('data-theme', mode);
		  } catch(e) {}
		})();
		// Fallback for back button in full-page spot view
		document.addEventListener('click', function(ev){
		  var a = ev.target && ev.target.closest ? ev.target.closest('a.closeDetails') : null;
		  if (!a) return;
		  ev.preventDefault();
		  try {
		    if (window.history.length > 1) { window.history.back(); }
		    else { window.location.href = '<?php echo $tplHelper->makeBaseUrl('path'); ?>'; }
		  } catch(e) { window.location.href = '<?php echo $tplHelper->makeBaseUrl('path'); ?>'; }
		}, true);
		</script>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_statics, '')) { ?>
		<link rel='stylesheet' type='text/css' href='?page=statics&amp;type=css&amp;mod=<?php echo $tplHelper->getStaticModTime('css'); ?>'>
		<link rel='shortcut icon' href='?page=statics&amp;type=ico&amp;mod=<?php echo $tplHelper->getStaticModTime('ico'); ?>'>
<?php } ?>
<?php foreach ($tplHelper->getThemeHeaderCssFiles() as $cssFile) { ?>
		<link rel='stylesheet' type='text/css' href='<?php echo htmlspecialchars($cssFile, ENT_QUOTES, 'UTF-8'); ?>'>
<?php } ?>
		<style type="text/css" media="screen,handheld,projection">
			<?php echo $settings->get('customcss'); ?>
		</style>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) { ?>
		<style type="text/css" media="screen,handheld,projection">
			<?php echo $tplHelper->getUserCustomCss(); ?>
		</style>
<?php } ?>
		<!-- Modern JS (loaded separately to avoid statics concatenation issues) -->
<?php foreach ($tplHelper->getThemeHeaderJsFiles() as $jsFile) { ?>
		<script src="<?php echo htmlspecialchars($jsFile, ENT_QUOTES, 'UTF-8'); ?>" type="text/javascript" defer></script>
<?php } ?>
<?php
    $pageName = strtolower((string) ($_GET['page'] ?? 'index'));
    $tplName = strtolower((string) ($_GET['tplname'] ?? ''));
    $bodyClasses = [
        'modern-theme',
        'modern-page-'.preg_replace('/[^a-z0-9_-]+/', '-', $pageName),
    ];
    $configPages = ['editsettings', 'edituserprefs', 'usermanagement'];
    $configTemplates = ['usermanagement', 'listusers', 'listgroups', 'listfilters', 'editfilter'];
    if (in_array($pageName, $configPages, true) || in_array($tplName, $configTemplates, true)) {
        $bodyClasses[] = 'modern-config';
    }
?>
	</head>
	<body class="<?php echo htmlspecialchars(implode(' ', $bodyClasses), ENT_QUOTES, 'UTF-8'); ?>">
		<div id='editdialogdiv'></div>
		<div id="overlay"></div>
		<div class="container" id="container">
