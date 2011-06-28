$.address.init(function() {
	$('.spotlink').address();
}).externalChange( function( event ) { 
  if($.address.value()=="/"){
   $("a.closeDetails").click();
   if ($('table.spots tr.active').offset().top>$(window).height())$(document).scrollTop($('table.spots tr.active').offset().top - 50);
  } else openSpot($("table.spots tr.active a.spotlink"),$.address.value());
});

$(function(){
	$("a.spotlink").click(function(e) { e.preventDefault(); });

	$("a[href^='http']").attr('target','_blank');
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
	if(($("div#details").outerHeight() + $("div#details").offset().top <= $(window).height())) {
		$("div#details").addClass("noscroll");
	} else {
		$("div#details").removeClass("noscroll");
	}
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

	var messageid = url.split("=")[2];

	$("#overlay").addClass('loading');
	$("#overlay").empty().show();

	var scrollLocation = $(document).scrollTop();
	$("#overlay").load(url+' #details', function() {
		$("div.container").removeClass("visible").addClass("hidden");
		$("#overlay").removeClass('loading notrans');
		$("body").addClass("spotinfo");

		if($("#overlay").children().size() == 0) {
			alert("Er is een fout opgetreden bij het laden van de pagina, u wordt automatisch teruggestuurd naar het overzicht...");
			closeDetails(scrollLocation);
		}

		$("a.closeDetails").click(function(){ 
			closeDetails(scrollLocation); 
		});

		$("a[href^='http']").attr('target','_blank');
		$(window).bind("resize", detectScrollbar);

		postCommentsForm();
		loadComments(messageid,'5','0');
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
 * Helper functie om een dialog te openen, er moeten een aantal paramters 
 * meegegeven worden:
 *
 * divid = id van een div welke geburikt wordt om om te vormen tot een dialog.
 * title = title van de dialogbox
 * url = url van de content waar deze dialog geladen zou moeten worden
 * formname = naam van het formulier, dit is nodig om de submit buttons te attachen
 * autoClose = moet hte formulier automatisch sluiten als het resultaat 'success' was ?
 * closeCb = functie welke aangeroepen moet worden als de dialog gesloten wordt
 */
function openDialog(divid, title, url, formname, autoClose, closeCb) {
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
				
				if ((result == 'success') && (autoClose)) {
					$dialdiv.empty();
					$dialdiv.dialog('close');
					
					if (closeCb) {
						closeCb();
					} // if
				} else {						
					/* We herladen de content zodat eventuele dialog wijzigingen duidelijk zijn */
					if (!autoClose) {
						loadDialogContent(false);
					} // if

					// voeg nu de errors in de html
					var $formerrors = $dialdiv.find("ul.formerrors");
					$formerrors.empty();

					// zet de errors van het formulier in de errorlijst
					$('errors', xml).each(function() {
						$formerrors.append("<li>" + $(this).text() + "</li>");
					}); // each
				} // if post was not succesful
			} // success()
		}); // ajax call om de form te submitten
		
		return false; // standaard button submit supressen
	} // buttonClick

	
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
					
					pagenr++;
					$("td.next > a").attr("href", url);
					$("div.container").scrollTop(scrollLocation);
				});
			}
		}
	});
});

// Haal de comments op en zet ze per batch op het scherm
function loadComments(messageid,perpage,pagenr) {
	if (!spotweb_security_allow_view_comments) {
		return false;
	} // if 
	
	var xhr = null;
	xhr = $.get('?page=render&tplname=comment&messageid='+messageid+'&pagenr='+pagenr, function(html) {
		count = $(html+' > li').length / 2;
		if (count == 0 && pagenr == 0) {
			$("#commentslist").append("<li class='nocomments'>Geen (geverifieerde) comments gevonden.</li>");
		} else {
			$("span.commentcount").html('# '+$("#commentslist").children().not(".addComment").size());
		}

		$("#commentslist").append($(html).fadeIn('slow'));
		$("#commentslist > li:nth-child(even)").addClass('even');
		$("#commentslist > li.addComment").next().addClass('firstComment');

		pagenr++;
		if (count >= 1) { 
			loadComments(messageid,'5',pagenr);
		} else {
			detectScrollbar();
		}
	});
	$("a.closeDetails").click(function() { xhr.abort() });
}

