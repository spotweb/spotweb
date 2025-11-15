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
		<link rel='stylesheet' type='text/css' href='templates/modern/css/posting.css'>
		<link rel='stylesheet' type='text/css' href='templates/modern/css/config.css'>
		<style type="text/css" media="screen,handheld,projection">
			<?php echo $settings->get('customcss'); ?>
		</style>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_allow_custom_stylesheet, '')) { ?>
		<style type="text/css" media="screen,handheld,projection">
			<?php echo $tplHelper->getUserCustomCss(); ?>
		</style>
<?php } ?>
		<!-- Modern JS (loaded separately to avoid statics concatenation issues) -->
		<script src="templates/modern/js/theme-toggle.js" type="text/javascript" defer></script>
		<script src="templates/modern/js/back-fix.js" type="text/javascript" defer></script>
		<script src="templates/modern/js/sticky-offset.js" type="text/javascript" defer></script>
		<script src="templates/modern/js/infinite.js" type="text/javascript" defer></script>
		<script src="templates/modern/js/table-enhance.js" type="text/javascript" defer></script>
		<script src="templates/modern/js/filter-overlay.js" type="text/javascript" defer></script>
	</head>
	<body>
		<div id='editdialogdiv'></div>
		<div id="overlay"></div>
		<div class="container" id="container">
