<?php
/*
 * Copyright (c) 2009 David Soria Parra
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.



 * Gettext implementation in PHP.
 *
 * @copyright (c) 2009 David Soria Parra <sn_@gmx.net>
 * @author David Soria Parra <sn_@gmx.net>
 */
class Gettext_PHP extends SpotGettext
{
    /*
     * First magic word in the MO header.
     */
    const MAGIC1 = 0xde120495;

    /*
     * First magic word in the MO header.
     */
    const MAGIC2 = 0x950412de;

    protected $dir;
    protected $domain;
    protected $locale;
    protected $translationTable = [];
    protected $parsed = [];

    /*
     * Initialize a new gettext class.
     *
     * @param string $mofile The file to parse
     */
    public function __construct($directory, $domain, $locale)
    {
        $this->dir = $directory;
        $this->domain = $domain;
        $this->locale = $locale;
    }

    /*
     * Parse the MO file header and returns the table
     * offsets as described in the file header.
     *
     * If an exception occured, null is returned. This is intentionally
     * as we need to get close to ext/gettexts beahvior.
     *
     * @param Resource $fp The open file handler to the MO file
     *
     * @return An array of offset
     */
    private function parseHeader($fp)
    {
        $data = fread($fp, 8);
        $header = unpack('lmagic/lrevision', $data);

        if ((int) self::MAGIC1 != $header['magic']
           && (int) self::MAGIC2 != $header['magic']) {
            return null;
        }

        if (0 != $header['revision']) {
            return null;
        }

        $data = fread($fp, 4 * 5);
        $offsets = unpack('lnum_strings/lorig_offset/'
                          .'ltrans_offset/lhash_size/lhash_offset', $data);

        return $offsets;
    }

    /*
     * Parse and returns the string offsets in a table. Two table's can be found in
     * a mo file. The table with the translations and the table with the original
     * strings. Both contain offsets to the strings in the file.
     *
     * If an exception occured, null is returned. This is intentionally
     * as we need to get close to ext/gettexts beahvior.
     *
     * @param Resource $fp     The open file handler to the MO file
     * @param int       $offset The offset to the table that should be parsed
     * @param int       $num    The number of strings to parse
     *
     * @return array of offsets
     */
    private function parseOffsetTable($fp, $offset, $num)
    {
        if (fseek($fp, $offset, SEEK_SET) < 0) {
            return null;
        }

        $table = [];
        for ($i = 0; $i < $num; $i++) {
            $data = fread($fp, 8);
            $table[] = unpack('lsize/loffset', $data);
        }

        return $table;
    }

    /*
     * Parse a string as referenced by an table. Returns an
     * array with the actual string.
     *
     * @param Resource $fp    The open file handler to the MO fie
     * @param array     $entry The entry as parsed by parseOffsetTable()
     *
     * @return Parsed string
     */
    private function parseEntry($fp, $entry)
    {
        if (is_array($entry) && (fseek($fp, $entry['offset'], SEEK_SET) < 0)) {
            return null;
        }
        if (is_array($entry) && ($entry['size'] > 0)) {
            return fread($fp, $entry['size']);
        }

        return '';
    }

    /*
     * Parse the MO file.
     *
     * @return void
     */
    private function parse($locale, $domain)
    {
        $this->translationTable[$locale][$domain] = [];
        $mofile = sprintf('%s/%s/LC_MESSAGES/%s.mo', $this->dir, $locale, $domain);
        $cachefile = sprintf('%s/%s/LC_MESSAGES/%s.ser', $this->dir, $locale, $domain);

        if (!file_exists($mofile)) {
            $this->parsed[$locale][$domain] = true;

            return;
        }

        $filesize = filesize($mofile);
        if ($filesize < 4 * 7) {
            $this->parsed[$locale][$domain] = true;

            return;
        }

        if (($tmpobj = @file_get_contents($cachefile)) === false || @filemtime($cachefile) < filemtime($mofile)) {
            /* check for filesize */
            $fp = fopen($mofile, 'rb');

            if (is_resource($fp)) {
                $offsets = $this->parseHeader($fp);
                if (null == $offsets || $filesize < 4 * ($offsets['num_strings'] + 7)) {
                    fclose($fp);

                    return;
                }

                $transTable = [];
                $table = $this->parseOffsetTable(
                    $fp,
                    $offsets['trans_offset'],
                    $offsets['num_strings']
                );
                if (null == $table) {
                    fclose($fp);

                    return;
                }

                foreach ($table as $idx => $entry) {
                    $transTable[$idx] = $this->parseEntry($fp, $entry);
                }

                $table = $this->parseOffsetTable(
                    $fp,
                    $offsets['orig_offset'],
                    $offsets['num_strings']
                );
                foreach ($table as $idx => $entry) {
                    $entry = $this->parseEntry($fp, $entry);

                    $formes = explode(chr(0), $entry);
                    $translation = explode(chr(0), $transTable[$idx]);
                    foreach ($formes as $form) {
                        $this->translationTable[$locale][$domain][$form] = $translation;
                    }
                }

                /** @scrutinizer ignore-unhandled */@file_put_contents($cachefile, serialize($this->translationTable[$locale][$domain]));
                $fileput = file_put_contents($cachefile, serialize($this->translationTable[$locale][$domain]));
                if ($fileput === false) {
                    throw new Exception('Unable to write file to given location: '.$fileput);
                }
            } else {
                return;
            }
            fclose($fp);
        } else {
            $this->translationTable[$locale][$domain] = unserialize($tmpobj);
        }
        $this->parsed[$locale][$domain] = true;
    }

