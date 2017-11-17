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
						$(document).scrollTop(currentSpot.offset().top - 50);
					} // if
				} // if
			} else if ($.address.value() != '/') openSpot($('table.spots tr.active a.spotlink'), $.address.value());
		});

function initSpotwebJs(BetweenText, AndText) {
	//ready
	$("a.spotlink").click(function(e) { e.preventDefault(); });
	if(navigator.userAgent.toLowerCase().indexOf('chrome')>-1)$('a.spotlink').mouseup(function(e){if(e.which==2||(e.metaKey||e.ctrlKey)&&e.which==1){$(this).attr('rel','address:');}});
	$("a[href^='http']").attr('target','_blank');

    /*
     * Attach the "TipTip" tooltip behaviour for each SpotLink
     */
    $('.showTipTip a.spotlink').each(applyTipTip);

    attachInfiniteScroll();
    attachKeyBindings();
    attachSidebarBehaviour();
    attachSidebarVisibility();
    attachAdvancedSearchBehaviour(BetweenText, AndText);
    attachFilterVisibility();
    attachMaintenanceButtonsBehaviour();

    var BaseURL = createBaseURL();
    var loading = '<img src="'+BaseURL+'templates/we1rdo/img/loading.gif" height="16" width="16" />';
    attachEnablerBehaviour();
} // initSpotwebJs

/**
 * Creates a base url, full path to this Spotweb
 * installation.
 *
 * @returns {string}
 */
function createBaseURL() {
	var baseURL = window.location.protocol + '//' + window.location.hostname + window.location.pathname;
	if (window.location.port != '') {
		var baseURL = window.location.protocol + '//' + window.location.hostname+':'+window.location.port+window.location.pathname;
	}
	return baseURL;
} // createBaseURL

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
			//closeDetails(scrollLocation);
            parent.history.back();
			return false;
		}

		$("a.closeDetails").click(function(){ 
			$.address.value("");
			if ($('table.spots tr.active').offset().top > $(window).height())scrollLocation = $('table.spots tr.active').offset().top - 50;
			closeDetails(scrollLocation); 
		});

		$("a[href^='http']").attr('target','_blank');
		$(window).bind("resize", detectScrollbar);

		postCommentsForm();
		postBlacklistForm();
		
		if (spotweb_retrieve_commentsperpage > 0) {
			loadComments(messageid,spotweb_retrieve_commentsperpage,'0');
		} // if
		loadSpotImage();
	});

    return false;
}

/**
 * Refreshes a tabs content when given a tabname,
 * is primarily used a callback for ShowDialog(), so
 * when the dialog is closed, the tab contents is
 * refreshed.
 *
 * @returns void
 */
function refreshTab(tabName) {    
	var tab = $('#' + tabName);
	
	var selected = tab.tabs('option', 'selected');
	tab.tabs('load', selected);
} // refreshTab


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
function attachInfiniteScroll() {
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
					$("tbody#spots").append($($("div#overlay tbody#spots").html()).show());
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
} // attachInfiniteScroll

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

		html = $(html);
		$("a[href^='http']", html).attr('target','_blank');

		$("#commentslist").append(html);
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

    return false;
}

function postReportForm() {
    new SpotPosting().postReport($('form.postreportform')[0], postReportUiStart, postReportUiDone);
    return false;
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
		dataType: "json",
		data: formData,
		success: function(data) {
			var result = $data.results;
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
					.text(data.error.join('\n'));
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
			dataType: "json",
			data: formdata,
			success: function(data) {
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
		new SpotPosting().postComment(this,postCommentUiStart,postCommentUiDone);
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
		});
		$('a.postimage').attr('title', '<t>Click on this image to show real size (i)</t>');
		detectScrollbar();
	})
	.each(function() {
		// From the jQuery comments: http://api.jquery.com/load-event/
		if (this.complete || (jQuery.browser.msie && parseInt(jQuery.browser.version) == 6)) {
			$(this).trigger("load");
		}
	});

    return false;
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
function attachKeyBindings() {
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
	$document.bind('keydown', 'm', function(){downloadMultiNZB(spotweb_nzbhandler_type)});
	$document.bind('keydown', 'c', checkMultiNZB);
// console.timeEnd("3rd-ready");
} // attachKeyBindings

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
 * Initializes the user management page
 */