// Load post comment form
function postCommentsForm() {
	$("li.addComment a.togglePostComment").click(function(){
		if($("li.addComment div").is(":hidden")) {
			$("li.addComment div").slideDown(function(){
				detectScrollbar();
			});
			$("li.addComment a.togglePostComment span").addClass("up").parent().attr("title", "Reactie toevoegen (verbergen)");
		} else {
			$("li.addComment div").slideUp(function(){
				detectScrollbar();
			});
			$("li.addComment a.togglePostComment span").removeClass("up").parent().attr("title", "Reactie toevoegen (uitklappen)");
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
	})

	function sterStatus(id, rating) {
		if (id == 1) { ster = 'ster'; } else { ster = 'sterren'; }

		if (id < rating) {
			$("span#ster"+id).addClass("active").attr('title', 'Geef spot '+id+' '+ster);
		} else if (id == rating) {
			$("span#ster"+id).addClass("active").attr('title', 'Geen '+ster+' geven');
		} else {
			$("span#ster"+id).removeClass("active").attr('title', 'Geef spot '+id+' '+ster);
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
		$('a.postimage').attr('title', 'Klik om dit plaatje op ware grootte te laten zien (i)');
		detectScrollbar();
	});
}

function toggleImageSize(url) {
	if($("img.spotinfoimage").hasClass("full")) {
		$("img.spotinfoimage").removeClass("full");
		$("img.spotinfoimage").removeAttr("style");
		$('a.postimage').attr('title', 'Klik om dit plaatje op ware grootte te laten zien (i)');
	} else {
		$('a.postimage').attr('title', 'Klik om plaatje te verkleinen');
		$("img.spotinfoimage").addClass("full");
		$("img.spotinfoimage").css({
			'max-width': $("div#overlay").width() - 5,
			'max-height': $("div#overlay").height() - 35
		});
	}
}

// Bind keys to functions
$(function(){
	$('table.spots tbody tr').first().addClass('active');
	$(document).bind('keydown', 'k', function(){if(!($("div#overlay").hasClass("loading"))) {spotNav('prev')}});
	$(document).bind('keydown', 'j', function(){if(!($("div#overlay").hasClass("loading"))) {spotNav('next')}});
	$(document).bind('keydown', 'o', function(){if($("#overlay").is(':hidden')){$('table.spots tbody tr.active a.spotlink').click()}});
	$(document).bind('keydown', 'return', function(){if($("#overlay").is(':hidden')){$('table.spots tbody tr.active a.spotlink').click()}});
	$(document).bind('keydown', 'u', function(){$("a.closeDetails").click()});
	$(document).bind('keydown', 'esc', function(){$("a.closeDetails").click()});
	$(document).bind('keydown', 'i', toggleImageSize);
	$(document).bind('keydown', 's', function(){if($("#overlay").is(':visible') || $('#details').hasClass("external")) {$("#details a.sabnzbd-button").click()} else {$("tr.active a.sabnzbd-button").click()}});
	$(document).bind('keydown', 'n', function(){if($("#overlay").is(':visible') || $('#details').hasClass("external")) {location.href = $("#details a.nzb").attr('href')} else if($("th.nzb").is(":visible")) {location.href = $("tr.active a.nzb").attr('href')}});
	$(document).bind('keydown', 'w', function(){if($("#overlay").is(':visible') || $('#details').hasClass("external")) {$("#details th.watch a:visible").click()} else if($("div.spots").hasClass("watchlist")) {location.href = $("tr.active td.watch a").attr('href')} else {$("tr.active td.watch a:visible").click()}});
	$(document).bind('keydown', 't', function(){openNewWindow()});
	$(document).bind('keydown', 'h', function(){location.href = '?search[tree]=&search[unfiltered]=true'});
	$(document).bind('keydown', 'm', downloadMultiNZB);
	$(document).bind('keydown', 'c', checkMultiNZB);
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
			$('table.spots tbody tr.active a.spotlink').click();
		}
	} else if (direction == 'next' && next.size() == 1) {
		current.removeClass('active');
		next.addClass('active');
		if($("#overlay").is(':visible')) {
			$("div.container").removeClass("hidden").addClass("visible");
			$(document).scrollTop($('table.spots tr.active').offset().top - 50);
			$("table.spots tbody tr.active a.spotlink").click();
		}
	}
	if($("#overlay").is(':hidden')) {$(document).scrollTop($('table.spots tr.active').offset().top - 50)}
}

