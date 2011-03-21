function submitSearchForm(f) {
	
	var formField = $("#search-tree");
	
	// then append Dynatree selected 'checkboxes':
	var tree = $("#tree").dynatree("getTree").serializeArray();
	var tmp = '';
	for(var i = 0; i < tree.length; i++) {
		tmp += tree[i].value + ",";
	} // for
				
	//formField[0].value = tmp;
	
	var search_text = htmlspecialchars($('.search_text').val());
	var search_type = htmlspecialchars($('.radio_type:checked').val());
	var search_tree = tmp;
	
	$('#spots').load('?search[tree]='+search_tree+'&search[type]='+search_type+'&search[text]='+search_text+'&ajax=1')
	
	return false;
}

function scrollToTop() {
	$('html, body').animate({scrollTop:0}, 'slow');
}

function setMainFilter(f) {
	
	if(f.beeld.value != '') {
	  $('#spots').load('?search[tree]='+f.beeld.value+'&search[type]='+f.type.value+'&search[text]='+f.text.value+'&ajax=1')
	}
	
	return false;
}

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

function downloadMultiple() {
	
	var download_url = '?page=getnzb';
	$('#spot_table input:checked').each(function() {
		download_url += '&messageid%5B%5D='+$(this).val();
	});
	
	window.location=download_url;
	$("input[type=checkbox]").attr("checked", false);
	$(document).find('#download_menu').animate({'top': '-100px'}, 500, 'swing');
	
}


function htmlspecialchars (string, quote_style, charset, double_encode) {
    // Convert special characters to HTML entities  
    // 
    // version: 1103.1210
    // discuss at: http://phpjs.org/functions/htmlspecialchars
    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);
    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'
    var optTemp = 0,
        i = 0,
        noquotes = false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    string = string.replace(/ /g, '+');
 
    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE': 1,
        'ENT_HTML_QUOTE_DOUBLE': 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE': 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            } else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes) {
        string = string.replace(/"/g, '&quot;');
    }
 
    return string;
}