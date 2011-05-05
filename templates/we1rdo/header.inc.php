<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
    	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
		<title><?php echo $pagetitle?></title>
<?php if ($settings->get('deny_robots')) { echo "\t\t<meta name=\"robots\" content=\"noindex, nofollow\">\r\n"; } ?>
		<base href='<?php echo $tplHelper->makeBaseUrl("full"); ?>'>
		<link rel='stylesheet' type='text/css' href='?page=statics&amp;type=css&amp;mod=<?php echo $tplHelper->getStaticModTime('css'); ?>'>
		<link rel='alternate' type='application/atom+xml' href='<?php echo $tplHelper->getPageUrl('atom', true) . "&amp;sortby=stamp&amp;sortdir=DESC"; ?>'>
		<link rel='shortcut icon' href='?page=statics&amp;type=ico&amp;mod=<?php echo $tplHelper->getStaticModTime('ico'); ?>'>
		<script src='?page=statics&amp;type=js&amp;mod=<?php echo $tplHelper->getStaticModTime('js'); ?>' type='text/javascript'></script>
		<script type='text/javascript'>
		$(function(){
			// Attach the dynatree widget to an existing <div id="tree"> element
			// and pass the tree options as an argument to the dynatree() function:
			$("div#tree").dynatree({
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
		
		// Select or Deselect All checkboxes
		var checked=false;
		var frmname='';
		function checkedAll(frmname) {
			var valus= document.getElementById(frmname);
			if (checked==false) { 
				checked=true;
			} else { 
				checked = false; }
			for (var i =0; i < valus.elements.length; i++) {
				valus.elements[i].checked=checked;
			}
			multinzb()
		} // Select or Deselect All checkboxes
		</script>
	</head>
	<body>
    	<div id="overlay"></div>
		<div class="container" id="container">
