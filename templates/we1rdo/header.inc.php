<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
    	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
		<title><?php echo $pagetitle?></title>
<?php if ($settings->get('deny_robots')) { echo "\t\t<meta name=\"robots\" content=\"noindex, nofollow\">\r\n"; } ?>
		<base href='<?php echo $tplHelper->makeBaseUrl(); ?>'>
		<link rel='stylesheet' type='text/css' href='?page=statics&amp;type=css&amp;mod=<?php echo $tplHelper->getStaticModTime('css'); ?>'>
		<link rel='alternate' type='application/atom+xml' href='<?php echo $tplHelper->getPageUrl('atom', true) ?>'>
		<link rel='shortcut icon' href='?page=statics&amp;type=ico&amp;mod=<?php echo $tplHelper->getStaticModTime('ico'); ?>'>
		<script src='?page=statics&amp;type=js&amp;mod=<?php echo $tplHelper->getStaticModTime('js'); ?>' type='text/javascript'></script>

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
			<div id="toolbar">
                <div class="notifications">
                    <?php if ($settings->get('show_multinzb')) { ?>
                    <p class="multinzb"><a class="button" onclick="downloadMultiNZB()" title="MultiNZB"><span class="count"></span></a><a class="clear" onclick="uncheckMultiNZB()" title="Reset selectie">[x]</a></p>
                    <?php } ?>
                </div>

<?php
	# Toon geen welkom terug boodschap voor de anonymous user
	if ($currentSession['user']['userid'] != 1) {
?>	
                <div class="logininfo"><p><span class="user" title="Laatst gezien: <?php echo $tplHelper->formatDate($currentSession['user']['lastvisit'], 'lastvisit'); ?> geleden"><?php echo $currentSession['user']['firstname']; ?></span></p></div>
<?php
    }
?>

                <span class="scroll"><input type="checkbox" name="filterscroll" id="filterscroll" value="Scroll" title="Wissel tussen vaste en meescrollende sidebar"><label>&nbsp;</label></span>

                <form id="filterform" action="">
<?php
	$search = array_merge(array('type' => 'Titel', 'text' => '', 'tree' => '', 'unfiltered' => ''), $search);
	if (empty($search['type'])) {
		$search['type'] = 'Titel';
	} # if
?>
                    <div><input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $search['tree']; ?>"></div>
<?php
	$filterColCount = 3;
	if ($settings->get('retrieve_full')) {
		$filterColCount++;
	} # if
?>
                    <table class="filters" summary="Filters">
                        <tbody>
                            <tr>
                                <td colspan='<?php echo $filterColCount;?>'><input class='searchbox' type="text" name="search[text]" value="<?php echo htmlspecialchars($search['text']); ?>"><span class="filtersubmit"><input type='submit' class="filtersubmit" value='>>' title='Zoeken'></span></td>
                            </tr>
                            <tr class="hide<?php if ($filterColCount == 3) {echo " short";} ?>"> 
                                <td> <input type="radio" name="search[type]" value="Titel" <?php echo $search['type'] == "Titel" ? 'checked="checked"' : "" ?> ><label>Titel</label> </td>
                                <td> <input type="radio" name="search[type]" value="Poster" <?php echo $search['type'] == "Poster" ? 'checked="checked"' : "" ?> ><label>Poster</label> </td>
                                <td> <input type="radio" name="search[type]" value="Tag" <?php echo $search['type'] == "Tag" ? 'checked="checked"' : "" ?> ><label>Tag</label> </td>
<?php if ($settings->get('retrieve_full')) { ?>
                                <td> <input type="radio" name="search[type]" value="UserID" <?php echo $search['type'] == "UserID" ? 'checked="checked"' : "" ?> ><label>UserID</label> </td>
<?php } ?>									
                            </tr>
                            <tr class="unfiltered hide">
                                <td colspan='<?php echo $filterColCount;?>'> <input type="checkbox" name="search[unfiltered]" value="true"  <?php echo $search['unfiltered'] == "true" ? 'checked="checked"' : "" ?>><label>Vergeet filters voor zoekopdracht</label> </td>
                            </tr>
                        </tbody>
                    </table>

                    <div id="tree" class="hide"></div>
                </form>
            </div>