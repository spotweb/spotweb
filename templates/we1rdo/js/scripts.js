// Bind keys to functions
$(function(){
	$('table.spots tbody tr').first().addClass('active');
	$(document).bind('keydown', 'k', function(){spotNav('prev')});
	$(document).bind('keydown', 'j', function(){spotNav('next')});
	$(document).bind('keydown', 'o', function(){$('table.spots tbody tr.active a.spotlink').click()});
	$(document).bind('keydown', 'i', toggleImageSize);
	$(document).bind('keydown', 'return', function(){$('table.spots tbody tr.active a.spotlink').click()});
	$(document).bind('keydown', 'u', function(){$("a.closeDetails").click()});
	$(document).bind('keydown', 'esc', function(){$("a.closeDetails").click()});
});

// openSpot in overlay
function openSpot(id,url) {
	$("table.spots tr.active").removeClass("active");
	$(id).parent().parent().addClass('active');
	
	var messageid = url.split("=")[2];
	
	$("#overlay").empty().show();
	$("#overlay").addClass('loading');
	
	var scrollLocation = $(document).scrollTop();
	$("#overlay").load(url+' #details', function() {
		$("div.container").removeClass("visible").addClass("hidden");
		$("#overlay").removeClass('loading');
		
		$("a.closeDetails").click(function(){ 
			closeDetails(scrollLocation); 
		});
		
		loadComments(messageid,'5','0');
		loadSpotImage();
	});
}

// Sluit spotinfo overlay
function closeDetails(scrollLocation) {
	$("div.container").removeClass("hidden").addClass("visible");
	$("#overlay").hide();
	$("#details").remove();
	$(document).scrollTop(scrollLocation);
}

// Laadt nieuwe spots in overzicht wanneer de onderkant wordt bereikt
$(function(){
	var pagenr = $('#nextPage').val();
	$(document).scroll(function() {
		var url = '?direction=next&pagenr='+pagenr+$('#getURL').val()+' #spots';
		
		if($(document).scrollTop() >= $("div.spots").height() - $(window).height() && $("div.spots").height() >= $(window).height() && pagenr > 0) {
			var scrollLocation = $("div.container").scrollTop();
			$("#overlay").show().addClass('loading');
			$("div#overlay").load(url, function() {
				$("#overlay").hide().removeClass('loading');
				$("tbody#spots").append($($("div#overlay tbody#spots").html()).fadeIn('slow'));
				$("div#overlay").empty();
				
				pagenr++;
				$("td.next > a").attr("href", url);
				$("div.container").scrollTop(scrollLocation);
			});
		}
	});
});

// Haal de comments op en zet ze per batch op het scherm
function loadComments(messageid,perpage,pagenr) {
	$.get('?page=render&tplname=comment&messageid='+messageid+'&pagenr='+pagenr, function(html) {
		count = $(html+' > li').length / 2;
		if (count == 0 && pagenr == 0) { 
			$("#commentslist").html("<li class='nocomments'>Geen (geverifieerde) comments gevonden.</li>"); 
		} else {
			$("span.commentcount").html('# '+$("#commentslist").children().size());
		}
		
		$("#commentslist").append($(html).fadeIn('slow'));
		$("#commentslist > li:nth-child(even)").addClass('even');
		
		pagenr++;
		if (count > 0) { 
			loadComments(messageid,'5',pagenr);
		}
	});
}

// Laadt de spotImage wanneer spotinfo wordt geopend
function loadSpotImage() {
	$('img.spotinfoimage').hide();
	$('a.postimage').addClass('loading');
	$('img.spotinfoimage').load(function() {
		$('a.postimage').removeClass('loading');
		$(this).show();
		$('a.postimage').css({
			'width': $("img.spotinfoimage").width(),
			'height': $("img.spotinfoimage").height()
		})
		$('a.postimage').attr('title', 'Klik om dit plaatje op ware grootte te laten zien');
	});
}

function toggleImageSize(url) {
	if($("img.spotinfoimage").hasClass("full")) {
		$("img.spotinfoimage").removeClass("full");
		$("img.spotinfoimage").removeAttr("style");
		$('a.postimage').attr('title', 'Klik om dit plaatje op ware grootte te laten zien');
	} else {
		$('a.postimage').attr('title', 'Klik om plaatje te verkleinen');
		$("img.spotinfoimage").addClass("full");
		$("img.spotinfoimage").css({
			'max-width': $("div#overlay").width() - 5,
			'max-height': $("div#overlay").height() - 35
		});
	}
}

// Keyboard navigation
function spotNav(direction) {
	var current = $('table.spots tbody tr.active');
	var prev = current.prevUntil('tr.header').first();
	var next = current.next().first();
	
	if (direction == 'prev' && prev.size() == 1) {
		current.removeClass('active');
		prev.addClass('active');
	} else if (direction == 'next' && next.size() == 1) {
		current.removeClass('active');
		next.addClass('active');
	}

	if($("#overlay").is(':visible')) {
		$("a.closeDetails").click();
		$('table.spots tbody tr.active a.spotlink').click();
	}
}

// Regel positie en gedrag van sidebar (fixed / relative)
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
		$('#filterscroll').attr({checked:'checked', title:'Maak sidebar niet altijd zichtbaar'});
		$("#filter").css('position', 'fixed');
		$("#overlay").css('left', '235px');
	} else {
		$('#filterscroll').attr({title:'Maak sidebar altijd zichtbaar'});
		$("#filter").css('position', 'relative');
		$("#overlay").css('left', '0');
	}
}

// Regel het uit/inklappen van sidebar items
function toggleFilterBlock(linkName,block,cookieName) {
	$(block).toggle();
	if ($.cookie(cookieName) == 'none') { var view = 'block'; } else { var view = 'none'; }
	toggleFilterImage(linkName, view);
	$.cookie(cookieName, view, { path: '/', expires: 7 });
}

// Cookies uitlezen en aan de hand hiervan sidebar items verbergen / laten zien
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

// Wissel background in/uitklap button
function toggleFilterImage(linkName, state) {
	if (state == 'none') {
		$(linkName).removeClass("up");
		$(linkName).addClass("down");
	} else {
		$(linkName).removeClass("down");
		$(linkName).addClass("up");
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
	$.get("?page=watchlist&action="+action+"&messageid="+spot);

	// Switch buttons
	$('#watchremove_'+spot_id).toggle();
	$('#watchadd_'+spot_id).toggle();
}