$.address.init(function() {
	$('.spotlink').address();
}).externalChange(
		function(event) {
			basePATH = location.href.replace('#' + $.address.value(), '');
			if ($.address.value() == '/' && basePATH.indexOf('/?page=getspot') < 0 && basePATH.indexOf('/details/') < 0) {
				closeDetails(0);
				
				var currentSpot = $('table.spots tr.active');
				if ((currentSpot) && (currentSpot.offset() != null)) {
					if (currentSpot.offset().top > $(window).height()) {
						$(document).scrollTop($('table.spots tr.active').offset().top - 50);
					} // if
				} // if
			} else if ($.address.value() != '/') openSpot($('table.spots tr.active a.spotlink'), $.address.value());
		});

$(function(){
// console.time("10th-ready");
	//ready
	$("a.spotlink").click(function(e) { e.preventDefault(); });
	$('.showTipTip a.spotlink').each(applyTipTip);
	if(navigator.userAgent.toLowerCase().indexOf('chrome')>-1)$('a.spotlink').mouseup(function(e){if(e.which==2||(e.metaKey||e.ctrlKey)&&e.which==1){$(this).attr('rel','address:');}});
	$("a[href^='http']").attr('target','_blank');
	
    $("#filterform input").keypress(function (e) {
		if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
			$('form#filterform').find('input[type=submit].default').click();
			return false;
		} else {
			return true;
		}
    });	
// console.timeEnd("10th-ready");
});

// createBaseURL
function createBaseURL() {
	var baseURL = '$HTTP_S://'+window.location.hostname+window.location.pathname;
	if (window.location.port != '') {
		var baseURL = '$HTTP_S://'+window.location.hostname+':'+window.location.port+window.location.pathname;
	}
	return baseURL;
}

// Detecteer aanwezigheid scrollbar binnen spotinfo pagina
function detectScrollbar() {
	var $divDetails = $("div#details"); 
		
	if (($divDetails) && ($divDetails.offset() != null)) {
		if(($divDetails.outerHeight() + $divDetails.offset().top <= $(window).height())) {
			$divDetails.addClass("noscroll");
		} else {
			$divDetails.removeClass("noscroll");
		}
	} // if
}

// openSpot in overlay
function openSpot(id,url) {
	if (!spotweb_security_allow_spotdetail) {
		return false;
	} // if
	
	if($("#overlay").is(":visible")) {
		$("#overlay").addClass('notrans');
	}

	$("table.spots tr.active").removeClass("active");
	$(id).parent().parent().addClass('active');
	$("table.spots tr.active td.title").removeClass("new");

	if ($(id).attr('rel') ) {
		openNewWindow();
		setTimeout("$('a.spotlink').removeAttr('rel');",1);   
		return false;
	} //chrome
	
	var messageid = url.split("=")[2];

	$("#overlay").addClass('loading');
	$("#overlay").empty().show();

	var scrollLocation = $(document).scrollTop();
	$("#overlay").load(url+' #details', function() {
		$("div.container").removeClass("visible").addClass("hidden");
		$("#overlay").removeClass('loading notrans');
		$("body").addClass("spotinfo");

		if($("#overlay").children().size() == 0) {
			alert("<t>Error while loading this page, you will be returned automaticly to the mainview</t>");
			closeDetails(scrollLocation);
		}

		$("a.closeDetails").click(function(){ 
			$.address.value("");
			if ($('table.spots tr.active').offset().top > $(window).height())scrollLocation = $('table.spots tr.active').offset().top - 50;
			closeDetails(scrollLocation); 
		});

		$("a[href^='http']").attr('target','_blank');
		$(window).bind("resize", detectScrollbar);

		postCommentsForm();
		postReportForm();
		postBlacklistForm();
		
		if (spotweb_retrieve_commentsperpage > 0) {
			loadComments(messageid,spotweb_retrieve_commentsperpage,'0');
		} // if
		loadSpotImage();
	});
}

/*
 * Refresht een tab in een bepaalde tab lijst, 
 * kan als callback gegeven worden aan showDialog()
 */
function refreshTab(tabName) {    
	var tab = $('#' + tabName);
	
	var selected = tab.tabs('option', 'selected');
	tab.tabs('load', selected);
} // refreshTab

	
/*
 * Helper function to open a dialog, a couple of parameters are required.
 *
 * divid = id of a dummy div which should be used to create a dialog
 * title = title of the dialogbox
 * url = URL of the HTML content to load into the dialog
 * formname = Formname, necessary to attach the submit buttons
 * buttonClick = Function to be called when the submit button is pressed
 * successAction = choice of 'autoclose', 'showresultonly', 'reload'
 * closeCb = Function which should be called when the dialog is closed
 * openCb = Function which should be called when the HTML content of the dialog is loaded
 */
function openDialog(divid, title, url, formname, buttonClick, successAction, closeCb, openCb) {
	var $dialdiv = $("#" + divid);
  
    if (!$dialdiv.is(".ui-dialog-content")) {
		// en nu kunnen we de dialog wel tonen
		$dialdiv.dialog( {
			title: title,
			autoOpen: false,
			resizable: false,
			position: 'center',
			stack: true,
			closeOnEscape: true,
			height: 'auto',
			width: 'auto',
			modal: true
		} );
	} // if

	// Update de dialogs' title, de tweede manier is er omdat het niet altijd goed
	// werkt met de 1e methode
	$dialdiv.dialog("option", 'title', title);
	$("span.ui-dialog-title").text(title);
	
	/* submit button handler */
	if (!buttonClick) {
		var buttonClick = function() {
			// In deze context is 'this' de submit button waarop gedrukt is,
			// dus die data voegen we gewoon aan de post data toe.
			var formdata = $(this).attr("name") + "=" + $(this).val();  
			formdata = $(this.form).serialize() + "&" + formdata;
			
			// post de data
			$.ajax({
				type: "POST",
				url: this.form.action,
				dataType: "xml",
				data: formdata,
				success: function(xml) {
					var $dialdiv = $("#"+divid)
					var result = $(xml).find('result').text();
					
					if ((result == 'success') && (successAction == 'autoclose')) {
						$dialdiv.dialog('close');
						$dialdiv.empty();
						
						if (closeCb) {
							closeCb();
						} // if
					} else {						
						/* We herladen de content zodat eventuele dialog wijzigingen duidelijk zijn */
						if (successAction == 'reload') {
							loadDialogContent(false);
						} // if
						
						if ((successAction == 'showresultsonly') && (result == 'success')) {
							$dialdiv.empty();
							
							/* Create the empty elements to show the errors/information in */
							$dialdiv.html("<ul class='formerrors'></ul><ul class='forminformation'></ul>");
						} // if

						var $formerrors = $dialdiv.find("ul.formerrors");
						$formerrors.empty();
						$('errors', xml).each(function() {
							$formerrors.append("<li>" + $(this).text() + "</li>");
						}); // each

						// Add the information items to the form
						var $forminfo = $dialdiv.find("ul.forminformation");
						$forminfo.empty();
						$('info', xml).each(function() {
							$forminfo.append("<li>" + $(this).text() + "</li>");
						}); // each
					} // if post was not succesful
				} // success()
			}); // ajax call om de form te submitten
			
			return false; // standaard button submit supressen
		} // buttonClick
	} // if not defined
	
	/*
	 * definieer een dialog loader functie welke tegelijkertijd
	 * de submit buttons attached, deze wordt namelijk aangeroepen
	 * als een dialog submit succesvol is, maar de dialog niet gesloten
	 * mag worden. Dat is namelijk de simpelste manier om de content
	 * te refreshen
	 */
	function loadDialogContent(async) {
		/* en laad de werkelijke content */
		$.ajax({
			type: "GET",
			dataType: "html",
			async: async,
			url: url,
			data: {},
			success: function(response) {
				// Laad de content van de pagina in de dialog zodat we die daarna 
				// kunnen laten zien
				var $dialdiv = $("#" + divid);
				$dialdiv.empty().html(response);
				
				// sla de geladen url op zodat we het resultaat zien
				$dialdiv.data('dialogurl', url);
				
				// we vragen vervolgens alle submit buttons op, hier gaan we dan een
				// form submit handler aan vast knopen. Dit is nodig omdat standaard
				// de form submit handler van jquery niet weet op welke knop er gedrukt
				// is, dus moeten we wat doen om dat duidelijk te krijgen.
				//var $buttons = $("form." + formname + " input[type='submit']"); 
				var $buttons = $("#" + divid + " input[type='submit']"); 
				$buttons.click(buttonClick)

				// Call the open callback
				if (openCb) {
					openCb();
				} // if
				
				// en toon de dialog
				$dialdiv.dialog('open');
					
				return false; // standaard link actie voor openen dialog supressen
			} // success function
		}); // ajax call
	} // loadDialogContent

	// en laad de content
	loadDialogContent(true);
	return false;
} // openDialog

