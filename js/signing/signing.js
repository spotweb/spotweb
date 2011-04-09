//
// We breken de make expensive hash op in stukken omdat 
// anders IE8 gaat zeuren over scripts die te lang lopen.
//
// De utility functie komt van 
//    http://www.picnet.com.au/blogs/Guido/post/2010/03/04/How-to-prevent-Stop-running-this-script-message-in-browsers.aspx
//
RepeatingOperation = function(op, yieldEveryIteration) {
	var count = 0;
	var runCount = 0;
	var instance = this;
	this.step = function(arg1,arg2) { 
		// De step functie controleert feitelijk of we door
		// gaan in de huidige 'thread' (setTimeOut() zien we even als
		// thread), of dat we een nieuwe thread starten en de vorige stopzetten.
		//
		// Omdat IE aantal operaties per thread telt, kunnen we hier mee de 
		// 'wow your script is slow'-waarschuwing omzeilen
		//
		runCount++;
		if (++count >= yieldEveryIteration) { 
			count = 0;
			setTimeout(function() { op(arg1,arg2,runCount); }, 1, [])
			return;
		} // if
		
		op(arg1,arg2,runCount);
	}; // function
}; // RepeatingOperation

var ro = new RepeatingOperation(function(prefix, suffix, runCount) {
	var possibleChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	var hash = prefix + suffix;

	uniquePart = '';
	for(i = 0; i < 15; i++) {
		var irand = Math.round(Math.random() * (possibleChars.length - 1));
		uniquePart += possibleChars[irand];
	} // for
	
	hash = $.sha1(prefix + '.' + uniquePart + suffix);
	
	if (hash.substr(0, 4) != '0000') {
		if (runCount > 400000) {
			alert("Unable to calculate SHA1 hash: " + runCount);
		} else {
			ro.step(prefix, suffix, runCount);
			return ;
		} // else
	} // if
	
	alert(prefix + '.' + uniquePart + suffix + ' ==> ' + runCount);
	alert($.sha1(prefix + '.' + uniquePart + suffix));
}, 500); // new RepeatingOperation met 10000 iteraties
	
// ro.step("<PqPs1LvROTU9PpfTQALQI.0.random", "@spot.net>", 0);