// Edit user preference tabs
$(document).ready(function() {
	$("#edituserpreferencetabs").tabs();
	$("#adminpaneltabs").tabs();
	
	$('#nzbhandlingselect').change(function() {
	   $('#nzbhandling-fieldset-localdir, #nzbhandling-fieldset-runcommand, #nzbhandling-fieldset-sabnzbd, #nzbhandling-fieldset-nzbget').hide();
	   
	   var selOpt = $(this).find('option:selected').data('fields').split(' ');
	   $.each(selOpt, function(index) {
			$('#nzbhandling-fieldset-' + selOpt[index]).show();
		}); // each
	});	// change

	// roep de change handler aan zodat alles goed staat
	$('#nzbhandlingselect').change();

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
});

// Regel positie en gedrag van sidebar (fixed / relative)
$().ready(function() {
	$('#filterscroll').bind('change', function() {
		var scrolling = $(this).is(':checked');
		$.cookie('scrolling', scrolling, { path: '', expires: $COOKIE_EXPIRES, domain: '$COOKIE_HOST' });

		toggleScrolling(scrolling);
	});

	var scrolling = $.cookie("scrolling");
	toggleScrolling(scrolling);
});

function toggleScrolling(state) {
	if (state == true || state == 'true') {
		$('#filterscroll').attr({checked:'checked', title:'Maak sidebar niet altijd zichtbaar'});
		$('body').addClass('fixed');
	} else {
		$('#filterscroll').attr({title:'Maak sidebar altijd zichtbaar'});
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
});

function toggleSidebarItem(id) {
	var hide = $(id).next();
	
	$(hide).toggle();
	$(id).children("h4").children("span").toggleClass("up down");

	getSidebarState()
}

// Geavanceerd zoeken op juiste moment zichtbaar / onzichtbaar maken
$(function(){
	$("input.searchbox").focus(function(){
		if($("form#filterform .advancedSearch").is(":hidden")) {
			toggleSidebarPanel('.advancedSearch');
		}
	});

	$("input[name='search[unfiltered]']").attr('checked') ? $("div#tree").hide() : $("div#tree").show();
	$("input[name='search[unfiltered]']").click(function() {
		if($("div#tree").is(":visible")) {
			$("div#tree").hide();
			$("ul.clearCategories label").html('Categori&euml;n gebruiken');
		} else {
			$("div#tree").show();
			$("ul.clearCategories label").html('Categori&euml;n niet gebruiken');
		}
	});
});