// Open spot in los scherm
function openNewWindow() {
	url = $('table.spots tr.active a.spotlink').attr("onclick").toString().match(/"(.*?)"/)[1];
	window.open(url);
}

// En maak de overlay onzichtbaar
function closeOverlay() {
	$("div.container").removeClass("hidden").addClass("visible");
	$("#overlay").hide();
	$("#details").remove();
} // closeOverlay

// Sluit spotinfo overlay
function closeDetails(scrollLocation) {
	closeOverlay();
	$("body").removeClass("spotinfo");
	$(document).scrollTop(scrollLocation);
}

// Laadt nieuwe spots in overzicht wanneer de onderkant wordt bereikt
$(function(){
	//ready
// console.time("2nd-ready");
	var pagenr = $('#nextPage').val();
	$(window).scroll(function() {
		var url = '?direction=next&data[spotsonly]=1&pagenr='+pagenr+$('#getURL').val()+' #spots';

		if($(document).scrollTop() >= $(document).height() - $(window).height() && $(document).height() >= $(window).height() && pagenr > 0 && $("#overlay").is(':hidden')) {
			if(!($("div.spots").hasClass("full"))) {
				var scrollLocation = $("div.container").scrollTop();
				$("#overlay").show().addClass('loading');
				$("div#overlay").load(url, function() {				
					if($("div#overlay tbody#spots").children().size() < $('#perPage').val()) {
						$("table.footer").remove();
						$("div.spots").addClass("full");
					}
					$("#overlay").hide().removeClass('loading'); 
					$("tbody#spots").append($($("div#overlay tbody#spots").html()).fadeIn('slow'));
					$("div#overlay").empty();
					$("a.spotlink").click(function(e) { e.preventDefault(); });
					$(".showTipTip a.spotlink").each(applyTipTip);
					
					pagenr++;
					$("td.next > a").attr("href", url);
					$("div.container").scrollTop(scrollLocation);
				});
			}
		}
	});
// console.timeEnd("2nd-ready");
});

// Haal de comments op en zet ze per batch op het scherm
function loadComments(messageid,perpage,pagenr) {
	if (!spotweb_security_allow_view_comments) {
		return false;
	} // if 
	
	var xhr = null;
	xhr = $.get('?page=render&tplname=comment&messageid='+messageid+'&pagenr='+pagenr+'&perpage='+perpage, function(html) {
		count = $(html+' > li').length / 2;
		if (count == 0 && pagenr == 0) {
			$("#commentslist").append("<li class='nocomments'><t>No (verified) comments found.</t></li>");
		} else {
			$("span.commentcount").html('# '+$("#commentslist").children().not(".addComment").size());
		}

		$("#commentslist").append($(html).fadeIn('slow'));
		$("#commentslist > li:nth-child(even)").addClass('even');
		$("#commentslist > li.addComment").next().addClass('firstComment');

		pagenr++;
		if (count >= 1) { 
			loadComments(messageid,perpage,pagenr);
		} else {
			detectScrollbar();
		}
	});
	$("a.closeDetails").click(function() { xhr.abort() });
}

function postReportForm() {
	$("form.postreportform").submit(function(){ 
		new spotPosting().postReport(this,postReportUiStart,postReportUiDone); 
		return false;
	});	
}

function blacklistSpotterId(spotterId) {
	$("input[name='blacklistspotterform[spotterid]']").val(spotterId); 
	$('form.blacklistspotterform').submit();
} // blacklistSpotterId

function whitelistSpotterId(spotterId) {
	$("input[name='blacklistspotterform[spotterid]']").val(spotterId);
	$("input[name='blacklistspotterform[idtype]']").val('2');
	$("input[name='blacklistspotterform[origin]']").val('Whitelisted by user');
	$('form.blacklistspotterform').submit();
} // blacklistSpotterId



function validateNntpServerSetting(settingsForm, serverArrayId) {
	$("#servertest_" + serverArrayId + "_loading").show();

	var formData = 'data[host]=' + settingsForm.elements[settingsForm.name + '[' + serverArrayId + '][host]'].value;

	if (settingsForm.elements[settingsForm.name + '[' + serverArrayId + '][enc][switch]'].checked) {
		formData += '&data[enc]=' + settingsForm.elements[settingsForm.name + '[' + serverArrayId + '][enc][select]'].value;
	} // if

	formData += '&data[port]=' + settingsForm.elements[settingsForm.name + '[' + serverArrayId + '][port]'].value;
	formData += '&data[user]=' + settingsForm.elements[settingsForm.name + '[' + serverArrayId + '][user]'].value;
	formData += '&data[pass]=' + settingsForm.elements[settingsForm.name + '[' + serverArrayId + '][pass]'].value;

	$.ajax({
		type: "POST",
		url: "?page=render&tplname=validatenntp", 
		dataType: "xml",
		data: formData,
		success: function(xml) {
			var result = $(xml).find('result').text();
			$("#servertest_" + serverArrayId + "_loading").hide();

			/* Remove existing style and contents from from the string */
			$("#servertest_" + serverArrayId + "_result")
				.removeClass("servertest_" + serverArrayId + "_result_success")
				.removeClass("servertest_" + serverArrayId + "_result_fail")
				.empty();

					
			if (result == 'success') {
				$("#servertest_" + serverArrayId + "_result")
					.addClass("servertest_" + serverArrayId + "_result_success")
					.text("OK");
			} else {
				$("#servertest_" + serverArrayId + "_result")
					.addClass("servertest_" + serverArrayId + "_result_fail")
					.text($(xml).find('error').text());
			} // else
		} // success
	}); // ajax call om de form te submitten

	return false;
} // validateNntpServerSetting

