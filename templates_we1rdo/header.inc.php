<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title><?php echo $pagetitle?></title>
		<link rel='stylesheet' type='text/css' href='<?php echo $tplHelper->makeBaseUrl(); ?>js/dynatree/skin-vista/ui.dynatree.css'>
		<link rel="stylesheet" href="<?php echo $tplHelper->makeBaseUrl(); ?>templates_we1rdo/style.css" type="text/css">
		<link rel="stylesheet" type="text/css" href="<?php echo $tplHelper->makeBaseUrl(); ?>js/fancybox/jquery.fancybox-1.3.4.css" media="screen" />
		<link rel="alternate" type="application/atom+xml" href="<?php echo $tplHelper->getPageUrl('atom', true) ?>"/>
		<link rel='shortcut icon' href='<?php echo $tplHelper->makeBaseUrl(); ?>templates_we1rdo/img/favicon.ico'>

		<!-- Jquery, necessary for dynatree -->
		<script src='<?php echo $tplHelper->makeBaseUrl(); ?>js/jquery/jquery.min.js' type='text/javascript'></script>
		<script src='<?php echo $tplHelper->makeBaseUrl(); ?>js/jquery/jquery-ui.custom.min.js' type='text/javascript'></script>
		<script src='<?php echo $tplHelper->makeBaseUrl(); ?>js/jquery/jquery.cookie.js' type='text/javascript'></script>

		<!-- dynatree iteslf -->
		<script src='<?php echo $tplHelper->makeBaseUrl(); ?>js/dynatree/jquery.dynatree.min.js' type='text/javascript'></script>

		<!-- fancybox -->
		<script type="text/javascript" src="<?php echo $tplHelper->makeBaseUrl(); ?>js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>

		<!-- Add code to initialize the tree when the document is loaded: -->
		<script type='text/javascript'>
		$(function(){
			$("a.spotlink").click(function(e){
				if(e.metaKey || e.altKey || e.shiftKey || e.button == 1) {
					e.stopImmediatePropagation();
				}
			});

			$("a.spotlink").fancybox({
				'width'			: '80%',
				'height' 		: '94%',
				'autoScale' 	: false,
				'transitionIn'	: 'none',
				'transitionOut'	: 'none',
				'type'			: 'iframe'
			})
		});

		$(function(){
			// Attach the dynatree widget to an existing <div id="tree"> element
			// and pass the tree options as an argument to the dynatree() function:
			$("#tree").dynatree({
				initAjax: { url: "<?php echo $tplHelper->getPageUrl('catsjson');?>" },
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
						var x = $("li.info").html("<img src='<?php echo $tplHelper->makeBaseUrl(); ?>templates_we1rdo/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = setTimeout( function() { $("li.info").html("History removed") }, 1000);
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
						var x = $("li.info").html("<img src='<?php echo $tplHelper->makeBaseUrl(); ?>templates_we1rdo/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = $("li.info").html("Updated spots");
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
						var x = $("li.info").html("<img src='<?php echo $tplHelper->makeBaseUrl(); ?>templates_we1rdo/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = setTimeout( function() { $("li.info").html("All marked as read.") }, 1000);
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
						$(temp).html("<img class='sabnzbd-button loading' src='<?php echo $tplHelper->makeBaseUrl(); ?>templates_we1rdo/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						setTimeout( function() { $(temp).html("<img class='sabnzbd-button' src='<?php echo $tplHelper->makeBaseUrl(); ?>templates_we1rdo/img/succes.png' />") }, 1000);
					}, // # complete
					dataType: "text"
				});
			}); // click
		});
		
		$(function(){
			var theStateSearch = $.cookie("viewSearch");
			
			$(".showSearch").click(function(e) {
					$("#filterform").show();
					$.cookie("viewSearch", "block", { path: '/', expires: 7 });
					theStateSearch = "block";
			});
			
			$(".hideSearch").click(function(e) {
					$("#filterform").hide();
					$.cookie("viewSearch", "none", { path: '/', expires: 7 });
					theStateSearch = "none"
			});
			
			$("#filterform").css('display', theStateSearch);

			var theStateMaintenance = $.cookie("viewMaintenance");
			
			$(".showMaintenance").click(function(e) {
					$("ul.maintenancebox").show();
					$.cookie("viewMaintenance", "block", { path: '/', expires: 7 });
					theStateMaintenance = "block";
			});
			
			$(".hideMaintenance").click(function(e) {
					$("ul.maintenancebox").hide();
					$.cookie("viewMaintenance", "none", { path: '/', expires: 7 });
					theStateMaintenance = "none"
			});
			
			$("ul.maintenancebox").css('display', theStateMaintenance);
			
			var theStateFilters = $.cookie("viewFilters");
			
			$(".showFilters").click(function(e) {
					$("ul.filters").show();
					$.cookie("viewFilters", "block", { path: '/', expires: 7 });
					theStateFilters = "block";
			});
			
			$(".hideFilters").click(function(e) {
					$("ul.filters").hide();
					$.cookie("viewFilters", "none", { path: '/', expires: 7 });
					theStateFilters = "none"
			});
			
			$("ul.filters").css('display', theStateFilters);
		});
		
		function addWatchSpot(spot,spot_id) {
			
			// Set watchspot
			$.get("?page=watchlist&action=add&messageid="+spot);
			
			// Switch buttons
			$('#watch_'+spot_id).hide();
			$('#watched_'+spot_id).show();
		}
		
		function removeWatchSpot(spot,spot_id) {
			
			// Set watchspot
			$.get("?page=watchlist&action=remove&messageid="+spot);
			
			// Switch buttons
			$('#watch_'+spot_id).show();
			$('#watched_'+spot_id).hide();
		}

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
		
		//// Check for checkboxes at submit
		 $(function() {
            $('input[id$=multisubmit]').click(function(e) {
                var checked = $(':checkbox:checked').length;
                if (checked == 0) {
                    alert('Je moet minstens 1 spot selecteren!');
                    e.preventDefault();
                }
            });
        });
		
			//// Select or Deselect All checkboxes
		var checked=false;
		var frmname='';
		function checkedAll(frmname)
		{
			var valus= document.getElementById(frmname);
			if (checked==false)
			{
				checked=true;
			}
			else
			{
				checked = false;
			}
			for (var i =0; i < valus.elements.length; i++) 
			{
				valus.elements[i].checked=checked;
			}
		} //// Select or Deselect All checkboxes
		</script>
		
	</head>
	
	<body>
		<div class="container">
