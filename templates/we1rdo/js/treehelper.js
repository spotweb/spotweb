		$(function(){
			// Attach the dynatree widget to an existing <div id="tree"> element
			// and pass the tree options as an argument to the dynatree() function:
			$("div#tree").dynatree({
				initAjax: { url: "?page=catsjson" },
			    checkbox: true, // Show checkboxes.
				persist: false, // Persist expand-status to a cookie
				selectMode: 3, //  1:single, 2:multi, 3:multi-hier
			    clickFolderMode: 2, // 1:activate, 2:expand, 3:activate and expand
			    ajaxDefaults: { // Used by initAjax option
					cache: true // false: Append random '_' argument to the request url to prevent caching.
				},
				onQuerySelect: function(flag, node) {
					if (!flag) {
						if (!node.data.strongnot) {
							node.data.strongnot = true;
							node.data.addClass = 'strongnotnode';
						} else {
							node.data.strongnot = false;
							node.data.addClass = node.data.addClass.replace('strongnotnode', '');
						} // else
						
						node.render(true);
						return (!node.data.strongnot);
					} else {
						return true;
					} // else					
				},
				onPostInit: function(isReloading, isError) {
					var formField = $("#search-tree");
					matchTree(formField[0].value, false);
				} // onPostInit
			});

			$("#filterform").submit(function() {
				var formField = $("#search-tree");
				
				// then append Dynatree selected 'checkboxes':
				var selectedNodes = $("#tree").dynatree("getTree").getSelectedNodes();
				var tmp = $.map(selectedNodes, function(node){
					if (node.data.strongnot) {
						return '~' + node.data .key;
					} else {
						return node.data.key;
					} // else
				}); // map
            	
				formField[0].value = tmp.join(',');
				
				if (formField[0].value.length == 0) {
					$(formField[0]).remove();
				} // if

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
					if (node) {
						node.select(false);
						node.data.strongnot = false;
					} // if
				} else if (keyList[i][0] == '~') {
					var node = tree.getNodeByKey(keyList[i].substr(1));
					if (node) {
						node.data.strongnot = true;
						node.data.addClass = 'strongnotnode';
						node.select(true);
					} // if
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
