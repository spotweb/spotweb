<!--
#########################################
#                                       #
#                Author:                #
#          Splendid (Tweakers)          #
#                                       #
#########################################
-->
<?php if(empty($_GET['ajax'])) { ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title><?php echo $pagetitle?></title>
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<style type="text/css">
		  @import url('js/dynatree/skin-vista/ui.dynatree.css');
		  @import url('templates_splendid/style.css');
		  @import url('js/fancybox/jquery.fancybox-1.3.4.css');
		</style>


		<!-- Jquery, necessary for dynatree -->
		<script src='js/jquery/jquery.min.js' type='text/javascript'></script>
		<script src='js/jquery/jquery-ui.custom.min.js' type='text/javascript'></script>
		<script src='js/jquery/jquery.cookie.js' type='text/javascript'></script>
		
		<script src='templates_splendid/js/gen_tree.js' type='text/javascript'></script>
		<script src='templates_splendid/js/scripts.js' type='text/javascript'></script>

		<!-- dynatree iteslf -->
		<script src='js/dynatree/jquery.dynatree.min.js' type='text/javascript'></script>

		<!-- fancybox -->
		<script type="text/javascript" src="js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>

		<!-- Add code to initialize the tree when the document is loaded: -->
		<script type='text/javascript'>
		$(function() {
			$('#spots').load('?search[tree]=<?php if(!empty($settings['index_filter']['tree'])) echo $settings['index_filter']['tree'] ?>&ajax=1');
		});
		var min_width = 295;
		</script>
		<!--[if IE]><script type='text/javascript'>var min_width = 315;</script><![endif]-->
	</head>
	
	<body>
		
		<div id="download_menu">
		  <div><a onclick="downloadMultiple()">Download <span id="total_spots"></span></a></div>
		</div>
		
		<div class="container">
			<div id="page_header" align="center">
			  <?php if(empty($_GET['page']) || $_GET['page'] == 'index') { ?>
			  <div class="top_menu"><a href="?page=watchlist" class="spotlink"> Watchlist</a></div>
			  <div class="menu_top"></div>
			  <?php 
			    } else if($_GET['page'] == 'getspot') {
			      echo "<div id=\"page_title\">Spotinfo</div>\n";
			    } else {
			      echo '<div id="page_title">'.ucfirst($_GET['page'])."</div>\n";
			    }
			  ?>
			</div>
<?php } ?>