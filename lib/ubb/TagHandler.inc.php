<?php

    class TagHandler
    {
        /*
         * Denied tags -- used to be able to process
         * all this in two passes (eg: used for tags needing information for other stuff)
         */
        private static $deniedtags = [];

        /*
        * UBB tag configuration params
        */
        public static $tagconfig =
            [
                /* ------- b -------------------- */
                'b'	=> ['b' => ['closetags' => ['b'],
                    'allowedchildren'       => [null],
                    'handler'               => ['TagHandler', 'handle_bold'], ],
                    'br' => ['closetags'    => [null],
                        'allowedchildren'   => [''],
                        'handler'           => ['TagHandler', 'handle_br'], ],
                ],

                /* ------- i -------------------- */
                'i'	=> ['i' => ['closetags' => ['i'],
                    'allowedchildren'       => [null],
                    'handler'               => ['TagHandler', 'handle_italic'], ],

                    'img' => ['closetags' => [null],
                        'allowedchildren' => [''],
                        'handler'         => ['TagHandler', 'handle_img'], ],

                ],

                /* ------- u ------------------- */
                'u'	=> ['u' => ['closetags' => ['u'],
                    'allowedchildren'       => [null],
                    'handler'               => ['TagHandler', 'handle_underline'], ],

                    'url' => ['closetags' => ['url'],
                        'allowedchildren' => [''],
                        'handler'         => ['TagHandler', 'handle_url'], ],

                ],

                /* ------- q ------------------- */
                'q'	=> ['quote' => ['closetags' => ['quote'],
                    'allowedchildren'           => [null],
                    'handler'                   => ['TagHandler', 'handle_quote'], ],
                    //)
                ],

                /* ------- y ------------------- */
                'y'	=> ['youtube' => ['closetags' => ['youtube'],
                    'allowedchildren'             => [null],
                    'handler'                     => ['TagHandler', 'handle_youtube'], ],
                ],
            ];

        /**
         * Returns the tag config for a given tag.
         */
        public static function gettagconfig($tagname)
        {
            if ((strlen($tagname) >= 1) && (isset(self::$tagconfig[$tagname[0]][$tagname]))) {
                return self::$tagconfig[$tagname[0]][$tagname];
            } else {
                return null;
            } // else
        }

        // gettagconfig

        /*
         * Add additional configuration for a tag
         */
        public static function setadditionalinfo($tagname, $name, $value)
        {
            self::$tagconfig[$tagname[0]][$tagname][$name] = $value;
        }

        // setadditionalinfo

        /*
         * Set the list of denied tags
         */
        public static function setdeniedtags($deniedtags)
        {
            self::$deniedtags = $deniedtags;
        }

        // setdeniedtags

        /*
         * Returns the list of denied tags
         */
        public static function getdeniedtags($deniedtags)
        {
            return self::$deniedtags;
        }

        // getdeniedtags

        /*
         * Processes an tag (when allowed)
         */
        public static function process_tag($tagname, $params, $contents)
        {
            if (array_search($tagname, self::$deniedtags) !== false) {
                return null;
            } // if denied tag

            if (isset(self::$tagconfig[$tagname[0]][$tagname]['handler'])) {
                return call_user_func_array(
                    self::$tagconfig[$tagname[0]][$tagname]['handler'],
                    [$params, $contents]
                );
            } else {
                // ??
            } // if
        }

        // process_tag

        /* Returns an empty append/prepend, used for deprecated tags */
        public static function handle_empty($params, $contents)
        {
            return ['prepend' => '', 'content' => $contents, 'append' => ''];
        }

        // func. handle_empty

        public static function handle_bold($params, $contents)
        {
            return ['prepend' => '<b>',
                'content'     => $contents,
                'append'      => '</b>', ];
        }

        // handle_bold

        public static function handle_underline($params, $contents)
        {
            return ['prepend' => '<u>',
                'content'     => $contents,
                'append'      => '</u>', ];
        }

        // handle_underline

        public static function handle_italic($params, $contents)
        {
            return ['prepend' => '<i>',
                'content'     => $contents,
                'append'      => '</i>', ];
        }

        // handle_italic

        /* Handles [br] */
        public static function handle_br($params, $contents)
        {
            return ['prepend' => '<br>',
                'content'     => $contents,
                'append'      => '', ];
        }

        // handle_br

        /* handle the img tag */
        public static function handle_img($params, $contents)
        {
            $origAppend = '';

            // are only specific images allowed?
            if (isset(self::$tagconfig['i']['img']['allowedimgs'])) {
                if (!isset(self::$tagconfig['i']['img']['allowedimgs'][$params['params'][0]])) {
                    return self::handle_empty($params, $contents);
                } else {
                    $origAppend = $contents;
                    $contents = self::$tagconfig['i']['img']['allowedimgs'][$params['params'][0]];
                } // if
            } // if

                return ['prepend' => '<img src="'.$contents.'">',
                    'content'     => $origAppend,
                    'append'      => '', ];
        }

        // handle_img

        /* handle the quote tag */
        public static function handle_quote($params, $contents)
        {
            // quote it
            return ['prepend' => '<blockquote><strong>'.sprintf(_('%s commented earlier:'), substr($params['originalparams'], 1)).'</strong><br>',
                'content'     => $contents,
                'append'      => '</blockquote>', ];
        }

        // handle_quote

        /* handle the img tag */
        public static function handle_url($params, $contents)
        {
            // are only specific images allowed?
            return ['prepend' => '<a href="'.substr($params['originalparams'], 1).'">',
                'content'     => $contents,
                'append'      => '</a>', ];
        }

        // handle_url

        public static function handle_youtube($params, $contents)
        {
            // are only specific images allowed?
            return ['prepend' => '<div style="max-width: 480px; clear: left"><div style="position: relative; height:0px; padding-bottom: 75%"><iframe style="position: absolute; top: 0px; left:0px; width: 100%; height: 100%" src="https://www.youtube.com/embed/',
                'content'     => $contents,
                'append'      => '" frameborder="0" allowfullscreen></iframe></div></div>', ];
        }

        // handle_url

        /* handle the noubb tag */
        public static function handle_noubb($params, $contents)
        {
            return ['prepend' => '',
                'content'     => $contents,
                'append'      => '', ];
        }

        // handle_noubb
    } // class TagHandler
