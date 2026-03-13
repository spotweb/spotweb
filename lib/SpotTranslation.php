<?php

/*
 * Translation code for Spotweb
 */
class SpotTranslation
{
    private static function getLocaleCandidates($lang)
    {
        $localeCandidates = [
            $lang.'.UTF-8',
            $lang.'.utf8',
            $lang,
        ];

        $windowsLocales = [
            'en_US' => ['English_United States.1252'],
            'nl_NL' => ['Dutch_Netherlands.1252'],
            'fr_FR' => ['French_France.1252'],
            'tr_TR' => ['Turkish_Turkey.1254'],
        ];

        if (isset($windowsLocales[$lang])) {
            $localeCandidates = array_merge($localeCandidates, $windowsLocales[$lang]);
        } // if

        if ($lang === 'en_US') {
            $localeCandidates[] = 'C';
            $localeCandidates[] = 'POSIX';
        } // if

        return array_values(array_unique($localeCandidates));
    }

    // getLocaleCandidates

    private static function hasTranslationCatalog($lang)
    {
        return is_file(__DIR__.'/../locales/'.$lang.'/LC_MESSAGES/messages.mo');
    }

    // hasTranslationCatalog

    public static function initialize($lang)
    {
        /*
         * Do we have native gettext? We also check to see if this function exists,
         * because if the gettext module fails to load, this function will not exist.
         * See GitHub issue #1696
         */
        if (extension_loaded('gettext') && function_exists('bind_textdomain_codeset')) {
            $localePath = realpath(__DIR__.'/../locales');
            $localeCandidates = self::getLocaleCandidates($lang);
            $textDomain = self::hasTranslationCatalog($lang) ? 'messages' : 'messages-'.$lang;

            putenv('LANGUAGE='.$lang);
            putenv('LANG='.$lang);
            putenv('LC_ALL='.$localeCandidates[0]);
            setlocale(LC_ALL, $localeCandidates);

            // Initialize the textdomain
            bindtextdomain($textDomain, ($localePath !== false) ? $localePath : 'locales/');
            bind_textdomain_codeset($textDomain, 'UTF-8');
            textdomain($textDomain);
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