function postBlacklistForm() {
	$("form.blacklistspotterform").submit(function(){ 
		formdata = $(this).serialize();
		
		$.ajax({
			type: "POST",
			url: this.action, 
			dataType: "xml",
			data: formdata,
			success: function(xml) {
				$(".blacklistuserlink_" + $("input[name='blacklistspotterform[spotterid]']").val()).remove();
			} // success
		}); // ajax call om de form te submitten
		return false;
	}); // submit
} // postBlacklistForm


// Load post comment form
function postCommentsForm() {
	$("li.addComment a.togglePostComment").click(function(){
		if($("li.addComment div").is(":hidden")) {
			$("li.addComment div").slideDown(function(){
				detectScrollbar();
			});
			$("li.addComment a.togglePostComment span").addClass("up").parent().attr("title", "<t>Add comment (hide)</t>");
		} else {
			$("li.addComment div").slideUp(function(){
				detectScrollbar();
			});
			$("li.addComment a.togglePostComment span").removeClass("up").parent().attr("title", "<t>Add comment (show)</t>");
		}
	});

	for (i=1; i<=10; i++) {
		$("li.addComment dd.rating").append("<span id='ster"+i+"'></span>");
		sterStatus(i, 0);
	}

	var rating = 0;
	$("li.addComment dd.rating span").click(function() {
		if($(this).index() == rating) {
			rating = 0;
		} else {
			rating = $(this).index();
		}

		$("li.addComment dd.rating span").each(function(){
			sterStatus($(this).index(), rating);
		});
		$("li.addComment input[name='postcommentform[rating]']").val(rating);
	});

	function sterStatus(id, rating) {
		if (id == 1) { ster = '<t>star</t>'; } else { ster = '<t>stars</t>'; }

		if (id < rating) {
			$("span#ster"+id).addClass("active").attr('title', '<t>Rate spot</t> '+id+' '+ster);
		} else if (id == rating) {
			if (id == 1) {
				$("span#ster"+id).addClass("active").attr('title', "<t>Don't give any star</t>");
			} else {
				$("span#ster"+id).addClass("active").attr('title', "<t>Don't give any stars</t>");
			} // if
		} else {
			$("span#ster"+id).removeClass("active").attr('title', '<t>Rate spot</t> '+id+' '+ster);
		}
	}

	$("form.postcommentform").submit(function(){ 
		new spotPosting().postComment(this,postCommentUiStart,postCommentUiDone); 
		return false;
	});	
}

// Laadt de spotImage wanneer spotinfo wordt geopend
function loadSpotImage() {
	if (!spotweb_security_allow_view_spotimage) {
		return false;
	} // if
	
	$('img.spotinfoimage').hide();
	$('a.postimage').addClass('loading');

	$('img.spotinfoimage').load(function() {
		$('a.postimage').removeClass('loading');
		$(this).show();
		$('a.postimage').css({
			'width': $("img.spotinfoimage").width(),
			'height': $("img.spotinfoimage").height()
		})
		$('a.postimage').attr('title', '<t>Click on this image to show real size (i)</t>');
		detectScrollbar();
	})
	.each(function() {
		// From the jQuery comments: http://api.jquery.com/load-event/
		if (this.complete || (jQuery.browser.msie && parseInt(jQuery.browser.version) == 6)) {
			$(this).trigger("load");
		}
	});
}

function toggleImageSize() {
	if($("img.spotinfoimage").hasClass("full")) {
		$("img.spotinfoimage").removeClass("full");
		$("img.spotinfoimage").removeAttr("style");
		$('a.postimage').attr('title', '<t>Click on this image to show real size (i)</t>');
	} else {
		$('a.postimage').attr('title', '<t>Click image to reduce</t>');
		$("img.spotinfoimage").addClass("full");
		$("img.spotinfoimage").css({
			'max-width': $("div#overlay").width() - 5,
			'max-height': $("div#overlay").height() - 35
		});
	}
}

// Bind keys to functions
$(function(){
// console.time("3rd-ready");
	//ready
	$('table.spots tbody tr').first().addClass('active');

	var $document = $(document);
	$document.bind('keydown', 'k', function(){if(!($("div#overlay").hasClass("loading"))) {spotNav('prev')}});
	$document.bind('keydown', 'j', function(){if(!($("div#overlay").hasClass("loading"))) {spotNav('next')}});
	$document.bind('keydown', 'o', function(){if($("#overlay").is(':hidden')){$('table.spots tbody tr.active .title a.spotlink').click()}});
	$document.bind('keydown', 'return', function(){if($("#overlay").is(':hidden')){$('table.spots tbody tr.active .title a.spotlink').click()}});
	$document.bind('keydown', 'u', function(){$("a.closeDetails").click()});
	$document.bind('keydown', 'esc', function(){$("a.closeDetails").click()});
	$document.bind('keydown', 'i', toggleImageSize);
	$document.bind('keydown', 's', function(){if($("#overlay").is(':visible') || $('#details').hasClass("external")) {$("#details a.sabnzbd-button").click()} else {$("tr.active a.sabnzbd-button").click()}});
	$document.bind('keydown', 'n', function(){if($("#overlay").is(':visible') || $('#details').hasClass("external")) {location.href = $("#details a.nzb").attr('href')} else if($("th.nzb").is(":visible")) {location.href = $("tr.active a.nzb").attr('href')}});
	$document.bind('keydown', 'w', function(){if($("#overlay").is(':visible') || $('#details').hasClass("external")) {$("#details th.watch a:visible").click()} else if($("div.spots").hasClass("watchlist")) {location.href = $("tr.active td.watch a").attr('href')} else {$("tr.active td.watch a:visible").click()}});
	$document.bind('keydown', 't', function(){openNewWindow()});
	$document.bind('keydown', 'h', function(){location.href = '?search[tree]=&search[unfiltered]=true'});
	$document.bind('keydown', 'm', downloadMultiNZB);
	$document.bind('keydown', 'c', checkMultiNZB);
// console.timeEnd("3rd-ready");
});

// Keyboard navigation functions
function spotNav(direction) {	
	var current = $('table.spots tbody tr.active');
	var prev = current.prevUntil('tr.header').first();
	var next = current.next().first();

	if (direction == 'prev' && prev.size() == 1) {
		current.removeClass('active');
		prev.addClass('active');
		if($("#overlay").is(':visible')) {
			$("div.container").removeClass("hidden").addClass("visible");
			$(document).scrollTop($('table.spots tr.active').offset().top - 50);
			$('table.spots tbody tr.active .title a.spotlink').click();
		}
	} else if (direction == 'next' && next.size() == 1) {
		current.removeClass('active');
		next.addClass('active');
		if($("#overlay").is(':visible')) {
			$("div.container").removeClass("hidden").addClass("visible");
			$(document).scrollTop($('table.spots tr.active').offset().top - 50);
			$("table.spots tbody tr.active .title a.spotlink").click();
		}
	}
	if($("#overlay").is(':hidden')) {$(document).scrollTop($('table.spots tr.active').offset().top - 50)}
}

/*
 * Initializes the user preferences screen
 */
