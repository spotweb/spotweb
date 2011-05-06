$(function(){
	$("a.spotlink").click(function(e) { e.preventDefault(); });

	$("a[href^='http']").attr('target','_blank');
});

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

		spotRating();
		postCommentsForm();
		loadComments(messageid,'5','0');
		loadSpotImage();
	});
}

// Open spot in los scherm
function openNewWindow() {
	url = $('table.spots tr.active a.spotlink').attr("onclick").toString().match(/"(.*?)"/)[1];
	window.open(url);
}

// Sluit spotinfo overlay
function closeDetails(scrollLocation) {
	$("div.container").removeClass("hidden").addClass("visible");
	$("body").removeClass("spotinfo");
	$("#overlay").hide();
	$("#details").remove();
	$(document).scrollTop(scrollLocation);
}

// Laadt nieuwe spots in overzicht wanneer de onderkant wordt bereikt
$(function(){
	var pagenr = $('#nextPage').val();
	$(window).scroll(function() {
		var url = '?direction=next&pagenr='+pagenr+$('#getURL').val()+' #spots';

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

	var i = 1;
	for (i = 1; i <= 10; i++) {
		if(i == 1) {
			$("li.addComment dd.rating").append("<span title='Geef spot "+i+" ster'></span>");
		} else {
			$("li.addComment dd.rating").append("<span title='Geef spot "+i+" sterren'></span>");
		}
	}
	$("li.addComment dd.rating span").click(function() {
		var rating = $(this).index();
		$("li.addComment dd.rating span").removeClass("active");
		$("li.addComment dd.rating span").each(function(){
			if($(this).index() <= rating) {
				$(this).addClass("active");
			}
		});
		$("li.addComment input[name='postcommentform[rating]']").val(rating);
	})
	
	$("form.postcommentform").submit(function(){ 
		new spotPosting().postComment(this,postCommentUiStart,postCommentUiDone); 
		return false;
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
	$("div#filter > h4").each(function(index) {
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
		$("div#filter > h4").eq(value.count).next().css("display", value.state);
		if(value.state != "none") {
			$("div#filter > h4").eq(value.count).children("span.viewState").children("a").removeClass("down").addClass("up");
		} else {
			$("div#filter > h4").eq(value.count).children("span.viewState").children("a").removeClass("up").addClass("down");
		}
	});
});

function toggleSidebarItem(id) {
	var hide = $(id).parent().parent().next();

	if($(hide).is(":visible")) {
		$(hide).hide();
		$(id).removeClass("up").addClass("down");
	} else {
		$(hide).show();
		$(id).removeClass("down").addClass("up");
	}
	getSidebarState()
}

// Geavanceerd zoeken op juiste moment zichtbaar / onzichtbaar maken
$(function(){
	$("input.searchbox").focus(function(){
		if($("form#filterform .advancedSearch").is(":hidden")) {
			toggleSidebarPanel('.advancedSearch')
		}
	});

	$("input.filtersubmit").click(function() {
		if($("ul.dynatree-container li > span").hasClass("dynatree-partsel")) {
			$("input[name='search[unfiltered]']").attr('checked', false);
		} else {
			$("input[name='search[unfiltered]']").attr('checked', true);
		}
	});
});

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
		$("div.userPanel span.viewState > a").removeClass("up").addClass("down");
		$("div.userPanel h4.dropDown").css("margin", "0 0 5px 0");
		$("div.createUser").hide();
	} else {
		if($("div.createUser")) {$("div.createUser").html()}		
		$("div.createUser").load(url, function() {
			$("div.createUser").show();
			$("div.userPanel h4.dropDown").css("margin", "0");
			$("div.userPanel span.viewState > a").removeClass("down").addClass("up");

			$('form.createuserform').submit(function(){ 
				var xsrfid = $("form.createuserform input[name='createuserform[xsrfid]']").val();
				var username = $("form.createuserform input[name='createuserform[username]']").val();
				var firstname = $("form.createuserform input[name='createuserform[firstname]']").val();
				var lastname = $("form.createuserform input[name='createuserform[lastname]']").val();
				var mail = $("form.createuserform input[name='createuserform[mail]']").val();

				var url = $("form.createuserform").attr("action");
				var dataString = 'createuserform[xsrfid]=' + xsrfid + '&createuserform[username]=' + username + '&createuserform[firstname]=' + firstname + '&createuserform[lastname]=' + lastname + '&createuserform[mail]=' + mail + '&createuserform[submit]=true';
				
				$.ajax({
					type: "POST",
					url: url,
					dataType: "xml",
					data: dataString,
					success: function(xml) {
						var result = $(xml).find('result').text();
						
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
		$("div.userPanel span.viewState > a").removeClass("up").addClass("down");
		$("div.userPanel h4.dropDown").css("margin", "0 0 5px 0");
		$("div.editUser").hide();
	} else {
		if($("div.editUser")) {$("div.editUser").html()}		
		$("div.editUser").load(url, function() {
			$("div.editUser").show();
			$("div.userPanel h4.dropDown").css("margin", "0");
			$("div.userPanel span.viewState > a").removeClass("down").addClass("up");

			$('form.edituserform').submit(function(){ 
				var xsrfid = $("form.edituserform input[name='edituserform[xsrfid]']").val();
				var action = $("form.edituserform input[name='edituserform[action]']").val();
				var newpassword1 = $("form.edituserform input[name='edituserform[newpassword1]']").val();
				var newpassword2 = $("form.edituserform input[name='edituserform[newpassword2]']").val();
				var firstname = $("form.edituserform input[name='edituserform[firstname]']").val();
				var lastname = $("form.edituserform input[name='edituserform[lastname]']").val();
				var mail = $("form.edituserform input[name='edituserform[mail]']").val();

				var url = $("form.edituserform").attr("action");
				var dataString = 'edituserform[xsrfid]=' + xsrfid + '&edituserform[action]=' + action + '&userid=' + userid + '&edituserform[newpassword1]=' + newpassword1 + '&edituserform[newpassword2]=' + newpassword2 + '&edituserform[firstname]=' + firstname + '&edituserform[lastname]=' + lastname + '&edituserform[mail]=' + mail + '&edituserform[submit]=true';

				$.ajax({
					type: "POST",
					url: url,
					// dataType: "xml",
					data: dataString,
					success: function(xml) {
						var result = $(xml).find('result').text();

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

// Pas sorteervolgorde aan voor datum
$(function(){
	$("ul.sorting input").click(function() {
		if($(this).val() == 'stamp') {
			$("div.advancedSearch input[name=sortdir]").attr("value", "DESC");
		} else {
			$("div.advancedSearch input[name=sortdir]").attr("value", "ASC");
		}
	});
});

// SabNZBd actions
function sabBaseURL() {
	var apikey = $("div.sabnzbdPanel input.apikey").val();
	var baseURL = 'http://'+window.location.hostname+window.location.pathname+'?page=sabapi&apikey='+apikey;
	return baseURL;
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
				
				$("table.sabQueue").append("<tr class='title "+slot.index+"'><td><span class='move'><a class='up' title='Omhoog'></a><a class='down' title='Omlaag'></a></span><span class='delete'><a title='Verwijder uit de wachtrij'></a></span><strong>"+slot.index+".</strong> "+slot.filename+"</td></tr><tr class='progressBar'><td><div class='progressBar"+progress+"' title='"+slot.mbleft+" / "+slot.mb+" MB' style='width:"+slot.percentage+"%'></div></td></tr>");
				
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
		
		if($("table.sabQueue tr.title td span.move").size() == 1) {
			$("table.sabQueue tr.title td span.move").hide();
		} else {
			$("table.sabQueue tr.title td span.move").first().css('padding', '2px 4px 3px 0').children("a.up").hide();
			$("table.sabQueue tr.title td span.move").last().css('padding', '2px 4px 3px 0').children("a.down").hide();
		}
		
		var interval = 5000;
		var timeOut = setTimeout(function(){
			if($("div.sabnzbdPanel").is(":visible") && !($("td.speedlimit input[name=speedLimit]").hasClass("hasFocus"))) {
				updateSabPanel(start,limit);
			}
		}, interval);
	});
}

// spotRating verwerken
function spotRating() {
	var rating = Math.round($("table.spotinfo td.rating").text());
	if($("table.spotinfo td.rating").is(":empty")) {
		$("table.spotinfo td.rating").html('N/A');
	} else {
		$("table.spotinfo td.rating").empty().addClass("stars");
		var i = 1;
		for (i = 1; i <= 10; i++) {
			if(rating == 1) {
				$("table.spotinfo td.rating").append("<span title='Deze spot heeft "+rating+" ster'></span>");
			} else {
				$("table.spotinfo td.rating").append("<span title='Deze spot heeft "+rating+" sterren'></span>");
			}
		}
		$("table.spotinfo td.rating span").each(function(){
			if($(this).index()+1 <= rating) {
				$(this).addClass("active");
			}
		});
	}
}