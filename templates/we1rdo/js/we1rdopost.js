function postCommentUiStart() {
	$("li.addComment > div div").css({
		width: $("li.addComment > div").width(),
		height: $("li.addComment > div").height()
	});
	$("li.addComment > div div").addClass("loading");
} // postCommentUiStart

function postCommentUiDone() {
	$("li.addComment a.togglePostComment").click();
	$("li.addComment > div div").removeClass("loading");
} // postCommentUidone

function postReportUiStart() {
	$(".spamreport-button").addClass("loading");
} // postReportUiStart

function postReportUiDone() {
	$(".spamreport-button").removeClass("loading");
	$(".spamreport-button").addClass("success");
	$(".spamreport-button").attr("title", "Deze spot heb jij als spam gerapporteerd");
} // postReportUiDone

function postSpotUiStart() {
} // postSpotUiStart()

function postSpotUiDone() {
} // postSpotUiDone()