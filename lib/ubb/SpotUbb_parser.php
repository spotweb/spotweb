<?php
/**
 * ToDo: 
 *    Noubb tag handling
 *    closing tags van config zouden gechecked moeten worden
 *    ...
 */


class SpotUbb_parser {
	/*
 	 * Current parser state
	 */
	private $curpos = -1;
	private $inputstr = '';

	function __construct($inputstr) {
		$this->setinputstring($inputstr);
	} // ctor UbbParse

	/*
	 * Sets the input string and resets current cursor pos 
	 */
	function setinputstring($inputstr) {
		$this->curpos = -1;
		$this->inputstr = $inputstr;
	} // setinputstring

	/**
	 * Returns true when there is a new character available
	 */
	function hasnextch() {
		return ($this->curpos < (strlen($this->inputstr) - 1));
	} // func. hasnextch

	/** 
	 * Seeks one character back in input string
	 */
	function seekback() {
		$this->curpos--;
	} // func. seekback()

	/**
	 * Returns the next character, but don't improve the 'cursor'
	 */
	function peekch() {
		if (!$this->hasnextch()) {
			return false;
		} // if

		return $this->inputstr[$this->curpos+1];
	} // func. peekch()

	/**
	 * Returns the next character in line, or false when EOS 
	 */
	function nextch() {
		if ($this->curpos >= (strlen($this->inputstr) - 1)) {
			return false;
		} // if

		$this->curpos++;
		return $this->inputstr[$this->curpos];
	} // nextch()

	/**
	 * Returns current position in string
	 */
	function getpos() {
		return $this->curpos;
	} // func. getpos()

	/**
	 * Is current character the opening of an UBB tag?
	 */
	function startofubbtag() {
		return ($this->inputstr[$this->curpos] == '[');
	} // func startofubbtag()

	/**
	 * Is current character the closing of an UBB tag?
	 */
	function endofubbtag() {
		return (substr($this->inputstr, $this->curpos, 2) == '[/');
	} // func endofubbtag()

	/**
	 * Returns the contents of the closing tag (only the closing tag)
	 */
	function fetchendtag() {
		$endtag = '';
		$this->nextch(); // skip the slash 

		while ($this->hasnextch()) {
			$ch = $this->nextch();

			if ($ch != ']') {
				$endtag .= $ch;
			} else {
				break;
			} // end tag is complete

		} // while

		return $endtag;	
	} // func. endtag()


	/**
	 * Returns the contents of the given parameters
	 */
	function fetchparams($namedparams) {
		$paramstr = '';

		while (($this->hasnextch()) && ($this->peekch() != ']')) {
			$ch = $this->nextch();

			$paramstr .= $ch; 
		} // while

		/* we override the $namedparams because the old generator allowed 
                   named parameters, while the first character indicated it didn't */
		if (!$namedparams) {
			$namedparams = (strpos($paramstr, '='));
		} // if

		/* if we were supposed to get named params, parse them into an array */
		if ($namedparams) {
			/* First split all strings on spaces -- not 100% correct 
			   (what if we get a quoted string with spaces..? but will do 
			   for now */
			$pairs = explode(' ', $paramstr);
			foreach($pairs as $key) {
				$tmp = explode('=', $key);

				if (count($tmp) > 1) {
					$params[$tmp[0]] = $tmp[1];
				} else {
					$params[] = $tmp[0];
				} // else
			} // foreach
		} else {
			$params = explode(',', $paramstr);
		} // else

		return Array('arenamedparams' => $namedparams,
			     'originalparams' => ($namedparams ? ' ' : '=') . $paramstr,
			     'params' => $params); 
	} // func. fetchparams()

	/**
	 * Returns the contents of the opening tag (only the opening tag) 
	 */
	function fetchopeningtag() {
		$tmp['tagposition'] = ($this->getpos() + 1);
		$tmp['tagname'] = '';
		$tmp['params'] = Array('arenamedparams' => FALSE, 
					'originalparams' => '',
					'params' => '');

		/* Get the tag name, tagname ends upon either an ] or a space */
		while ($this->hasnextch()) {
			$ch = $this->nextch();

			/* tag is being closed, be done with it */
			if ($ch == ']') {
				break;
			} // if

			/* tag is getting parameters, be happy ... */
			if (($ch == ' ') || ($ch == '=')) {
				$tmp['params'] = $this->fetchparams(($ch != '='));
			} else {
				$tmp['tagname'] .= $ch;
			} // else

		} // while

		return $tmp;	
	} // func. fetchopeningtag()

	/**
	 * return new content array
	 */
	function newcontent() {
		return Array('tagname' => '', 
			     'tagposition' => $this->getpos(),
			     'params' => Array('arenamedparams' => FALSE, 
					       'originalparams' => '',
					       'params' => ''),
			     'content' => '');
	} // func. newcontent()


	/**
	 * returns wether the given element is an empty element
	 */
	function nonemptycontent($content) {
		return !( empty($content['tagname']) && 
			 empty($content['params']) &&
			 empty($content['content']) );
	} // func. emptycontent
 
