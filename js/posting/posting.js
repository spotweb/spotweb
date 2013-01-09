function spotPosting() {
	this.commentForm = null;
	this.reportForm = null;
	this.uiStart = null;
	this.uiDone = null;

	this.cbHashcashCalculated = function (self, hash) {
	    self.commentForm['postcommentform[newmessageid]'].value = hash;
	    self.commentForm['postcommentform[submitpost]'].value = 'Post';
		self.uiDone();

		// convert the processed form to post values
		var dataString = $(self.commentForm).serialize()
		
		// and actually process the call
		$.ajax({  
			type: "POST",  
			url: "?page=postcomment",  
			dataType: "xml",
			data: dataString,  
			success: function(xml) {
				var result = $(xml).find('result').text();
				if(result == 'success') {
					var user = $(xml).find('user').text();
					var spotterid = $(xml).find('spotterid').text();
					var rating = $(xml).find('rating').text();
					var text = $(xml).find('body').text();
					var spotteridurl = 'http://'+window.location.host+window.location.pathname+'?search[tree]=&amp;search[type]=SpotterID&amp;search[text]='+spotterid;
					var commentimage = $(xml).find('commentimage').text();

					var data = "<li> <img class='commentavatar' src='" + commentimage + "'> <strong> <t>Posted by %1</t>".replace("%1", "<span class='user'>"+user+"</span>") + " (<a class='spotterid' target='_parent' href='"+spotteridurl+"' title='<t>Search spots from %1</t>".replace("%1", spotterid) + "'>"+spotterid+"</a>) @ <t>just now</t> </strong> <br>"+text+"</li>";

					$("li.nocomments").remove();
					$("li.firstComment").removeClass("firstComment");
					$("li.addComment").after(data).next().hide().addClass("firstComment").fadeIn(function(){
						$("#commentslist > li").removeClass("even");
						$("#commentslist > li:nth-child(even)").addClass('even');
						$("span.commentcount").html('# '+$("#commentslist").children().not(".addComment").size());
					});
				}
			},
			error: function(xml) {
				console.log('error: '+((new XMLSerializer()).serializeToString(xml)));
			}
		});
	} // cbHashcashCalculated
		
	this.rpHashcashCalculated = function (self, hash) {
			self.reportForm['postreportform[newmessageid]'].value = hash;
			self.reportForm['postreportform[submitpost]'].value = 'Post';
			self.uiDone();
			
			var dataString2 = $(self.reportForm).serialize()
			
			$.ajax({  
				type: "POST",  
				url: "?page=reportpost",  
				dataType: "xml",
				data: dataString2,  
				success: function(xml) {
					var result = $(xml).find('result').text();
					var errors = $(xml).find('errors').text();
					
					if(result != 'success') {
						console.log('error: '+((new XMLSerializer()).serializeToString(xml)));
						
						$(".spamreport-button").attr('title', result + ': ' + errors);
						alert('<t>Marking as spam was not successfull:</t> ' + errors);
					} // else					
				},
				error: function(xml) {
					console.log('error: '+((new XMLSerializer()).serializeToString(xml)));
				}
			});
	} // callback rpHashcashCalculated

	this.spotHashcashCalculated = function (self, hash) {
			// and enter the form's inputfields
			self.newSpotForm['newspotform[newmessageid]'].value = hash;
			self.newSpotForm['newspotform[submitpost]'].value = 'Post';
			self.uiDone();

			$(self.newSpotForm).ajaxSubmit({
				type: "POST",  
				url: "?page=postspot",  
				dataType: "xml",
				success: function(xml) {
					var $dialdiv = $("#editdialogdiv")
					var result = $(xml).find('result').text();
					
					var $formerrors = $dialdiv.find("ul.formerrors");
					$formerrors.empty();
					var $forminfo = $dialdiv.find("ul.forminformation");
					$forminfo.empty();

					if (result == 'success') {
						// zet de information van het formulier in de infolijst
						$('info', xml).each(function() {
							$forminfo.append("<li>" + $(this).text() + "</li>");
						}); // each
						
						/**
						 * We succesfully posted a new spot, now clear the title field
						 * and body. This makes the other field stay in tact, so if a 
						 * user is posting several of the same type of spots, he doesn't
						 * have to enter everything again 
						 */
						$("input[name='newspotform[title]']").val('');
						$("textarea[name='newspotform[body]']").val('');
						$("input[name='newspotform[nzbfile]']").val('');
						$("input[name='newspotform[imagefile]']").val('');						
					} else {						
						// voeg nu de errors in de html
						// zet de errors van het formulier in de errorlijst
						$('errors', xml).each(function() {
							$formerrors.append("<li>" + $(this).text() + "</li>");
						}); // each
					} // if post was not succesful
				}, // success()
				error: function(xml) {
					console.log('error: '+((new XMLSerializer()).serializeToString(xml)));
				}
			});
	} // callback spotHashcashCalculated
	
	this.postComment = function(commentForm, uiStart, uiDone) {
		this.commentForm = commentForm;
		this.uiStart = uiStart;
		this.uiDone = uiDone;
		
		// update the UI 
		this.uiStart();

		// First retrieve some values from the form we are submitting
		var randomstr = commentForm['postcommentform[randomstr]'].value;
		var rating = commentForm['postcommentform[rating]'].value;

		// inreplyto is the whole messageid, we strip off the @ part to add
		// our own stuff
		var inreplyto = commentForm['postcommentform[inreplyto]'].value;
		inreplyto = inreplyto.substring(0, inreplyto.indexOf('@'));

		/* Nu vragen we om, asynchroon, een hashcash te berekenen. Zie comments van calculateCommentHashCash()
		   waarom dit asynhcroon verloopt */
		this.calculateCommentHashCash('<' + inreplyto + '.' + rating + '.' + randomstr + '.', '@spot.net>', 0, this.cbHashcashCalculated);
	} // postComment
	
	this.postReport = function(reportForm, uiStart, uiDone) {
		this.reportForm = reportForm;
		this.uiStart = uiStart;
		this.uiDone = uiDone;
		
		this.uiStart();
		
		var randomstr = reportForm['postreportform[randomstr]'].value;
		
		var inreplyto = reportForm['postreportform[inreplyto]'].value;
		inreplyto = inreplyto.substring(0, inreplyto.indexOf('@'));
		
		this.calculateCommentHashCash('<' + inreplyto + '.' + randomstr + '.', '@spot.net>', 0, this.rpHashcashCalculated);
	} // postReport

	this.postNewSpot = function(newSpotForm, uiStart, uiDone) {
		this.newSpotForm = newSpotForm;
		this.uiStart = uiStart;
		this.uiDone = uiDone;

		/* Clear the errors */
		var $dialdiv = $("#editdialogdiv")
		$dialdiv.find("ul.formerrors").empty();
		$dialdiv.find("ul.forminformation").empty();
				
		this.uiStart();
		
		var randomstr = newSpotForm['newspotform[randomstr]'].value;
		
		this.calculateCommentHashCash('<' + randomstr, '@spot.net>', 0, this.spotHashcashCalculated);
	} // postNewSpot
	
	//
	// We breken de make expensive hash op in stukken omdat 
	// anders IE8 gaat zeuren over scripts die te lang lopen.
	//
	this.calculateCommentHashCash = function(prefix, suffix, runCount, cbWhenFound) {
		var possibleChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		var hash = prefix + suffix;
		var validHash = false;

		do {
			runCount++;
			uniquePart = '';
			
			for(i = 0; i < 15; i++) {
				var irand = Math.round(Math.random() * (possibleChars.length - 1));
				uniquePart += possibleChars.charAt(irand);
			} // for

			hash = $.sha1(prefix + uniquePart + suffix);
			validHash = (hash.substr(0, 4) == '0000');
		} while ((!validHash) && ((runCount % 500) != 0));

		if (validHash) {
			cbWhenFound(this, prefix + uniquePart + suffix);
		} else {
			if (runCount > 400000) {
				alert("<t>Calculation of SHA1 hash was not successfull:</t> " + runCount);
				cbWhenFound(this, '');
			} else {
				var _this = this;
				window.setTimeout(function() { _this.calculateCommentHashCash(prefix, suffix, runCount, cbWhenFound); }, 0);
			} // else
		} // if

	} // calculateCommentHashCash
};