    /*
     * Return a translated string.
     *
     * If the translation is not found, the original passed message
     * will be returned.
     *
     * @return Translated message
     */
    public function gettext($msg)
    {
        if (!@$this->parsed[$this->locale][$this->domain]) {
            $this->parse($this->locale, $this->domain);
        }

        if (array_key_exists($msg, $this->translationTable[$this->locale][$this->domain])) {
            return $this->translationTable[$this->locale][$this->domain][$msg][0];
        }

        return $msg;
    }

    /*
     * Overrides the domain for a single lookup.
     *
     * If the translation is not found, the original passed message
     * will be returned.
     *
     * @param string $domain The domain to search in
     * @param string $msg    The message to search for
     *
     * @return Translated string
     */
    public function dgettext($domain, $msg)
    {
        if (!@$this->parsed[$this->locale][$domain]) {
            $this->parse($this->locale, $domain);
        }

        if (array_key_exists($msg, $this->translationTable[$this->locale][$domain])) {
            return $this->translationTable[$this->locale][$domain][$msg][0];
        }

        return $msg;
    }

    /*
     * Return a translated string in it's plural form.
     *
     * Returns the given $count (e.g second, third,...) plural form of the
     * given string. If the id is not found and $num == 1 $msg is returned,
     * otherwise $msg_plural
     *
     * @param string $msg        The message to search for
     * @param string $msg_plural A fallback plural form
     * @param int    $count      Which plural form
     *
     * @return Translated string
     */
    public function ngettext($msg, $msg_plural, $count)
    {
        if (!@$this->parsed[$this->locale][$this->domain]) {
            $this->parse($this->locale, $this->domain);
        }

        $msg = (string) $msg;

        if (array_key_exists($msg, $this->translationTable[$this->locale][$this->domain])) {
            $translation = $this->translationTable[$this->locale][$this->domain][$msg];
            /* the gettext api expect an unsigned int, so we just fake 'cast' */
            if ($count <= 0 || count($translation) < $count) {
                $count = count($translation);
            }

            return $translation[$count - 1];
        }

        /* not found, handle count */
        if (1 == $count) {
            return $msg;
        } else {
            return $msg_plural;
        }
    }

    /*
     * Override the current domain for a single plural message lookup.
     *
     * Returns the given $count (e.g second, third,...) plural form of the
     * given string. If the id is not found and $num == 1 $msg is returned,
     * otherwise $msg_plural
     *
     * @param string $domain     The domain to search in
     * @param string $msg        The message to search for
     * @param string $msg_plural A fallback plural form
     * @param int    $count      Which plural form
     *
     * @return Translated string
     */
    public function dngettext($domain, $msg, $msg_plural, $count)
    {
        if (!@$this->parsed[$this->locale][$domain]) {
            $this->parse($this->locale, $domain);
        }

        $msg = (string) $msg;

        if (array_key_exists($msg, $this->translationTable[$this->locale][$domain])) {
            $translation = $this->translationTable[$this->locale][$domain][$msg];
            /* the gettext api expect an unsigned int, so we just fake 'cast' */
            if ($count <= 0 || count($translation) < $count) {
                $count = count($translation);
            }

            return $translation[$count - 1];
        }

        /* not found, handle count */
        if (1 == $count) {
            return $msg;
        } else {
            return $msg_plural;
        }
    }
}
