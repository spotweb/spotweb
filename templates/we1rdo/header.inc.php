<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
    	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
		<title><?php echo $pagetitle?></title>
		<base href='<?php echo $tplHelper->makeBaseUrl(); ?>'>
		<link rel='stylesheet' type='text/css' href='?page=statics&amp;type=css&amp;mod=<?php echo $tplHelper->getStaticModTime('css'); ?>'>
		<link rel='alternate' type='application/atom+xml' href='<?php echo $tplHelper->getPageUrl('atom', true) ?>'>
		<link rel='shortcut icon' href='?page=statics&amp;type=ico&amp;mod=<?php echo $tplHelper->getStaticModTime('ico'); ?>'>
		<script src='?page=statics&amp;type=js&amp;mod=<?php echo $tplHelper->getStaticModTime('js'); ?>' type='text/javascript'></script>

		<!-- Add code to initialize the tree when the document is loaded: -->
		<script type='text/javascript'>
		$(function(){
			$("a.spotlink").click(function(e){
				if(e.metaKey || e.altKey || e.shiftKey || e.button == 1) {
					e.stopImmediatePropagation();
				}
			});

			$("a.spotlink").fancybox({
				'width'			: '100%',
				'height' 		: '100%',
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

			$("#removedllistbtn").click(function(e) {
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
						var x = $("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = setTimeout( function() { $("li.info").html("History removed") }, 1000);
						setTimeout( function() { location.reload() }, 1500);
					}, // # complete
					dataType: "xml"
				});
			}); // erasedlsbtn
			
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
						var x = $("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						var x = $("li.info").html("Updated spots");
					}, // # complete
					dataType: "xml"
				});
			}); // updatebutton
			
			$("#markallasreadbtn").click(function(e) {
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
						var x = $("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
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
						$(temp).removeClass("succes");
						$(temp).addClass("loading");
					}, // # beforeSend
					complete: function(jqXHR, textStatus) {
						setTimeout( function() { 
							$(temp).removeClass("loading");
							$(temp).addClass("succes");
						}, 1000);
					}, // # complete
					dataType: "text"
				});
			}); // click
		});

		//Scrolling along
		$().ready(function() {
			$('#filterscroll').bind('change', function() {
				var scrolling = $(this).is(':checked');
				$.cookie('scrolling', scrolling, { path: '/', expires: 7 });
				toggleScrolling(scrolling);
			});

			var scrolling = $.cookie("scrolling");
			toggleScrolling(scrolling);
		});
		
		function toggleScrolling(state) {
			if (state == true || state == 'true') {
				$('#filterscroll').attr({checked:'checked', title:'Klik om sidebar niet meer mee te laten scrollen'});
				$("#filter").css('position', 'fixed');
			} else {
				$('#filterscroll').attr({title:'Klik om sidebar mee te laten scrollen'});
				$("#filter").css('position', 'static');
			}
		} // toggleScrolling

		function toggleFilterBlock(linkName,block,cookieName) {
			$(block).toggle();
			if ($.cookie(cookieName) == 'none') { var view = 'block'; } else { var view = 'none'; }
			toggleFilterImage(linkName, view);
			$.cookie(cookieName, view, { path: '/', expires: 7 });
		} // toggleFitlerBlock

		//Cookie uitlezen en in die staat op scherm toveren
		$(function(){
			var items = {'viewSearch': ['.hide', '#filterform_link'],
						'viewQuickLinks': ['ul.quicklinks', '#quicklinks_link'],
						'viewFilters': ['ul.filters', '#filters_link'],
						'viewMaintenance': ['ul.maintenancebox', '#maintenance_link']
			};
			
			// array doorlopen en actie ondernemen
			$.each(items, function(key, value) {
				var theState = $.cookie(key);
				$(value[0]).css('display', theState);
				toggleFilterImage(value[1], theState);
			});
		});

		function toggleFilterImage(linkName, state) {
			if (state == 'none') {
				$(linkName).removeClass("up");
				$(linkName).addClass("down");
			} else {
				$(linkName).removeClass("down");
				$(linkName).addClass("up");
			}
		} // toggleFilterImage

		function toggleWatchSpot(spot,action,spot_id) {
			// Add/remove watchspot
			$.get("?page=watchlist&action="+action+"&messageid="+spot);

			// Switch buttons
			$('#watchremove_'+spot_id).toggle();
			$('#watchadd_'+spot_id).toggle();
		} // toggleWatchSpot

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
