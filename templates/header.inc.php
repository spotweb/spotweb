<html>
	<head>
		<link rel='stylesheet' type='text/css' href='js/dynatree/skin-vista/ui.dynatree.css'>
		<link rel="stylesheet" href="css/style.css" type="text/css" media="screen, projection">
		<link rel="stylesheet" href="css/tablecloth.css" type="text/css" media="screen, projection">

		<!-- Jquery, necessary for dynatree -->
		<script src='js/jquery/jquery.min.js' type='text/javascript'></script>
		<script src='js/jquery/jquery-ui.custom.min.js' type='text/javascript'></script>
		<script src='js/jquery/jquery.cookie.js' type='text/javascript'></script>

		<!-- dynatree iteslf -->
		<script src='js/dynatree/jquery.dynatree.min.js' type='text/javascript'></script>

		<!-- Add code to initialize the tree when the document is loaded: -->
		<script type='text/javascript'>
		$(function(){
			// Attach the dynatree widget to an existing <div id="tree"> element
			// and pass the tree options as an argument to the dynatree() function:
			$("#tree").dynatree({
				initAjax: { url: "?page=catsjson" },
			    checkbox: true, // Show checkboxes.
				persist: true, // Persist expand-status to a cookie
				selectMode: 3, //  1:single, 2:multi, 3:multi-hier
			    clickFolderMode: 2 // 1:activate, 2:expand, 3:activate and expand
			});
		});
		</script>
	</head>
	
	<body>
		<div class="">
			<!-- The header -->
			<br >
		</div>
			
		<div class="container">