function initializeUserPreferencesScreen() {
	$("#edituserpreferencetabs").tabs();

	/* If the user preferences tab is loaded, make the filters sortable */
	$('#edituserpreferencetabs').bind('tabsload', function(event, ui) {
		bindSelectedSortableFilter();
	});	

	$('#nzbhandlingselect').change(function() {
	   $('#nzbhandling-fieldset-localdir, #nzbhandling-fieldset-runcommand, #nzbhandling-fieldset-sabnzbd, #nzbhandling-fieldset-nzbget').hide();
	   
	   var selOpt = $(this).find('option:selected').data('fields').split(' ');
	   $.each(selOpt, function(index) {
			$('#nzbhandling-fieldset-' + selOpt[index]).show();
		}); // each
	});	// change

	// roep de change handler aan zodat alles goed staat
	$('#nzbhandlingselect').change();

	/* Attach the hide/show functionalitity to the checkboxes who want it */
	attachEnablerBehaviour();

	$('#twitter_request_auth').click(function(){
		$('#twitter_result').html(loading);
		$.get(BaseURL+"?page=twitteroauth", function (data){window.open(data)}).complete(function() {
			$('#twitter_result').html('<t><b>Step 2</b?:<br />Please fill below your PIN-number that twitter has given you and validate this.</t><br /><input type="text" name="twitter_pin" id="twitter_pin">');
		});
		$(this).replaceWith('<input type="button" id="twitter_verify_pin" value="<t>Validate PIN</t>">');
	});
	$('#twitter_verify_pin').live('click', function(){
		var pin = $("#twitter_pin").val();
		$('#twitter_result').html(loading);
		$.get(BaseURL+"?page=twitteroauth", {'action':'verify', 'pin':pin}, function(data){ $('#twitter_result').html(data); });
	});
	$('#twitter_remove').click(function(){
		$('#twitter_result').html(loading);
		$.get(BaseURL+"?page=twitteroauth", {'action': 'remove'}, function(data){ $('#twitter_result').html(data); });
	});
} // initializeUserPreferencesScreen


/*
 * Some checkboxes behave as an 'hide/show' button for extra settings
 * we want to add the behaviour to those buttons
 */
function attachEnablerBehaviour() {
    $(".enabler").each(function(){
        if (!$(this).prop('checked'))
            $('#content_'+$(this).attr('id')).hide();
    });

	$(".enabler").click(function() {
		if ($(this).prop('checked'))
			$('#content_'+$(this).attr('id')).show();
		else
			$('#content_'+$(this).attr('id')).hide();
	});	
} // attachEnablerBehaviour


$(document).ready(function() {
// console.time("4th-ready");
	//ready
	var BaseURL = createBaseURL();
	var loading = '<img src="'+BaseURL+'templates/we1rdo/img/loading.gif" height="16" width="16" />';
	$("#usermanagementtabs").tabs();
	$("#editsettingstab").tabs();
	attachEnablerBehaviour();
	initializeUserPreferencesScreen();
// console.timeEnd("4th-ready");
});

// Regel positie en gedrag van sidebar (fixed / relative)
$().ready(function() {
// console.time("5th-ready");
	//ready
	$('#filterscroll').bind('change', function() {
		var scrolling = $(this).is(':checked');
		$.cookie('scrolling', scrolling, { path: '', expires: $COOKIE_EXPIRES, domain: '$COOKIE_HOST' });

		toggleScrolling(scrolling);
	});

	var scrolling = $.cookie("scrolling");
	toggleScrolling(scrolling);
// console.timeEnd("5th-ready");
});

function toggleScrolling(state) {
	if (state == true || state == 'true') {
		$('#filterscroll').attr({checked:'checked', title:'<t>Do not always make the sidebar visible</t>'});
		$('body').addClass('fixed');
	} else {
		$('#filterscroll').attr({title:'<t>Make sidebar always visible</t>'});
		$('body').removeClass('fixed');
	}
}

// Sidebar items in/uitklapbaar maken
function getSidebarState() {
	var data = new Array();
	$("div#filter > a.viewState").each(function(index) {
		var state = $(this).next().css("display");
		data.push({"count": index, "state": state});
	});	
	$.cookie("sidebarVisibility", JSON.stringify(data), { path: '', expires: $COOKIE_EXPIRES, domain: '$COOKIE_HOST' });
}

$(function(){
// console.time("6th-ready");
	//ready
	var data = jQuery.parseJSON($.cookie("sidebarVisibility"));
	if(data == null) {
		getSidebarState();
		var data = jQuery.parseJSON($.cookie("sidebarVisibility"));
	}
	$.each(data, function(i, value) {
		$("div#filter > a.viewState").eq(value.count).next().css("display", value.state);
		if(value.state != "none") {
			$("div#filter > a.viewState").eq(value.count).children("h4").children("span").removeClass("down").addClass("up");
		} else {
			$("div#filter > a.viewState").eq(value.count).children("h4").children("span").removeClass("up").addClass("down");
		}
	});
// console.timeEnd("6th-ready");
});

function toggleSidebarItem(id) {
	var hide = $(id).next();
	
	$(hide).toggle();
	$(id).children("h4").children("span").toggleClass("up down");

	getSidebarState()
}

// Geavanceerd zoeken op juiste moment zichtbaar / onzichtbaar maken
$(function(){
// console.time("7th-ready");
	//ready
	$("input.searchbox").focus(function(){
		if($("form#filterform .advancedSearch").is(":hidden")) {
			toggleSidebarPanel('.advancedSearch');
		}
	});

	$("input[name='search[unfiltered]']").attr('checked') ? $("div#tree").hide() : $("div#tree").show();
	$("input[name='search[unfiltered]']").click(function() {
		if($("div#tree").is(":visible")) {
			$("div#tree").hide();
			$("ul.clearCategories label").html('<t>Use categories</t>');
		} else {
			$("div#tree").show();
			$("ul.clearCategories label").html("<t>Don't use categories</t>");
		}
	});
// console.timeEnd("7th-ready");
});

// Pas sorteervolgorde aan voor datum
$(function(){
// console.time("8th-ready");
	//ready
	$("ul.sorting input").click(function() {
		if($(this).val() == 'stamp' || $(this).val() == 'commentcount' || $(this).val() == 'spotrating') {
			$("div.advancedSearch input[name=sortdir]").attr("value", "DESC");
		} else {
			$("div.advancedSearch input[name=sortdir]").attr("value", "ASC");
		}
	});
// console.timeEnd("8th-ready");
});

// sidebarPanel zichtbaar maken / verbergen
function toggleSidebarPanel(id) {
	if($(id).is(":visible")) {
		$(id).fadeOut();
	} else {
		if($(".sidebarPanel").is(":visible")) {
			$(".sidebarPanel").fadeOut();
			$(id).fadeIn();
		} else {
			$(id).fadeIn();
		}

		if(id == ".sabnzbdPanel") {
			updateSabPanel(1,5);
		}
	}
}

// SabNZBd knop; url laden via ajax (regel loading en succes status)
function downloadSabnzbd(id,url) {
	$(".sab_"+id).removeClass("succes").addClass("loading");
	
	/* This is a cross-domain request, so success will never be called */
	$.get(url, function(data, textStatus, jqXHR) {
		$(".sab_"+id).removeClass("loading").addClass("succes");
	});
	
	setTimeout( function() { $(".sab_"+id).removeClass("loading").addClass("succes"); }, 2000);
}

