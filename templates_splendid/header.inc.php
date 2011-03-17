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
		$(function(){
			$("a.spotlink").fancybox({
				'width'			: '80%',
				'height' 		: '94%',
				'autoScale' 	: false,
				'transitionIn'	: 'none',
				'transitionOut'	: 'none',
				'type'			: 'iframe'
			})
			$('#spots').load('?search[tree]=<?php if(!empty($settings['index_filter']['tree'])) echo $settings['index_filter']['tree'] ?>&ajax=1');
		});
		</script>
		
	</head>
	
	<body>
		<div class="container">
			<div id="page_header" align="center">
			  <?php if(empty($_GET['page']) || $_GET['page'] == 'index') { ?>
			  <!--[if IE]><div class="ie_error">DEZE TEMPLATE ONDERSTEUND HELAAS (NOG) GEEN INTERNET EXPLORER!</div><![endif]-->
			  <div class="menu_top"></div>
			  <?php } ?>
			</div>

<?php } ?>