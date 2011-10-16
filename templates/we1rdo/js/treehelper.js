		$(function(){
			// Attach the dynatree widget to an existing <div id="tree"> element
			// and pass the tree options as an argument to the dynatree() function:
			$("div#tree").dynatree({
				initAjax: { url: "?page=catsjson" + spotweb_currentfilter_params },
			    checkbox: true, // Show checkboxes.
				persist: false, // Persist expand-status to a cookie
				selectMode: 3, //  1:single, 2:multi, 3:multi-hier
			    clickFolderMode: 2, // 1:activate, 2:expand, 3:activate and expand
			    ajaxDefaults: { // Used by initAjax option
					cache: false // false: Append random '_' argument to the request url to prevent caching.
				},
				onQuerySelect: function(flag, node) {
					if (!flag) {
						if (!node.data.strongnot) {
							node.visit(function (node) {
								node.data.strongnot = true;
								node.data.addClass = 'strongnotnode';
							}, true); 
						} else {
							node.visit(function (node) {
								node.data.strongnot = false;
								node.data.addClass = node.data.addClass.replace('strongnotnode', '');
							}, true); 
						} // else
						
						node.render(true);
						return (!node.data.strongnot);
					} else {
						return true;
					} // else					
				}
			});

			$("#filterform").submit(function() {
				var formField = $("#search-tree");
				
				// then append Dynatree selected 'checkboxes':
				var selectedNodes = $("#tree").dynatree("getTree").getSelectedNodes();
				var tmp = $.map(selectedNodes, function(node){
					if (node.data.strongnot) {
						/* If our parent node is selected, don't bother selecting this one */
						if (node.parent) {
							if ((node.parent.data.strongnot) && (node.parent.isSelected())) {
								return ;
							} // if
						} // if
						
						return '~' + node.data.key;
					} else {
						/* If our parent node is selected, don't bother selecting this one */
						if (node.parent) {
							if ((!node.parent.data.strongnot) && (node.parent.isSelected())) {
								return ;
							} // if
						} // if
						
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
		
		function loadNewSpotTree() {
			// Attach the dynatree widget to an existing <div id="newspotcatselecttree"> element
			// and pass the tree options as an argument to the dynatree() function:
			$("div#newspotcatselecttree").dynatree({
				initAjax: { url: "?page=catsjson&category=0&subcatz=1&disallowstrongnot=1" },
			    checkbox: true, // Show checkboxes.
				persist: false, // Persist expand-status to a cookie
				selectMode: 3, //  1:single, 2:multi, 3:multi-hier
			    clickFolderMode: 2, // 1:activate, 2:expand, 3:activate and expand
			    ajaxDefaults: { // Used by initAjax option
					cache: false // false: Append random '_' argument to the request url to prevent caching.
				},
				onSelect: function(flag, node) {
					if (flag) {
						if (node.data.key.match('^cat\\d_z[0-9z]_a\\d+$')) {
							// Find the parent node, and deselect them all. 
							node.tree.getNodeByKey(node.data.key.match('^cat\\d_z[0-9z]_a')).visit(function(visitNode) {
								if (visitNode.data.key != node.data.key) { 
									visitNode.select(false);
								} // if
							});
						} // if
						
					} // if
					
					return true;
				} // onSelect
			});
		} // loadNewSpotTree
		

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
