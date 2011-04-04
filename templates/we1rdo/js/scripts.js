// Zorg ervoor dat overlay op juiste moment zichtbaar is
$(function(){	
	$("a.spotlink").click(function(e) {
		e.preventDefault();
		
		$("#overlay").show();
		$("#overlay").addClass('loading');
		
		$("#overlay").load(this.href+' #details', function() {
			$("#overlay").removeClass('loading');
		});
	});
});

function closeDetails() {
	$("#overlay").hide();
	$("#details").remove();
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