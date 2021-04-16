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
    $("#postReportFormSubmitButton").addClass("loading");
} // postReportUiStart

function postReportUiDone() {
	$(".spamreport-button").removeClass("loading");
	$(".spamreport-button").addClass("success");
	$(".spamreport-button").attr("title", "<t>You already reported this spot as spam</t>");

    // close the dialog of the report reason
    var $dialdiv = $("#editdialogdiv");
    $dialdiv.dialog('close');
    $dialdiv.empty();
} // postReportUiDone

function postSpotUiStart() {
	$("div.newspotdiv > div").css({
		width: $("div.newspotdiv").width(),
		height: $("div.newspotdiv").height()
	});
	$("div.newspotdiv > div").addClass("loading").show();
} // postSpotUiStart()

function postSpotUiDone() {
	$("div.newspotdiv > div ").removeClass("loading").hide();
} // postSpotUiDone()
 