// Voorzie de span.newspots van link naar nieuwe spots binnen het filter
function gotoNew(url) {
	$("a").click(function(){ return false; });
	window.location = url+'&search[value][]=New:0';
}

// Voorzie de span.newspots van link naar spots binnen het filter
function gotoFilteredCategory(url) {
	$("a").click(function(){ return false; });
	window.location = url;
}

// Toevoegen en verwijderen van spots aan watchlist
function toggleWatchSpot(spot,action,spot_id) {
	// Add/remove watchspot
	$.get("?search[tree]=&search[unfiltered]=true&search[value][]=Watch:0&action="+action+"&messageid="+spot);

	// Switch buttons
	$('.watchremove_'+spot_id).toggle();
	$('.watchadd_'+spot_id).toggle();
}

// MultiNZB download knop
function multinzb() {
	var count = $('td.multinzb input[type="checkbox"]:checked').length;
	if(count == 0) {
		$('div.notifications').fadeOut();
	} else {
		$('div.notifications').fadeIn();
		if(count == 1) {
			$('span.count').html('<t>Download 1 spot</t>');
		} else {
			$('span.count').html('<t>Download %1 spots</t>'.replace('%1', count));
		}
	}
}

function uncheckMultiNZB() {
	$("table.spots input[type=checkbox]").attr("checked", false);
	$('div.notifications').fadeOut();
}

function checkMultiNZB() {
	if($("tr.active input[type=checkbox]").is(":checked")) {
		$("tr.active input[type=checkbox]").attr('checked', false);
		multinzb()
	} else {
		$("tr.active input[type=checkbox]").attr('checked', true);
		multinzb()
	}
}

function downloadMultiNZB() {
	var count = $('td.multinzb input[type="checkbox"]:checked').length;
	if(count > 0) {
		var url = '?page=getnzb';
		$('td.multinzb input[type=checkbox]:checked').each(function() {
			url += '&messageid%5B%5D='+$(this).val();
		});
		window.location = url;
		$("table.spots input[type=checkbox]").attr("checked", false);
		multinzb();
	}
}

// Toggle filter visibility
$(function(){
// console.time("9th-ready");
	//ready
	var data = jQuery.parseJSON($.cookie("filterVisiblity"));
	if(data != null) {
		$.each(data, function(i, value) {
			$("ul.subfilterlist").parent().eq(value.count).children("ul").css("display", value.state);
			if(value.state == "block") {
				$("ul.subfilterlist").parent().eq(value.count).children("a").children("span.toggle").css("background-position", "-77px -98px");
				$("ul.subfilterlist").parent().eq(value.count).children("a").children("span.toggle").attr("title", "<t>Collapse filter</t>");
			} else {
				$("ul.subfilterlist").parent().eq(value.count).children("a").children("span.toggle").css("background-position", "-90px -98px");
				$("ul.subfilterlist").parent().eq(value.count).children("a").children("span.toggle").attr("title", "<t>Expand filter</t>");

			}
		});
	}
// console.timeEnd("9th-ready");
});

function toggleFilter(id) {
	$(id).parent().click(function(){ return false; });

	var ul = $(id).parent().next();
	if($(ul).is(":visible")) {
		ul.hide();
		ul.prev().children("span.toggle").css("background-position", "-90px -98px");
		ul.prev().children("span.toggle").attr("title", "<t>Expand filter</t>");
	} else {
		ul.show();
		ul.prev().children("span.toggle").css("background-position", "-77px -98px");
		ul.prev().children("span.toggle").attr("title", "<t>Collapse filter</t>");
	}

	var data = new Array();
	$("ul.subfilterlist").each(function(index) {
		var state = $(this).css("display");
		data.push({"count": index, "state": state});
	});

	$.cookie("filterVisiblity", JSON.stringify(data), { path: '', expires: $COOKIE_EXPIRES, domain: '$COOKIE_HOST' });
}

// Maintenance buttons
$(function(){
	$("ul.maintenancebox a.retrievespots").click(function(){return false});
	$("ul.maintenancebox a.erasedownloads").click(function(){return false});
	$("ul.maintenancebox a.markasread").click(function(){return false});
});

