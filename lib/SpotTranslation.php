<?php
/*
 * Translation code for Spotweb
 */
class SpotTranslation
{
    public static function initialize($lang)
    {
        /*
         * Do we have native gettext? We also check to see if this function exists,
         * because if the gettext module fails to load, this function will not exist.
         * See GitHub issue #1696
         */
        if (extension_loaded('gettext') && (function_exists('bind_textdomain_codeset'))) {
            putenv('LC_ALL='.$lang.'.UTF-8');
            setlocale(LC_ALL, $lang.'.UTF-8');

            // Initialize the textdomain
            bindtextdomain('messages', 'locales/');
            bind_textdomain_codeset('messages', 'UTF-8');
            textdomain('messages');
        } else {
            global $_gt_obj;
            $_gt_obj = new Gettext_PHP('locales', 'messages', $lang);
        } // else
    }

    // initialize
} // class SpotTranslation

/*
 * This is procedural code because we want these functions to
 * be in the global name space
 */
if (!extension_loaded('gettext') || (!function_exists('bind_textdomain_codeset'))) {
    function _($msg)
    {
        return $GLOBALS['_gt_obj']->gettext($msg);
    } // _ alias of gettext

    function gettext($msg)
    {
        return $GLOBALS['_gt_obj']->gettext($msg);
    } // gettext

    function dgettext($domain, $msg)
    {
        return $GLOBALS['_gt_obj']->dgettext($domain, $msg);
    } // dgettext

    function ngettext($msg, $msg_plural, $count)
    {
        return $GLOBALS['_gt_obj']->ngettext($msg, $msg_plural, $count);
    } // ngettext

    function dngettext($domain, $msg, $msg_plural, $count)
    {
        return $GLOBALS['_gt_obj']->dngettext($domain, $msg, $msg_plural, $count);
    } // dngettext
} // if