// Pas sorteervolgorde aan voor datum
$(function(){
	$("ul.sorting input").click(function() {
		if($(this).val() == 'stamp' || $(this).val() == 'commentcount' || $(this).val() == 'spotrating') {
			$("div.advancedSearch input[name=sortdir]").attr("value", "DESC");
		} else {
			$("div.advancedSearch input[name=sortdir]").attr("value", "ASC");
		}
	});
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

		if(id == ".userPanel") {
			$("div.login").load('?page=login', function() {
				$('form.loginform').submit(function(){ 
					var xsrfid = $("form.loginform input[name='loginform[xsrfid]']").val();
					var username = $("form.loginform input[name='loginform[username]']").val();
					var password = $("form.loginform input[name='loginform[password]']").val();
					
					var url = $("form.loginform").attr("action");
					var dataString = 'loginform[xsrfid]=' + xsrfid + '&loginform[username]=' + username + '&loginform[password]=' + password + '&loginform[submit]=true';
	
					$.ajax({
						type: "POST",
						url: url,
						dataType: "xml",
						data: dataString,
						success: function(xml) {
							result = $(xml).find('result').text();
	
							$("div.login ul.formerrors > li").empty()
							if(result == "failure") {
								$("div.login > ul.formerrors").append("<li>Inloggen mislukt</li>");
							} else {
								$("div.login > ul.forminformation").append("<li>Succesvol ingelogd</li>");
								setTimeout( function() { location.reload() }, 2000);
							}
						}
					});
					return false;
				});	
			});
		}
		
		if(id == ".sabnzbdPanel") {
			updateSabPanel(0,5);
		}
	}
}

// SabNZBd knop; url laden via ajax (regel loading en succes status)
function downloadSabnzbd(id,url) {
	$(".sab_"+id).removeClass("succes").addClass("loading");
	$.get(url, function(data) {
		$(".sab_"+id).removeClass("loading").addClass("succes");
	});
}

// Voorzie de span.newspots van link naar nieuwe spots binnen het filter
function gotoNew(url) {
	$("a").click(function(){ return false; });
	window.location = url+'&search[value][]=New:0';
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
			$('span.count').html('Download '+count+' spot');
		} else {
			$('span.count').html('Download '+count+' spots');
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
	var data = jQuery.parseJSON($.cookie("filterVisiblity"));
	if(data != null) {
		$.each(data, function(i, value) {
			$("ul.filters").children().eq(value.count).children("ul").css("display", value.state);
			if(value.state == "block") {
				$("ul.filters").children().eq(value.count).children("a").children("span.toggle").css("background-position", "-77px -98px");
				$("ul.filters").children().eq(value.count).children("a").children("span.toggle").attr("title", "Filter inklappen");
			} else {
				$("ul.filters").children().eq(value.count).children("a").children("span.toggle").css("background-position", "-90px -98px");
				$("ul.filters").children().eq(value.count).children("a").children("span.toggle").attr("title", "Filter uitklappen");

			}
		});
	}
});

function toggleFilter(id) {
	$(id).parent().click(function(){ return false; });

	var ul = $(id).parent().next();
	if($(ul).is(":visible")) {
		ul.hide();
		ul.prev().children("span.toggle").css("background-position", "-90px -98px");
		ul.prev().children("span.toggle").attr("title", "Filter uitklappen");
	} else {
		ul.show();
		ul.prev().children("span.toggle").css("background-position", "-77px -98px");
		ul.prev().children("span.toggle").attr("title", "Filter inklappen");
	}

	var data = new Array();
	$("ul.filters > li > ul").each(function(index) {
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
		setTimeout( function() { $("li.info").html("Nieuwe spots binnengehaald") }, 1000);
		setTimeout( function() { location.reload() }, 2000);
	});
}

