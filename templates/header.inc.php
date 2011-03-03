<html>
	<head>
		<title><?php echo $pagetitle?></title>
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

			$("#updatespotsbtn").click(function(e) {
				e.preventDefault();

				var surl = this.href.split("?");
			
				$.ajax({
					url: surl[0],
					data: surl[1],
					context: $(this),
					error: function(jqXHR, textStatus, errorThrown) {
						alert('Error fetching updates');
					},
					success: function(data, textStatus, jqXHR) {
						// We kunnen de returncode niet checken want cross-site
						// scripting is niet toegestaan, dus krijgen we de inhoud 
						// niet te zien
						var totproc = $(data).find("totalprocessed")[0];
						if (totproc.textContent != "0") {
							location.reload();
						} // if 
					},
					beforeSend: function(jqXHR, settings) {
						var x = $("#updatespotimg")[0].src = "images/loading.gif";
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = $("#updatespotimg")[0].src = "images/gobutton.png";
					}, // # complete
					dataType: "xml"
				});
			}); // updatebutton
			
			$("img.sabnzbd-button").click(function(e) {
				e.preventDefault();

				var surl = $(this).parent()[0].href.split("?");
			
				$.ajax({
					url: surl[0],
					data: surl[1],
					context: $(this),
					error: function(jqXHR, textStatus, errorThrown) {
						alert(textStatus);
					},
					success: function(data, textStatus, jqXHR) {
						// We kunnen de returncode niet checken want cross-site
						// scripting is niet toegestaan, dus krijgen we de inhoud 
						// niet te zien
					},
					beforeSend: function(jqXHR, settings) {
						$(this).src = "images/loading.gif";
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						$(this).remove();
					}, // # complete
					dataType: "text"
				});
			}); // click
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