	/**
	 * Tokenize (...) an UBB string
	 */
	function tokenize($nowtag = array() ) {
		$curcnt = 0;
		$tagcfg = NULL;
		$contents[$curcnt] = $this->newcontent();

		if (!empty($nowtag)) {
			$tagcfg = TagHandler::gettagconfig($nowtag['tagname']);
		} // if

		/* We enter this function when the current character was an
		 * opening brace 
		 */
		while ($this->hasnextch()) {
			$ch = $this->nextch();

			if (($this->endofubbtag()) && (!empty($nowtag))) {
				/* Now make sure the current tag, has to be closed, else.. well.. */
				if (($tagcfg !== NULL) && ($tagcfg['closetags'] === NULL)) {
					# :echo 'Closing tag found for non-closing code' . "\r\n";
					# var_dump($tagcfg);
					# var_dump($nowtag);

					$this->seekback();
					break;
				} // if

				$endtag = $this->fetchendtag();
				if (array_search($endtag, $tagcfg['closetags']) !== FALSE) {
					break; // tag is complete
				} else { 
					/* Not the proper closing tag, just append it again */
					$contents[$curcnt]['content'] .= '[/' . $endtag . ']';
				} // else
			
			} else if ($this->startofubbtag()) {
				if (($tagcfg !== NULL) && ($tagcfg['closetags'] === Array(NULL))) {
					$this->seekback();
					break;
				} // if

				$tmptag = $this->fetchopeningtag();

				/* To properly process this tag, it should not be null */
				if (TagHandler::gettagconfig($tmptag['tagname']) !== NULL) {
					$tmptag['content'] = $this->tokenize($tmptag);
					$contents[++$curcnt] = $tmptag;

					/* and be done with this tag */
					$contents[++$curcnt] = $this->newcontent();
				} else {
					/* Not a proper tag, lets see if we can reconstruct it and ignore it */
					$contents[$curcnt]['content'] .= '[' . $tmptag['tagname'];

					/* add the parameters (if any) back */ 
					if ($tmptag['params'] !== '') {
						$contents[$curcnt]['content'] .= $tmptag['params']['originalparams'];
					} // if

					/* and add the closing bracket */
					$contents[$curcnt]['content'] .= ']';
				} // else
			} else {
				$contents[$curcnt]['content'] .= $ch;
			} // else
 
		} // while

		/* and return the result set */
		return $contents;
	} // func tokenize()

	/**
	 * Returns the formatted UBB
	 */
	function converttoubb($parseresult, $allowedchildren = Array(null)) {
		$output = Array('');
		$bodycount = 0;

		$parseResultCount = sizeof($parseresult);
		for($i = 0; $i < $parseResultCount; $i++) {
			/* save the current allowedchildren */
			$saveallowedchildren = $allowedchildren;

			/* first get the tag results */
			if ($parseresult[$i]['tagname'] !== '') {
				$tagresult = NULL;

				/* Get the configuration for this tag, as it also includes the function
				 * to call when processing this tag */
				$tagconfig = TagHandler::gettagconfig($parseresult[$i]['tagname']);

				/* Are we allowed to run this tag? */
				if (($allowedchildren[0] === NULL) | 
					(array_search($parseresult[$i]['tagname'], $allowedchildren))) {

					$tagresult = TagHandler::process_tag($parseresult[$i]['tagname'],
									$parseresult[$i]['params'],
									$parseresult[$i]['content']);
				} // if 

				/* If tag result was NULL, the given tag is invalid, so reconstruct it */
				if ($tagresult === NULL) {
					if ($tagconfig['closetags'] !== Array(NULL)) {
						$appendclosetag = '[/' . $parseresult[$i]['tagname'] . ']';
					} else {
						$appendclosetag = '';
					} // else

					/* add the tag back in */
					$tagresult = Array('prepend' => '[' . $parseresult[$i]['tagname'] .
								$parseresult[$i]['params']['originalparams'] . ']',
							   'append' => $appendclosetag );

				} else {
					/* allow the tags to overwrite the content */
					$parseresult[$i]['content'] = $tagresult['content'];
				} // else

				/* Now append the allowed children */
				if ($tagconfig['allowedchildren'][0] === '') {
					/* deny all child tags */
					$allowedchildren = Array('');
				} else if ($tagconfig['allowedchildren'][0] !== NULL) {
					/* all tags are allowed */
					$allowedchildren = array_intersect($allowedchildren, $tagconfig['allowedchildren']);
				} // else 


			} else {
				$tagresult = Array('prepend' => '',
						   'append' => '');
			} // else

			$output[$bodycount] .= $tagresult['prepend'];
			if (is_array($parseresult[$i]['content'])) {
				$parsedchildcontent = $this->converttoubb($parseresult[$i]['content'], $allowedchildren);

				$output[$bodycount] .= $parsedchildcontent[0];
				if (count($parsedchildcontent) > 1) {
					array_shift($parsedchildcontent);
					$output = array_merge($output, $parsedchildcontent);
				} // if
			} else {
				$output[$bodycount] .= $parseresult[$i]['content'];
			} // else
			$output[$bodycount] .= $tagresult['append'];

			/* restore the saved allowed children */
			$allowedchildren = $saveallowedchildren;
		} // for

		return $output;
	} // converttoubb

	function parse() {
		$parseresult = $this->tokenize();
		return $this->converttoubb($parseresult);
	} // func. parse()
	
} # class SpotUbb_Parse