function retrieveSpots() {
	var url = $("ul.maintenancebox a.retrievespots").attr("href");

	$("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
	$.get(url, function(data) {
		setTimeout( function() { $("li.info").html("<t>New spots retrieved</t>") }, 1000);
		setTimeout( function() { location.reload() }, 2000);
	});
}

function eraseDownloads() {
	var url = $("ul.maintenancebox a.erasedownloads").attr("href");

	$("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
	$.get(url, function(data) {
		setTimeout( function() { $("li.info").html("<t>Erased downloadhistory</t>") }, 1000);
		setTimeout( function() { location.reload() }, 2000);
	});
}

function markAsRead() {
	var url = $("ul.maintenancebox a.markasread").attr("href");

	$("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
	$.get(url, function(data) {
		setTimeout( function() { $("li.info").html("<t>Marked everything as read</t>") }, 1000);
		setTimeout( function() { location.reload() }, 2000);
	});
}

function ajaxSubmitFormWithCb(url, tbutton, cb) {
	var formdata = $(tbutton).attr("name") + "=" + $(tbutton).val();  
	formdata = $(tbutton.form).serialize() + "&" + formdata;
	
	// post de data
	$.ajax({
		type: "POST",
		url: url, // '?page=editfilter',
		dataType: "html",
		data: formdata,
		success: function(xml) {
			// alert(xml);
			cb(xml);
		} // success
	}); // ajax call om de form te submitten
} // ajaxSubmitFormWithCb

function requestNewUserApiKeyCbHandler(xml) {
	var result = $(xml).find('newapikey').text();

	$(".apikeyinputfield").val(result);
} // requestNewUserApiKeyCbHandler

function userLogout() {
	var url = createBaseURL() + '?page=logout';

    $.ajax({
        type: "GET",
        url: url,
		async: false,
        dataType: "xml",
        success: function(msg) {
			window.location.reload();
		}
	});
} // userLogout

// SabNZBd actions
function sabBaseURL() {
	var apikey = $("div.sabnzbdPanel input.apikey").val();
	var sabBaseURL = createBaseURL()+'?page=nzbhandlerapi&nzbhandlerapikey='+apikey;
	return sabBaseURL;
}

function sabActions(start,limit,action,slot) {
	var baseURL = sabBaseURL();
	
	if(action == 'pause') {
		var url = baseURL+'&action=pause&id'+slot;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'resume') {
		var url = baseURL+'&action=resume&id'+slot;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'speedlimit') {
		var limit = $("td.speedlimit input[name=speedLimit]").val();
		var url = baseURL+'&action=setspeedlimit&limit='+limit;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'up') {
		var url = baseURL+'&action=moveup&id='+slot;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'down') {
		var url = baseURL+'&action=movedown&id='+slot;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'delete') {
		var url = baseURL+'&action=delete&id='+slot;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else 	if(action == 'pausequeue') {
		var url = baseURL+'&action=pausequeue';
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'resumequeue') {
		var url = baseURL+'&action=resumequeue';
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	}
}

// Text toevoegen aan id (Smiley's)
function addText(text,element_id) {
	document.getElementById(element_id).value += text;
	document.getElementById(element_id).focus();
}

function drawGraph(currentSpeed,interval) {
	var numXLabels = 8;
	var numYLabels = 5;

	if($("table.sabGraphData tbody > tr").size() == 1) {
		// maak juiste hoeveelheid data rijen aan (afhankelijk van numXLabels
		$("table.sabGraphData").empty();
		i = 0;
		for (i = 0; i <= numXLabels; i++) {
			$("table.sabGraphData").append("<tr><td>0.00</td></tr>");
		}
	}
	// vul de juiste rijen met de juiste data
	if($("table.sabGraphData td:empty").size() != 0) {
		$("table.sabGraphData td:empty").first().html(currentSpeed);
	} else {
		$("table.sabGraphData td").first().remove();
		$("table.sabGraphData").append("<tr><td>"+currentSpeed+"</td></tr>");
	}

	var elem = $("canvas#graph");
	elem.width = $("canvas#graph").width();
	elem.height = $("canvas#graph").height();
	var offset = {
		"top": 6,
		"right": 6,
		"bottom": 18, 
		"left": 30
	};
	var graph = {
		"width": elem.width - offset.right - offset.left,
		"height": elem.height - offset.bottom - offset.top
	};
	var axisSpacing = {
		"x": 8,
		"y": 6
	};
	var intervalWidth = (elem.width - offset.left - offset.right) / numXLabels;

	var context = elem[0].getContext("2d");

	var speed = new Array();
	$("table.sabGraphData td").each(function(){
		speed.push({
			"count": $(this).index(),
			"value": $(this).text()
		});
	});
	var maxspeed = 0;
	var i = 0;
	for (i = 0; i <= numXLabels; i++) {
		if(Math.round(speed[i].value) >= Math.round(maxspeed)) {
			var maxspeed = speed[i].value;
		}
	};

	var speedAxis = new Array();
	var i = 0;
	for (i = 0; i <= numYLabels; i++) {
		speedAxis.push({
			"count": i, 
			"posx": offset.left - axisSpacing.x, 
			"posy": (elem.height-offset.bottom-offset.top) - (elem.height-offset.bottom-offset.top) * i/numYLabels + offset.top, 
			"value": Math.round(maxspeed * i/numYLabels)
		});
	};

	var interval = interval / 1000;
	var timeAxis = new Array();
	var i = 0;
	for (i = 0; i <= numXLabels; i++) {
		timeAxis.push({
			"count": i, 
			"posx": intervalWidth * i + offset.left, 
			"posy": elem.height - offset.bottom + axisSpacing.y, 
			"value": interval * i
		});
	};

	context.clearRect(0, 0, elem.width, elem.height);

	if(context) {
		// draw graph background
		context.shadowColor = "#777";
		context.shadowBlur = 0;
		context.fillStyle = "#eee";	
		context.fillRect(offset.left, offset.top, graph.width, graph.height);

		// draw axis
		context.fillStyle = "#000";	
		context.strokeStyle = "#fff";
		context.lineWidth = 2;

		context.shadowBlur = 3;
		context.beginPath();
		context.moveTo(offset.left, offset.top);
		context.lineTo(offset.left, elem.height - offset.bottom);
		context.lineTo(elem.width - offset.right, elem.height - offset.bottom);
		context.stroke();

		// draw axis labels
		context.shadowBlur = 0;
		$.each(speedAxis, function(i, value) {
			context.save();

			context.beginPath();
			context.moveTo(offset.left - 3, value.posy);
			context.lineTo(elem.width - offset.right, value.posy);
			context.stroke();

			if(maxspeed != 0 || value.count == 0) {
				context.shadowBlur = 0;
				context.textBaseline = "middle";
				context.textAlign = "end";
				context.fillText(value.value, value.posx, value.posy);
			}

			context.restore();
		});
		$.each(timeAxis, function(i, value) {
			context.save();

			context.beginPath();
			context.moveTo(value.posx, elem.height - offset.bottom);
			context.lineTo(value.posx, elem.height - offset.bottom + 3);
			context.stroke();

			context.textBaseline = "top";
			context.textAlign = "center";
			context.fillText(value.value, value.posx, value.posy);

			context.restore();
		});

		// draw graph
		context.fillStyle = "#219727";
		context.shadowBlur = 3;

		var speedData = new Array();
		var i = 0;
		for (i = 0; i <= numXLabels; i++) {
			speedData.push({
				"count": i, 
				"posx": offset.left + i*intervalWidth, 
				"posy": (graph.height + offset.top) - (speed[i].value / maxspeed) * graph.height
			});
		};

		context.beginPath();
		context.moveTo(offset.left, elem.height - offset.bottom);
		$.each(speedData, function(i, value) {
			context.lineTo(value.posx, value.posy);
		});
		context.lineTo(offset.left + graph.width, offset.top + graph.height);
		context.lineTo(offset.left, offset.top + graph.height);
		context.fill();
		context.stroke();
	}
}

function updateSabPanel(start,limit) {
	var baseURL = sabBaseURL();
	var url = baseURL+'&action=getstatus';

	$.getJSON(url, function(json){
		var queue = json.queue;

		if(queue.paused) {var state = "resume";} else {var state = "pause";}
		$("table.sabInfo td.state").html("<strong>"+queue.status+"</strong> (<a class='state' title='"+state+"'>"+state+"</a>)");
		$("table.sabInfo td.state a.state").click(function(){
			if(timeOut) {clearTimeout(timeOut)};
			sabActions(start,limit,state+"queue");
		});
		$("table.sabInfo td.diskspace").html("<strong title='<t>Free space (complete)</t>'>"+queue.freediskspace+"</strong> / <strong title='<t>Totale space (complete)</t>'>"+queue.totaldiskspace+"</strong> <t>GB</t>");
		$("table.sabInfo td.speed").html("<strong>"+(queue.bytepersec/1024).toFixed(2)+"</strong> <t>KB/s</t>");
		$("table.sabInfo td.speedlimit").html("<input type='text' name='speedLimit' value='"+(queue.speedlimit!=0?queue.speedlimit:"")+"'><label><t>KB/s</t></label>");
		$("td.speedlimit input[name=speedLimit]").focus(function(){
			$(this).addClass("hasFocus");
		});
		$("td.speedlimit input[name=speedLimit]").keyup(function(e) {
			if(e.keyCode == 13) {
				if(timeOut) {clearTimeout(timeOut)}; 
				sabActions(start,limit,'speedlimit');
			}
		});
		$("td.speedlimit input[name=speedLimit]").blur(function(){
			if(timeOut) {clearTimeout(timeOut)}; 
			sabActions(start,limit,'speedlimit');
		});
		
		var hours = Math.floor(queue.secondsremaining / 3600);
		var minutes = pad_zeros(Math.floor((queue.secondsremaining - (hours * 3600)) / 60),2);
		var seconds = pad_zeros((queue.secondsremaining % 60),2);
		
		$("table.sabInfo td.timeleft").html("<strong>"+hours+":"+minutes+":"+seconds+"</strong>");
		
		var eta = "-";
		if (queue.secondsremaining != 0)
		{
			var estimate = new Date();
			estimate.setSeconds(estimate.getSeconds() + queue.secondsremaining); 
			eta = estimate.toLocaleString();
		}
		
		$("table.sabInfo td.eta").html("<strong>"+eta+"</strong>");
		$("table.sabInfo td.mb").html("<strong>"+queue.mbremaining+"</strong> / <strong>"+queue.mbsize+"</strong> <t>MB</t>");

		// make sure we don't try to show more items than available in the queue
		while (start > queue.nrofdownloads)	{start -= limit;}
		// a start value lower than one is invalid
		if (start < 1) {start = 1;}
		
		var end = start+limit-1;
		
		$("table.sabQueue").empty();
		if(queue.nrofdownloads == 0) {
			$("table.sabQueue").html("<tr><td class='info'><t>No items in queue</t></td></tr>");
		} else {
			var index = 0;
			$.each(queue.slots, function(){
				var slot = this;
				
				index++;
				if ((index >= start) && (index <= end))
				{
					if(slot.percentage == 0) {var progress = " empty"} else {var progress = "";}
					
					$("table.sabQueue").append("<tr class='title "+index+"'><td><span class='move'><a class='up' title='<t>Up</t>'></a><a class='down' title='<t>Down</t>'></a></span><span class='delete'><a title='<t>Delete from queue</t>'></a></span><strong>"+index+".</strong><span class='title'>"+slot.filename+"</span></td></tr>");
					$("table.sabQueue").append("<tr class='progressBar'><td><div class='progressBar"+progress+"' title='"+slot.mbremaining+" / "+slot.mbsize+" MB' style='width:"+slot.percentage+"%'></div></td></tr>");
					
					$("table.sabQueue tr."+index+" a.up").click(function(){
						if(timeOut) {clearTimeout(timeOut)}; 
						sabActions(start,limit,'up', slot.id);
					});
					$("table.sabQueue tr."+index+" a.down").click(function(){
						if(timeOut) {clearTimeout(timeOut)}; 
						sabActions(start,limit,'down', slot.id);
					});
					$("table.sabQueue tr."+index+" span.delete a").click(function(){
						if(timeOut) {clearTimeout(timeOut)}; 
						if(start+1 > queue.nrofdownloads-1) {
							sabActions(start-(limit-start),limit-(limit-start),'delete', slot.id);
						} else {
							sabActions(start,limit,'delete', slot.id);
						}
					});
				}
			});
		}

		if(queue.nrofdownloads != 0 && queue.nrofdownloads > end) {
			$("table.sabQueue").append("<tr class='nav'><td><t>Show %1 till %2 from a total of %3 results</t></td></tr>".replace('%1', start).replace('%2', end).replace('%3', queue.nrofdownloads));
		} else if(queue.nrofdownloads != 0 && end > queue.nrofdownloads) {
			if(queue.nrofdownloads == 1) {
				$("table.sabQueue").append("<tr class='nav'><td><t>Show 1 result</t></td></tr>");
			} else {
				$("table.sabQueue").append("<tr class='nav'><td><t>Show %1 till %2 from a total of %3 results</t></td></tr>".replace('%1', start).replace('%2', queue.nrofdownloads).replace('%3', queue.nrofdownloads));
			}
		} else if(queue.nrofdownloads != 0 && end == queue.nrofdownloads) {
			$("table.sabQueue").append("<tr class='nav'><td><t>Show %1 till %2 from a total of %3 results/t></td></tr>".replace('%1', start).replace('%2', end).replace('%3', queue.nrofdownloads));
		}

		if(queue.nrofdownloads == 1) {
			$("table.sabQueue tr.title td span.move").hide();
		} else {
			if (start == 1){
				$("table.sabQueue tr.title td span.move").first().css('padding', '2px 4px 3px 0').children("a.up").hide();
			}
			if (end >= queue.nrofdownloads){
				$("table.sabQueue tr.title td span.move").last().css('padding', '2px 4px 3px 0').children("a.down").hide();
			}
		}

		if(start > 1) {
			$("table.sabQueue tr.nav td").prepend("<a class='prev' title='<t>Previous</t>'>&lt;&lt;</a> ");
		}
		if(queue.nrofdownloads > end) {
			$("table.sabQueue tr.nav td").append(" <a class='next' title='<t>Next</t>'>&gt;&gt;</a>");
		}

		$("table.sabQueue tr.nav a").click(function(){
			if(timeOut) {clearTimeout(timeOut)}
			if($(this).hasClass("prev")) {
				updateSabPanel(start-limit,limit);
			} else if($(this).hasClass("next")) {
				updateSabPanel(start+limit,limit);
			}
		});

		$("tr.title td span.title").mouseenter(function(){
			$(this).addClass("hover");
		}).mouseleave(function(){
			if($(this).hasClass("hover")) {
				if(timeOut) {clearTimeout(timeOut)}
				$(this).removeClass("hover");
				updateSabPanel(start,limit);
			}
		});

		var interval = 5000;
		drawGraph(queue.bytepersec/1024, interval);

		var timeOut = setTimeout(function(){
			if($("div.sabnzbdPanel").is(":visible") && !($("td.speedlimit input[name=speedLimit]").hasClass("hasFocus")) && !($("tr.title td span.title").hasClass("hover"))) {
				updateSabPanel(start,limit);
			}
		}, interval);
	});
}

/*
 * Haalt uit een bestaande filter URL de opgegeven filter via
 * string replacement
 */
function removeFilter(href, fieldname, operator, value) {
	href = unescape(href).replace(/\+/g, ' ');

	return href.replace('search[value][]=' + fieldname + ':' + operator + ':' + value, '');
} // removeFilter	

/*
 * Submit het zoek formulier
 */
function submitFilterBtn(searchform) {
	var valelems = searchform.elements['search[value][]'];
	
	// We zetten nu de filter om naar een moderner soort filter
	for (var i=0; i < searchform.elements['search[type]'].length; i++) {
		if (searchform.elements['search[type]'][i].checked) {
			var rad_val = searchform.elements['search[type]'][i].value;
		} // if
	} // for
	
	//
	// we voegen nu onze input veld als hidden waarde toe zodat we 
	// altijd op dezelfde manier de query parameters opbouwen.
	//
	// Als er geen textfilter waarde is, submitten we hem ook niet
	if (searchform.elements['search[text]'].value.trim().length > 0)  {
		$('<input>').attr({
			type: 'hidden',
			name: 'search[value][]',
			value: rad_val + ':=:' + searchform.elements['search[text]'].value
		}).appendTo('form#filterform');
	} // if
	
	// en vewijder de oude manier
	$('form#filterform').find('input[name=search\\[text\\]]').remove();
	$('form#filterform').find('input[name=search\\[type\\]]').remove();

	// nu selecteren we alle huidige search values, als de include filters
	// knop is ingedrukt dan doen we er niks mee, anders filteren we die
	if ($('#searchfilter-includeprevfilter-toggle').val() != 'true') {
		$('form#filterform [data-currentfilter="true"]').each(function(index, value) { 
			$(value).remove();
		});	
	} // if
	
	// eventueel lege values die gesubmit worden door de age dropdown
	// ook filteren
	$('form#filterform').find('select[name=search\\[value\\]\\[\\]]').filter(':input[value=""]').remove(); 
	
	// de checkbox die aangeeft of we willen filteren of niet moeten we ook niet submitten
	$('#searchfilter-includeprevfilter-toggle').remove();
	
	// als de slider niet gewijzigd is van de default waardes, dan submitten
	// we heel de slider niet
	if ($('#min-filesize').val() == 'filesize:>:0') { 
		$('form#filterform').find('#min-filesize').remove();
	} // if
	if ($('#max-filesize').val() == 'filesize:<:375809638400') { 
		$('form#filterform').find('#max-filesize').remove();
	} // if
	
	// we use 21 reports as a magic value to say 'disable reporting'
	if ($('#max-reportcount').val() == 'reportcount:<=:21') { 
		$('form#filterform').find('#max-reportcount').remove();
	} // if

	return true;
} // submitFilterBtn
	
function format_size(size) {
	var sizes = ['<t>B</t>', '<t>KB</t>', '<t>MB</t>', '<t>GB</t>', '<t>TB</t>', '<t>PB</t>', '<t>EB</t>', '<t>ZB</t>', '<t>YB</t>'];
	var i = 0;
	while(size >= 1024) {
		size /= 1024;
		++i;
	}
	return size.toFixed(1) + ' ' + sizes[i];
}

function pad_zeros(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}


function bindSelectedSortableFilter() {
	/* Koppel de nestedSortable aan de sortablefilterlist */
	var $sortablefilterlist = $('#sortablefilterlist');
	if ($sortablefilterlist) {
		$sortablefilterlist.nestedSortable({
			opacity: .6,
			tabSize: 15,
			forcePlaceholderSize: true,
			forceHelperSize: true,
			maxLevels: 4,
			helper:	'clone',
			items: 'li',
			tabSize: 25,
			listType: 'ul',
			handle: 'div',
			placeholder: 'placeholder',
			revert: 250,
			tolerance: 'pointer',
			update: function() {
				var serialized = $sortablefilterlist.nestedSortable('serialize');
				var formdata = 'editfilterform[xsrfid]=' + editfilterformcsrfcookie + '&editfilterform[submitreorder]=true&' + serialized;
				
				// post de data
				$.ajax({
					type: "POST",
					url: '?page=editfilter',
					dataType: "html",
					data: formdata,
					success: function(xml) {
						// alert(xml);
					} // success
				}); // ajax call om de form te submitten
			}
		});
	} // if
} // bindSelectedSortableFilter

/*
 * Function to load the ?page=catsjson data into an
 * selectbox given by the system
 */
function loadCategoryIntoSelectbox(selectId, titleElm, data, async, doClear) {
	var $selectbox = $("#" + selectId);
	if (titleElm) {
		var $titleElm = $("#" + titleElm);
	} else {
		var $titleElm = null;
	} // else
	if ($selectbox.data('fromurl') == $.toJSON(data)) {
		return ;
	} // if
	
    $.ajax({
        type: "GET",
        url: "?page=catsjson",
        data: data,
		async: async,
        dataType: "json",
        success: function(msg) {
			$selectbox.data('fromurl', $.toJSON(data));
			var htmlData = '';
			
			if ($titleElm) {
				$titleElm.text(msg.title);
			} else {
				htmlData += '<optgroup label="' + msg.title + '">';
			} // else

			if (doClear) {
				$selectbox.empty();
			} // if
            $.each(msg.items, function(index, item) {
				htmlData += '<option value="' + index + '">' + item + '</option>';
            });
			if (!$titleElm) {
				htmlData += '</optgroup>';
			} // if

			$selectbox.append(htmlData);
            $selectbox[0].selected = 0;
			
			if ($selectbox[0].options.length < 2) {
				if ($titleElm) { $titleElm.hide(); }
				$selectbox.hide();
			} else {
				if ($titleElm) { $titleElm.show(); }
				$selectbox.show();
			} // else
        },
        error: function() {
            alert("Failed to load data");
        }
    });
}  // loadCategoryIntoSelectbox

function categorySelectChanged() {
	var itm = $("#spotcategoryselectbox")[0];

	loadCategoryIntoSelectbox('subcatzselectbox', 'txtsubcatz', {category: itm.value, subcatz: 0, rendertype: 'subcatz'}, false, true);
	var subcatzValue = $("#subcatzselectbox")[0].value;
	
	loadCategoryIntoSelectbox('subcataselectbox', 'txtsubcata', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcata'}, true, true);
	loadCategoryIntoSelectbox('subcatbselectbox', 'txtsubcatb', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcatb'}, true, true);
	loadCategoryIntoSelectbox('subcatcselectbox', 'txtsubcatc', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcatc'}, true, true);
	loadCategoryIntoSelectbox('subcatdselectbox', 'txtsubcatd', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcatd'}, true, true);
} // categorySelectChanged
 
function downloadMappingTypeChanged() {
	var itm = $("#spotcategoryselectbox")[0];
	var $selectbox = $('#subcataselectbox');

	var itmValue = itm.value.split('_')[0].substring(3);
	var subcatzValue = itm.value.split('_')[1];

 	loadCategoryIntoSelectbox('subcataselectbox', null, {category: itmValue, subcatz: subcatzValue, rendertype: 'subcata'}, false, true);
	loadCategoryIntoSelectbox('subcataselectbox', null, {category: itmValue, subcatz: subcatzValue, rendertype: 'subcatb'}, false, false);
	loadCategoryIntoSelectbox('subcataselectbox', null, {category: itmValue, subcatz: subcatzValue, rendertype: 'subcatc'}, false, false);
	loadCategoryIntoSelectbox('subcataselectbox', null, {category: itmValue, subcatz: subcatzValue, rendertype: 'subcatd'}, false, false);
} // downloadMappingTypeChanged

function addSpotFilter(xsrf, filterType, filterValue, filterName, addElementClass) {
	var formData = 'editfilterform[xsrfid]=' + escape(xsrf);
	formData += '&editfilterform[filterid]=9999';
	formData += '&editfilterform[tree]=';
	formData += '&editfilterform[valuelist]=' + escape(filterType) + ":=:" + escape(filterValue);
	formData += '&editfilterform[sorton]=date';
	formData += '&editfilterform[sortorder]=desc';
	formData += '&editfilterform[title]=' + escape(filterName);
	formData += '&editfilterform[icon]=application';
	formData += '&editfilterform[submitaddfilter]=add';
			
	// post de data
	$.ajax({
		type: "POST",
		url: '?page=editfilter',
		dataType: "xml",
		data: formData,
		success: function(xml) {
			var result = $(xml).find('result').text();
			
			if (result == 'success') {
				$("." + addElementClass).remove();
			} // if
		} // success()
	}); // ajax call om de form te submitten
			
} // addSpotFilter

function applyTipTip(){
	var categories = $(this).data('cats');
	if(!categories) return;
	var $dl = $("<ul/>")
	var list = $.map(categories, function(value, key){
		if(value) {
			return $("<li/>").append($("<strong/>").text(key + ": ")).append(value);
		}
	});

	$dl.append.apply($dl, list);
	$(this).attr("title", "");
	$(this).tipTip({defaultPosition: 'bottom', maxWidth: 'auto', content: $dl});
}
