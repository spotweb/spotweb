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
				persist: false, // Persist expand-status to a cookie
				selectMode: 3, //  1:single, 2:multi, 3:multi-hier
			    clickFolderMode: 2, // 1:activate, 2:expand, 3:activate and expand
				
				onPostInit: function(isReloading, isError) {
					var formField = $("#search-tree");
					matchTree(formField[0].value, false);
				} // onPostInit
			});

			
			$("#filterform").submit(function() {
				var formField = $("#search-tree");
				
				// then append Dynatree selected 'checkboxes':
				var tree = $("#tree").dynatree("getTree").serializeArray();
				var tmp = '';
				for(var i = 0; i < tree.length; i++) {
					tmp += tree[i].value + ",";
				} // for
				
				formField[0].value = tmp;

				return true;
			});
			
			$("img.sabnzbd-button").click(function(e) {
				e.preventDefault();

				$url = $(this).parent()[0].href.split("?");
				$(this).data("downloadpushed", "yes");
			
				$.ajax({
				  type: 'get',
				  url: $url[0],
				  data: $url[1],
				  async: true,
				 });
			}); // click
			
			$("img.sabnzbd-button").ajaxComplete(function(event, XMLHttpRequest, ajaxOptions) {
				var elm = $(event.target);

				if (elm.data("downloadpushed") == "yes") {	
					elm.remove();
				} // if
			}); // # ajaxComplete
			
			$("img.sabnzbd-button").ajaxStart(function(e) {	
				var elm = $(e.target);

				if (elm.data("downloadpushed") == "yes") {	
					this.src = "images/loading.gif";
				} // if
			}); // # ajaxStart
			
		});

		function clearTree() {
		  $("#tree").dynatree("getRoot").visit(function(node) {
				node.select(false);
		  });
		} // clearTree()
		
		function matchTree(s, dosubmit) {
			clearTree();
			
			var tree = $("#tree").dynatree("getTree");
			var keyList = s.split(",");
			var i;
			
			for(i = 0; i < keyList.length; i++) {
				if (keyList[i][0] == '!') {
					var node = tree.getNodeByKey(keyList[i].substr(1));
					if (node) node.select(false);
				} else {
					var node = tree.getNodeByKey(keyList[i]);
					if (node) node.select(true);
				} // if
			} // for
			
			if (dosubmit) {
				$("#filterform").submit();
			} // if
			return false;
		} // matchTree()
		
		</script>
	</head>
	
	<body>
		<div class="">
			<!-- The header -->
			<br >
		</div>
			
		<div class="container">
