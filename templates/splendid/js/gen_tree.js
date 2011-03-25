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

			/*
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
			});*/

			$(".erasedlsbtn").click(function(e) {
				e.preventDefault();

				var surl = this.href.split("?");
			
				$.ajax({
					url: surl[0],
					data: surl[1],
					context: $(this),
					error: function(jqXHR, textStatus, errorThrown) {
						alert('Error removing downloadlist');
					},
					beforeSend: function(jqXHR, settings) {
						var x = $("li.info").html("<img src='templates/splendid/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = setTimeout( function() { $("li.info").html("Geschiedenis is geleegd!") }, 1000);
						setTimeout( function() { location.reload() }, 1500);
					}, // # complete
					dataType: "xml"
				});
			}); // erasedlsbtn
			
			$(".updatespotsbtn").click(function(e) {
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
						var x = $("li.info").html("<img src='templates/splendid/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = $("li.info").html("Spots zijn geupdate!");
					}, // # complete
					dataType: "xml"
				});
			}); // updatebutton
			
			$(".markallasreadbtn").click(function(e) {
				e.preventDefault();

				var surl = this.href.split("?");
			
				$.ajax({
					url: surl[0],
					data: surl[1],
					context: $(this),
					error: function(jqXHR, textStatus, errorThrown) {
						alert('Error marking all as read');
					},
					beforeSend: function(jqXHR, settings) {
						var x = $("li.info").html("<img src='templates/splendid/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = setTimeout( function() { $("li.info").html("Alle spots zijn gemarkeerd als gelezen!") }, 1000);
						setTimeout( function() { location.reload() }, 1500);
					}, // # complete
					dataType: "xml"
				});
			}); // markallasreadbtn
						
			$("a.sabnzbd-button").click(function(e) {
				e.preventDefault();

				var surl = this.href.split("?");
				var temp = $(this);
			
				$.ajax({
					url: surl[0],
					data: surl[1],
					context: $(temp),
					error: function(jqXHR, textStatus, errorThrown) {
						// zie bij success(): alert(textStatus);
					},
					success: function(data, textStatus, jqXHR) {
						// We kunnen de returncode niet checken want cross-site
						// scripting is niet toegestaan, dus krijgen we de inhoud 
						// niet te zien
					},
					beforeSend: function(jqXHR, settings) {
						$(temp).html("<img class='sabnzbd-button' src='templates/splendid/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						setTimeout( function() { $(temp).html("<img class='sabnzbd-button' src='templates/splendid/img/succes.png' />") }, 1000);
					}, // # complete
					dataType: "text"
				});
			}); // click
		});

		function clearTree() {
		  $("#tree").dynatree("getRoot").visit(function(node) {
				node.select(false);
		  });
		  $('.dynatree-expanded').removeClass('dynatree-expanded');
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