function initializeUserManagementPage() {
    $("#usermanagementtabs").tabs();
} // initializeUserManagementPage

/*
 Initializes the settings pages
 */
function initializeSettingsPage() {
    initDatePicker();
    $("#editsettingstab").tabs();
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
	   $('#nzbhandling-fieldset-localdir, #nzbhandling-fieldset-runcommand, #nzbhandling-fieldset-sabnzbd, #nzbhandling-fieldset-nzbget, #nzbhandling-fieldset-nzbvortex').hide();
	   
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
			$('#twitter_result').html('<t><b>Step 2</b?:<br />Please fill below your PIN-number that twitter has given you and validate this.</t><br /><input type="text" name="twitter_pin" id="twitter_pin" />');
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


/**
 * Some checkboxes behave as an 'hide/show' button for extra settings
 * we want to add the behaviour to those buttons
 *
 * @returns void
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


// Regel positie en gedrag van sidebar (fixed / relative)
function attachSidebarBehaviour() {
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
} // attachSidebarBehaviour

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

function attachSidebarVisibility() {
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
} // attachSidebarVisibility

function toggleSidebarItem(id) {
	var hide = $(id).next();
	
	$(hide).toggle();
	$(id).children("h4").children("span").toggleClass("up down");

	getSidebarState()
}

// Geavanceerd zoeken op juiste moment zichtbaar / onzichtbaar maken
function attachAdvancedSearchBehaviour(BetweenText, AndText) {
// console.time("7th-ready");
	//ready
	$("input.searchbox").focus(function(){
		if($("form#filterform .advancedSearch").is(":hidden")) {

            toggleSidebarPanel('.advancedSearch');

            if (!$('div#tree').data('dynatree')) {
                attachDateSortBehaviour();
                initSliders(BetweenText, AndText);
                initializeCategoryTree();

                $("input[name='search[unfiltered]']").prop('checked') ? $("div#tree").hide() : $("div#tree").show();
            } // if
		}

        /*
         * Make sure that an 'enter' actually submits the searchform
         */
        $("#filterform input").keypress(function (e) {
            if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
                $('form#filterform').find('input[type=submit].default').click();
                return false;
            } else {
                return true;
            }
        });
	});

	$("input[name='search[unfiltered]']").click(function() {
		if($("input[name='search[unfiltered]']").prop('checked')) {
			$("div#tree").hide();
			$("ul.clearCategories label").html('<t>Use categories</t>');
		} else {
			$("div#tree").show();
			$("ul.clearCategories label").html("<t>Don't use categories</t>");
		}

        return true;
	});
// console.timeEnd("7th-ready");
} // attachAdvancedSearchBehaviour()

