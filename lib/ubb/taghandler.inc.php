<?php
	class TagHandler {
		/*
		 * Denied tags -- used to be able to process
		 * all this in two passes (eg: used for tags needing information for other stuff)
		 */
		private static $deniedtags = Array();

		/*
		* UBB tag configuration params
		*/
		public static $tagconfig =
			Array(
			/* ------- b -------------------- */
				'b'	=>
				Array('b' =>
					Array('closetags' => Array('b'),
					      'allowedchildren' => Array(NULL),
					      'handler' => Array('TagHandler', 'handle_bold') ),
					  'br' =>
					Array('closetags' => Array(NULL),
					      'allowedchildren' => Array(''),
					      'handler' => Array('TagHandler', 'handle_br') )
				),

			/* ------- i -------------------- */
				'i'	=>
					Array('i' => 
						Array('closetags' => Array('i'),
							  'allowedchildren' => Array(NULL),
							  'handler' => Array('TagHandler', 'handle_italic') ),
							  

						  'img' =>
							Array('closetags' => Array('img'),
								  'allowedchildren' => Array(''),
								  'handler' => Array('TagHandler', 'handle_img') )
							  
				),

			/* ------- u ------------------- */
				'u'	=>
					Array('u' =>
						Array('closetags' => Array('u'),
							  'allowedchildren' => Array(NULL),
							  'handler' => Array('TagHandler', 'handle_underline') )
					)
                );

		/**
		* Returns the tag config for a given tag
		*/
		function gettagconfig($tagname) {
			if ((strlen($tagname) >= 1) && (isset(TagHandler::$tagconfig[$tagname[0]][$tagname]))) {
				return TagHandler::$tagconfig[$tagname[0]][$tagname];
			} else {
				return NULL;
			} // else
		} // gettagconfig

		/*
		 * Add additional configuration for a tag 
		 */
		function setadditionalinfo($tagname, $name, $value) {
			TagHandler::$tagconfig[$tagname[0]][$tagname][$name] = $value;
		} # setadditionalinfo

		/*
		 * Set the list of denied tags	
		 */
		function setdeniedtags($deniedtags) {
			TagHandler::$deniedtags = $deniedtags;
		} // setdeniedtags

		/*
		 * Returns the list of denied tags
		 */
		function getdeniedtags($deniedtags) {
			return TagHandler::$deniedtags;
		} // getdeniedtags

		/*
		 * Processes an tag (when allowed)
		 */
		function process_tag($tagname, $params, $contents) {
			if (array_search($tagname, TagHandler::$deniedtags) !== FALSE) {
					return NULL;
			} // if denied tag

			if (isset(TagHandler::$tagconfig[$tagname[0]][$tagname]['handler'])) {
				return call_user_func_array(TagHandler::$tagconfig[$tagname[0]][$tagname]['handler'],
							array($params, $contents));
			} else {
				// ??
			} # if
		} // process_tag

		/* Returns an empty append/prepend, used for deprecated tags */
		function handle_empty($params, $contents) {
			return Array('prepend' => '', 'content' => $contents, 'append' => '');
		} // func. handle_empty

		function handle_bold($params, $contents) {
			return Array('prepend' => '<b>',
				     'content' => $contents,
				     'append' => '</b>');
		} // handle_bold

		function handle_underline($params, $contents) {
			return Array('prepend' => '<u>',
				     'content' => $contents,
				     'append' => '</u>');
		} // handle_underline

		function handle_italic($params, $contents) {
			return Array('prepend' => '<i>',
				     'content' => $contents,
				     'append' => '</i>');
		} // handle_italic

		
		/* Handles [br] */
		function handle_br($params, $contents) {
			return Array('prepend' => '<br>',
				     'content' => $contents,
				     'append' => '');
		} // handle_br

		
		/* handle the img tag */
		function handle_img($params, $contents) {
				# are only specific images allowed?
				if (isset(TagHandler::$tagconfig['i']['img']['allowedimgs'])) {
					if (!isset(TagHandler::$tagconfig['i']['img']['allowedimgs'][$params['params'][0]])) { 
						return TagHandler::handle_empty($params, $contents);
					} else {
						$contents = TagHandler::$tagconfig['i']['img']['allowedimgs'][$params['params'][0]];
					} # if
				} # if
				
				return Array('prepend' => '<img src="',
							 'content' => $contents,
							 'append' => '">');
		} // handle_img

		/* handle the noubb tag */
		function handle_noubb($params, $contents) {
			return Array('prepend' => '',
				     'content' => $contents,
				     'append' => '');
		} // handle_noubb

	} // class TagHandler 