function eraseDownloads() {
	var url = $("ul.maintenancebox a.erasedownloads").attr("href");

	$("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
	$.get(url, function(data) {
		setTimeout( function() { $("li.info").html("Download geschiedenis verwijderd") }, 1000);
		setTimeout( function() { location.reload() }, 2000);
	});
}

function markAsRead() {
	var url = $("ul.maintenancebox a.markasread").attr("href");

	$("li.info").html("<img src='templates/we1rdo/img/loading.gif' />");
	$.get(url, function(data) {
		setTimeout( function() { $("li.info").html("Alles als gelezen gemarkeerd") }, 1000);
		setTimeout( function() { location.reload() }, 2000);
	});
}

// User systeem
function userLogout() {
	var url = '?page=logout';

	$("div.userPanel > a.greyButton").hide();
	$("div.userPanel > a.greyButton").before("<span class='info'><img src='templates/we1rdo/img/loading.gif' /></span>");
	$.get(url, function(data) {
		setTimeout( function() { $("span.info").html("Succesvol uitgelogd") }, 1000);
		setTimeout( function() { location.reload() }, 2000);
	});
}

function toggleCreateUser() {
	var url = '?page=createuser';

	if($("div.createUser").html() && $("div.createUser").is(":visible")) {
		$("div.userPanel > a.viewState > h4 > span.createUser").removeClass("up").addClass("down");
		$("div.createUser").hide();
	} else {
		if($("div.createUser")) {$("div.createUser").html()}		
		$("div.createUser").load(url, function() {
			$("div.createUser").show();
			$("div.userPanel > a.viewState > h4 > span.createUser").removeClass("down").addClass("up");

			$('form.createuserform').submit(function(){ 
				var xsrfid = $("form.createuserform input[name='createuserform[xsrfid]']").val();
				var username = $("form.createuserform input[name='createuserform[username]']").val();
				var firstname = $("form.createuserform input[name='createuserform[firstname]']").val();
				var lastname = $("form.createuserform input[name='createuserform[lastname]']").val();
				var mail = $("form.createuserform input[name='createuserform[mail]']").val();
				var sendmail = $("form.createuserform input[name='createuserform[sendmail]']").is(':checked');

				var url = $("form.createuserform").attr("action");
				var dataString = 'createuserform[xsrfid]=' + xsrfid + '&createuserform[username]=' + username + '&createuserform[firstname]=' + firstname + '&createuserform[lastname]=' + lastname + '&createuserform[mail]=' + mail + '&createuserform[sendmail]=' + sendmail + '&createuserform[submit]=true';

				$.ajax({
					type: "POST",
					url: url,
					dataType: "xml",
					data: dataString,
					success: function(xml) {
						var result = $(xml).find('result').text();
						
						$("div.createUser > ul.forminformation").empty();
						$("div.createUser > ul.formerrors").empty();
						if(result == "success") {
							var user = $(xml).find('user').text();
							var pass = $(xml).find('password').text();
							$("div.createUser > ul.forminformation").append("<li>Gebruiker <strong>&quot;"+user+"&quot;</strong> succesvol toegevoegd</li>");
							$("div.createUser > ul.forminformation").append("<li>Wachtwoord: <strong>&quot;"+pass+"&quot;</strong></li>");
						} else {
							$('errors', xml).each(function() {
								$("div.createUser > ul.formerrors").append("<li>"+$(this).text()+"</li>");
							});
						}
					}
				});
				return false;
			});	
		});
	}
}

function toggleEditUser(userid) {
	var url = '?page=edituser&userid='+userid;

	if($("div.editUser").html() && $("div.editUser").is(":visible")) {
		$("div.userPanel > a.viewState > h4 > span.editUser").removeClass("up").addClass("down");
		$("div.editUser").hide();
	} else {
		if($("div.editUser")) {$("div.editUser").html()}		
		$("div.editUser").load(url, function() {
			$("div.editUser").show();
			$("div.userPanel > a.viewState > h4 > span.editUser").removeClass("down").addClass("up");

			$(".greyButton").click(function(){
				$("form.edituserform input[name='edituserform[buttonpressed]']").val(this.name);
			});

			$('form.edituserform').submit(function(){
				var xsrfid = $("form.edituserform input[name='edituserform[xsrfid]']").val();
				var action = $("form.edituserform input[name='edituserform[action]']").val();
				var newpassword1 = $("form.edituserform input[name='edituserform[newpassword1]']").val();
				var newpassword2 = $("form.edituserform input[name='edituserform[newpassword2]']").val();
				var firstname = $("form.edituserform input[name='edituserform[firstname]']").val();
				var lastname = $("form.edituserform input[name='edituserform[lastname]']").val();
				var mail = $("form.edituserform input[name='edituserform[mail]']").val();
				
				// determine which button was pressed
				var buttonPressed = $("form.edituserform input[name='edituserform[buttonpressed]']").val();
				var dataString = 'edituserform[xsrfid]=' + xsrfid + '&userid=' + userid + '&' + buttonPressed + '=true&edituserform[newpassword1]=' + newpassword1 + '&edituserform[newpassword2]=' + newpassword2 + '&edituserform[firstname]=' + firstname + '&edituserform[lastname]=' + lastname + '&edituserform[mail]=' + mail;

				$.ajax({
					type: "POST",
					url: url,
					dataType: "xml",
					data: dataString,
					success: function(xml) {
						var result = $(xml).find('result').text();

						$("div.editUser > ul.forminformation").empty();
						$("div.editUser > ul.formerrors").empty();
						if(result == "success") {
							$("div.editUser > ul.forminformation").append("<li>Gebruiker succesvol gewijzigd</li>");
						} else {
							$('errors', xml).each(function() {
								$("div.editUser > ul.formerrors").append("<li>"+$(this).text()+"</li>");
							});
						}
					}
				});
				return false;
			});
		});
	}
}

// SabNZBd actions
function sabBaseURL() {
	var apikey = $("div.sabnzbdPanel input.apikey").val();
	var sabBaseURL = createBaseURL()+'?page=sabapi&sabapikey='+apikey;
	return sabBaseURL;
}

function sabActions(start,limit,action,slot,value) {
	var baseURL = sabBaseURL();
	
	if(action == 'pause') {
		var url = baseURL+'&mode=pause';
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'resume') {
		var url = baseURL+'&mode=resume';
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'speedlimit') {
		var limit = $("td.speedlimit input[name=speedLimit]").val();
		var url = baseURL+'&mode=config&name=speedlimit&value='+limit;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'up') {
		var newIndex = value-1;
		var url = baseURL+'&mode=switch&value='+slot+'&value2='+newIndex;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'down') {
		var newIndex = value+1;
		var url = baseURL+'&mode=switch&value='+slot+'&value2='+newIndex;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	} else if(action == 'delete') {
		var url = baseURL+'&mode=queue&name=delete&value='+slot;
		$.get(url, function(){
			updateSabPanel(start,limit);
		});
	}
}

// Text toevoegen aan id (Smiley's)
function addText(text,element_id) {
	document.getElementById(element_id).value += text;
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
	var url = baseURL+'&mode=queue&start='+start+'&limit='+limit+'&output=json';

	$.getJSON(url, function(json){
		var queue = json.queue;

		if(queue.paused) {var state = "resume"} else {var state = "pause"}
		$("table.sabInfo td.state").html("<strong>"+queue.status+"</strong> (<a class='state' title='"+state+"'>"+state+"</a>)");
		$("table.sabInfo td.state a.state").click(function(){
			if(timeOut) {clearTimeout(timeOut)}; 
			sabActions(start,limit,state);
		});
		$("table.sabInfo td.speed").html("<strong>"+queue.kbpersec+"</strong> KB/s");
		$("table.sabInfo td.speedlimit").html("<input type='text' name='speedLimit' value='"+queue.speedlimit+"'><label>KB/s</label>");
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
		$("table.sabInfo td.timeleft").html("<strong>"+queue.timeleft+"</strong>");
		$("table.sabInfo td.eta").html("<strong>"+queue.eta+"</strong>");
		$("table.sabInfo td.mb").html("<strong>"+queue.mbleft+"</strong> / <strong>"+queue.mb+"</strong> MB");

		$("table.sabQueue").empty();
		if(queue.noofslots == 0) {
			$("table.sabQueue").html("<tr><td class='info'>Geen items in de wachtrij</td></tr>");
		} else {
			$.each(queue.slots, function(){
				var slot = this;
				if(slot.percentage == 0) {var progress = " empty"} else {var progress = "";}
				
				$("table.sabQueue").append("<tr class='title "+slot.index+"'><td><span class='move'><a class='up' title='Omhoog'></a><a class='down' title='Omlaag'></a></span><span class='delete'><a title='Verwijder uit de wachtrij'></a></span><strong>"+slot.index+".</strong><span class='title'>"+slot.filename+"</span></td></tr><tr class='progressBar'><td><div class='progressBar"+progress+"' title='"+slot.mbleft+" / "+slot.mb+" MB' style='width:"+slot.percentage+"%'></div></td></tr>");
				
				$("table.sabQueue tr."+slot.index+" a.up").click(function(){
					if(timeOut) {clearTimeout(timeOut)}; 
					sabActions(start,limit,'up', slot.nzo_id, slot.index);
				});
				$("table.sabQueue tr."+slot.index+" a.down").click(function(){
					if(timeOut) {clearTimeout(timeOut)}; 
					sabActions(start,limit,'down', slot.nzo_id, slot.index);
				});
				$("table.sabQueue tr."+slot.index+" span.delete a").click(function(){
					if(timeOut) {clearTimeout(timeOut)}; 
					if(start+1 > queue.noofslots-1) {
						sabActions(start-(limit-start),limit-(limit-start),'delete', slot.nzo_id);
					} else {
						sabActions(start,limit,'delete', slot.nzo_id);
					}
				});
			});
		}

		if(queue.noofslots != 0 && queue.noofslots > limit) {
			$("table.sabQueue").append("<tr class='nav'><td>Toon "+(start+1)+" t/m "+limit+" van "+queue.noofslots+" resultaten</td></tr>");
		} else if(queue.noofslots != 0 && limit > queue.noofslots) {
			if(queue.noofslots == 1) {
				$("table.sabQueue").append("<tr class='nav'><td>Toon "+queue.noofslots+" resultaat</td></tr>");
			} else {
				$("table.sabQueue").append("<tr class='nav'><td>Toon "+(start+1)+" t/m "+queue.noofslots+" van "+queue.noofslots+" resultaten</td></tr>");
			}
		} else if(queue.noofslots != 0 && limit == queue.noofslots) {
			$("table.sabQueue").append("<tr class='nav'><td>Toon "+(start+1)+" t/m "+limit+" van "+queue.noofslots+" resultaten</td></tr>");
		}

		if($("table.sabQueue tr.title td span.move").size() == 1) {
			$("table.sabQueue tr.title td span.move").hide();
		} else {
			$("table.sabQueue tr.title td span.move").first().css('padding', '2px 4px 3px 0').children("a.up").hide();
			$("table.sabQueue tr.title td span.move").last().css('padding', '2px 4px 3px 0').children("a.down").hide();
		}

		if(start > 1) {
			$("table.sabQueue tr.nav td").prepend("<a class='prev' title='Vorige'>&lt;&lt;</a> ");
		}
		if(queue.noofslots > limit) {
			$("table.sabQueue tr.nav td").append(" <a class='next' title='Volgende'>&gt;&gt;</a>");
		}

		$("table.sabQueue tr.nav a").click(function(){
			if(timeOut) {clearTimeout(timeOut)}
			if($(this).hasClass("prev")) {
				updateSabPanel(start-(limit-start),limit-(limit-start));
			} else if($(this).hasClass("next")) {
				updateSabPanel(start+limit,limit+limit);
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
		})

		var interval = 5000;
		drawGraph(queue.kbpersec, interval);

		var timeOut = setTimeout(function(){
			if($("div.sabnzbdPanel").is(":visible") && !($("td.speedlimit input[name=speedLimit]").hasClass("hasFocus")) && !($("tr.title td span.title").hasClass("hover"))) {
				updateSabPanel(start,limit);
			}
		}, interval);
	});
}

function format_size(size) {
	var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	var i = 0;
	while(size >= 1024) {
		size /= 1024;
		++i;
	}
	return size.toFixed(1) + ' ' + sizes[i];
}