// Pas sorteervolgorde aan voor datum
function attachDateSortBehaviour() {
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
} // attachDateSortBehaviour

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
function downloadSabnzbd(id,url, dltype) {
	$(".sab_"+id).removeClass("succes").addClass("loading");
	
	/*
	 * Get the URL, do not rely on the result handler always
	 * being called because in some cases (eg: client-sabnzbd)
	 * it is a cross-domain request and will never be called
	 */
    // post de data
    $.ajax({
        type: "GET",
        url: url,
        dataType: "json",
        success: function(data) {
            if (data.result == "success") {
                $(".sab_"+id).removeClass("loading").addClass("succes");
            } else {
                $(".sab_"+id).removeClass("loading").addClass("failure");
            } // else
        } // success
    }); // ajax call om de form te submitten

    /*
     * with client-sabnzbd we ask the browser to download a specific url,
     * so we cannot keep track if this succeeds or not. Therefore,
     * we just always set it to green
     */
	if (dltype == 'client-sabnzbd') {
    	setTimeout( function() { $(".sab_"+id).removeClass("loading").addClass("succes"); }, 1000);
    } // if
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

function checkMultiNZB() {
	if($("tr.active td.multinzb input[type=checkbox]").is(":checked")) {
		$("tr.active td.multinzb input[type=checkbox]").attr('checked', false);
		multinzb()
	} else {
		$("tr.active td.multinzb input[type=checkbox]").attr('checked', true);
		multinzb()
	}
}

function toggleAllMultiNzb() {
    var val = $('th.multinzb input[type=checkbox]').attr('checked');
    if (typeof val == "undefined") {
        val = false;
    } // if

    $('td.multinzb input[type=checkbox]').each(function() {
        $(this).attr('checked', val);
    });
    multinzb();
} // toggleAllMultinzb


function downloadMultiNZB(dltype) {
	var count = $('td.multinzb input[type="checkbox"]:checked').length;
	if(count > 0) {
        /*
         * with client-sabnzbd we override to display as we cannot send
         * multiple NZB files to the server just yet
         */
        if (dltype == 'client-sabnzbd' || dltype == 'disable') {
            dltype = 'display';
        } // if

		var url = '?page=getnzb&action=' + dltype;
		$('td.multinzb input[type=checkbox]:checked').each(function() {
			url += '&messageid%5B%5D='+$(this).val();
		});

        /*
         * Add loading to all NZB's being downloaded
         */
        $(".sabnzbd-button").removeClass("succes").addClass("loading");

        if (dltype != 'display') {
            $.ajax({
                type: "GET",
                url: url,
                dataType: "json",
                success: function(data) {
                    if (data.result == "success") {
                        $(".sabnzbd-button").removeClass("loading").addClass("succes");
                    } else {
                        $(".sabnzbd-button").removeClass("loading").addClass("failure");
                    } // else

                    $("table.spots input[type=checkbox]").attr("checked", false);
                    multinzb();
                } // success
            }); // ajax call om de form te submitten
        } else {
            window.location.href = url;

            $(".sabnzbd-button").removeClass("loading").addClass("succes");
            $("table.spots input[type=checkbox]").attr("checked", false);
            multinzb();
        }


	}
}

// Toggle filter visibility
function attachFilterVisibility() {
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
} // attachFilterVisibility

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
function attachMaintenanceButtonsBehaviour() {
	$("ul.maintenancebox a.retrievespots").click(function(){return false});
	$("ul.maintenancebox a.erasedownloads").click(function(){return false});
	$("ul.maintenancebox a.markasread").click(function(){return false});
} // attachMaintenanceButtionsBehaviour

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

/**
 * Submits a form using AJAX and call a user-defined callback
 * when the post was succesful
 *
 * @param url
 * @param tbutton
 * @param cb
 */
function ajaxSubmitFormWithCb(url, tbutton, cb) {
	var formdata = $(tbutton).attr("name") + "=" + $(tbutton).val();  
	formdata = $(tbutton.form).serialize() + "&" + formdata;
	
	// post de data
	$.ajax({
		type: "POST",
		url: url, 
		dataType: "json",
		data: formdata,
		success: function(data) {
			// alert(xml);
			cb(data);
		} // success
	}); // ajax call om de form te submitten
} // ajaxSubmitFormWithCb


function requestNewUserApiKeyCbHandler(data) {
	$(".apikeyinputfield").val(data.data.apikey);
} // requestNewUserApiKeyCbHandler

function userLogout() {
	var url = createBaseURL() + '?page=logout';

    $.ajax({
        type: "GET",
        url: url,
		async: false,
        dataType: "json",
        success: function(msg) {
			window.location.reload();
		}
	});
} // userLogout


// Text toevoegen aan id (Smiley's)
function addText(text,element_id) {
    document.getElementById(element_id).value += text;
    document.getElementById(element_id).focus();
}

/*
 * Haalt uit een bestaande filter URL de opgegeven filter via
 * string replacement
 */
function removeFilter(href, fieldname, operator, booloper, value) {
	href = unescape(href).replace(/\+/g, ' ');

	return href.replace('search[value][]=' + fieldname + ':' + operator + ':' + booloper + ':' + value, '');
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
			value: rad_val + ':=:DEF:' + searchform.elements['search[text]'].value
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
	if ($('#min-filesize').val() == 'filesize:>:DEF:0') {
		$('form#filterform').find('#min-filesize').remove();
	} // if
	if ($('#max-filesize').val() == 'filesize:<:DEF:274877906944') {
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
					success: function(data) {
						// alert(data);
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

/*
 * Function to load the ?page=catsjson data into an
 * selectbox given by the system
 */
function spotEditLoadCategoryIntoSelectbox(selectId, titleElm, data, async, doClear, subcategory) {
	if (typeof subcategory == "undefined") { subcategory = new Array(); }

	var $selectbox = $("#" + selectId);
	if (titleElm) {
		var $titleElm = $("#" + titleElm);
	} else {
		var $titleElm = null;
	} // else
	if ($selectbox.data('fromurl') == $.toJSON(data)) {
		return ;
	} // if

	$.ajax(
		{type: "GET",
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
				var selected = "";
				for (var i=0; i < subcategory.length; i++) {
					if (subcategory[i] == index) {
						selected = " selected=\"selected\"";
						break;
					} // if
				} // for

				htmlData += '<option' + selected + ' value="' + index + '">' + item + '</option>';
			}); // $.each

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
		},error: function() {
			alert("Failed to load data");
		}
	}); // $.ajax
} // spotEditLoadCategoryIntoSelectbox

function spotEditCategorySelectChanged(category, subcata, subcatb, subcatc, subcatd, subcatz) {
	var itm = $("#spotcategoryselectbox")[0];

	if (typeof subcatz != "undefined") {
		// remove leading z and trailing |
		subcatz = subcatz.slice(1,-1);
	}

	spotEditLoadCategoryIntoSelectbox('subcatzselectbox', 'txtsubcatz', {category: itm.value, subcatz: subcatz, rendertype: 'subcatz'}, false, true, new Array(subcatz));
	var subcatzValue = $("#subcatzselectbox")[0].value;

	// update the select boxes with the correct selections
	var subcataValue = $("#subcataselectbox")[0].value;
	subcataArray = makeSubcategoryArray(category, subcatzValue, subcata);
	// don't select items in category b and c when switching to or from type Book.
	if ((category == 0) && (subcatz == "2" || subcatzValue == 2)) { 
		subcatbArray = makeSubcategoryArray(category, subcatz, subcatb);
		subcatcArray = makeSubcategoryArray(category, subcatz, subcatc);
	}
	else
	{
		subcatbArray = makeSubcategoryArray(category, subcatzValue, subcatb);
		subcatcArray = makeSubcategoryArray(category, subcatzValue, subcatc);
	}
	subcatdArray = makeSubcategoryArray(category, subcatzValue, subcatd);

	spotEditLoadCategoryIntoSelectbox('subcataselectbox', 'txtsubcata', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcata_old'}, true, true, subcataArray);
	spotEditLoadCategoryIntoSelectbox('subcatbselectbox', 'txtsubcatb', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcatb_old'}, true, true, subcatbArray);
	spotEditLoadCategoryIntoSelectbox('subcatcselectbox', 'txtsubcatc', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcatc_old'}, true, true, subcatcArray);
	spotEditLoadCategoryIntoSelectbox('subcatdselectbox', 'txtsubcatd', {category: itm.value, subcatz: subcatzValue, rendertype: 'subcatd_old'}, true, true, subcatdArray);
} // spotEditCategorySelectChanged

function makeSubcategoryArray(category, subcatz, subcat)
{
	if (typeof subcat == "undefined") { return new Array(); }

	var subcatarray = subcat.split("|");
	for (var i=0; i < subcatarray.length-1; i++) {
		subcatarray[i] = 'cat' + category + "_z" + subcatz + "_" + subcatarray[i];
	}
	return subcatarray;
} // makeSubcategoryArray

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
	formData += '&editfilterform[valuelist]=' + escape(filterType) + ":=:DEF:" + escape(filterValue);
	formData += '&editfilterform[sorton]=date';
	formData += '&editfilterform[sortorder]=desc';
	formData += '&editfilterform[title]=' + escape(filterName);
	formData += '&editfilterform[icon]=application';
	formData += '&editfilterform[submitaddfilter]=add';
			
	// post de data
	$.ajax({
		type: "POST",
		url: '?page=editfilter',
		dataType: "json",
		data: formData,
		success: function(data) {
			if (data.result == 'success') {
				$("." + addElementClass).remove();
			} // if
		} // success()
	}); // ajax call om de form te submitten
			
} // addSpotFilter

function applyTipTip(){
var categories = $(this).data('cats');
        if(!categories) return;
        var dl = "<ul>";
        var list = $.map(categories, function(value, key){
                if(value) {
                        if(key=='image'){//if image is used, don't add text or :
                                return '<li>' + value + '</li>';
                        }
                        else{
                                return '<li><strong/>' + key + ': ' + value;
                        }
               } else {
            return '';
        } // else
       	});

	dl = dl + list + '</ul>';
        $(this).attr("title", "");
        $(this).tipTip({defaultPosition: 'bottom', delay: 800, maxWidth: 'auto', content: dl});
}

function findNearest(possibleValues, realValues, includeLeft, includeRight, value) {
    var nearest = null;
    var realValue = null;
    var diff = null;
    for (var i = 0; i < possibleValues.length; i++) {
        if ((includeLeft && possibleValues[i] <= value) || (includeRight && possibleValues[i] >= value)) {
            var newDiff = Math.abs(value - possibleValues[i]);
            if (diff == null || newDiff < diff) {
                nearest = possibleValues[i];
                realValue = realValues[i];
                diff = newDiff;
            }
        }
    }
    return [nearest, realValue];
}

function initSliders(BetweenText, AndText) {
    var _1MB = 1024 * 1024;
    var _1GB = 1024 * 1024 * 1024;

    var realValues     = [0, _1MB, _1MB * 10, _1MB * 50, _1MB * 512, _1GB, _1GB * 4, _1GB * 6, _1GB * 8, _1GB * 12, _1GB * 16, _1GB * 24, _1GB * 32, _1GB * 48, _1GB * 64, _1GB * 96, _1GB * 128, _1GB * 256];
    var possibleValues = [0,    1,         2,         3,          6,   10,       11,       12,       13,        14,        15,        16,        17,        18,        19,        20,         25,         30];
    if (realValues.length != possibleValues.length) {
        alert('error in code: possibleValues and realValues array length do not match up');
    } // if

    var max = possibleValues[possibleValues.length - 1];

    /*
     * convert the current sliderMinFileSize and sliderMaxFileSize
     * to values appropriate in the system
     */
    var convertedSliderMinFileSize = findNearest(realValues, possibleValues, true, false, sliderMinFileSize)[1];
    var convertedSliderMaxFileSize = findNearest(realValues, possibleValues, true, false, sliderMaxFileSize)[1];

    var slider = $( "#slider-filesize" ).slider({
        range: true,
        step: 1,
        min: 0,
        max: max,
        values: [ convertedSliderMinFileSize, convertedSliderMaxFileSize ],
        slide: function( event, ui ) {
            /*
             * code copied from: http://stackoverflow.com/questions/967372/jquery-slider-how-to-make-step-size-change
             */
            var includeLeft = event.keyCode != $.ui.keyCode.RIGHT;
            var includeRight = event.keyCode != $.ui.keyCode.LEFT;
            var fixedValues = findNearest(possibleValues, realValues, includeLeft, includeRight, ui.value);

//            console.log('findNearest(' + ui.value + ') => ' + fixedValues[0] + ' (' + fixedValues[1] + ')');

            if (ui.value == ui.values[0]) {
                slider.slider('values', 0, fixedValues[0]);
                $( "#min-filesize" ).val( "filesize:>:DEF:" + fixedValues[1]);
                sliderMinFileSize = fixedValues[1];
            } else {
                slider.slider('values', 1, fixedValues[0]);
                $( "#max-filesize" ).val( "filesize:<:DEF:" + fixedValues[1]);
                sliderMaxFileSize = fixedValues[1];
            } // else

            $( "#human-filesize" ).text( BetweenText + format_size( parseInt($( "#min-filesize").val().substring("filesize:>:DEF:".length)) ) + AndText + format_size( parseInt($( "#max-filesize").val().substring("filesize:>:DEF:".length) )) );

            return false;
            }
        });

    $( "#slider-reportcount" ).slider({
        range: 'max',
        min: 0,
        max: 21,
        step: 1,
        values: [ sliderMaxReportCount ],
        slide: function( event, ui ) {
            $( "#max-reportcount" ).val( "reportcount:<=:" + ui.values[0]);

            if (ui.values[0] == 21) {
            /* In de submit handler wordt 21 gefiltered */
            $( "#human-reportcount" ).text( "<t>Do not filter on # reports</t>" );
            } else {
            $( "#human-reportcount" ).text( "<t>Maximum %1 reports</t>".replace("%1", ui.values[0]) );
            } // if
        }
        });

    /* Filesizes */
    var nearestMin = findNearest(possibleValues, realValues, true, false, convertedSliderMinFileSize);
    var nearestMax = findNearest(possibleValues, realValues, false, true, convertedSliderMaxFileSize);
    $( "#min-filesize" ).val( "filesize:>:DEF:" + nearestMin[1]);
    $( "#max-filesize" ).val( "filesize:<:DEF:" + nearestMax[1]);
    $( "#human-filesize" ).text(BetweenText + format_size( parseInt($( "#min-filesize").val().substring("filesize:>:DEF:".length)) ) + AndText + format_size( parseInt($( "#max-filesize").val().substring("filesize:>:DEF:".length) )) );

    /* Report counts */
    var reportSlideValue = $( "#slider-reportcount" ).slider("values", 0);
    $( "#max-reportcount" ).val( "reportcount:<=:" + reportSlideValue);
            if (reportSlideValue == 21) {
                $( "#human-reportcount" ).text("<t>Do not filter on # reports</t>");
                } else {
                $( "#human-reportcount" ).text( "<t>Maximum %1 reports</t>".replace("%1", reportSlideValue));
                } // if
} // initSliders

// used by editsettings form
function initDatePicker() {
    if (typeof retrieveNewerThanDate != 'undefined') {
        $( "#datepicker" ).datepicker({ altField: "#retrieve_newer_than",
            dateFormat: "dd-mm-yy",
            defaultDate: retrieveNewerThanDate,
            dayNamesMin: ['<t>Su</t>', '<t>Mo</t>', '<t>Tu</t>', '<t>We</t>', '<t>Th</t>', '<t>Fr</t>', '<t>Sa</t>' ],
            monthNamesShort: ['<t>Jan</t>', '<t>Feb</t>', '<t>Mar</t>', '<t>Apr</t>', '<t>May</t>', '<t>Jun</t>', '<t>Jul</t>', '<t>Aug</t>', '<t>Sep</t>', '<t>Oct</t>', '<t>Nov</t>', '<t>Dec</t>'],
            prevText: '<t>Previous</t>',
            nextText: '<t>Next</t>',
            numberOfMonths: 3,
            stepMonths: 3,
            minDate: new Date(2009, 10, 1),
            maxDate: "today" });
    } // retrieveNewerThanDate
} // initDatePicker()
