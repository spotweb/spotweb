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