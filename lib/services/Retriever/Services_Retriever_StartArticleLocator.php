<?php

/*
 * Finds the article number from which a first retrieve has to start, so a new
 * (empty) database does not have to process every article in the group that
 * was posted before the date the user wants to retrieve from.
 *
 * The group is scanned from the first article on, only looking at the Date
 * header, and we stop at the first article dated on or after the requested
 * date (minus a safety margin). No assumption is made about the order of the
 * dates: every article dated on or after that moment has an article number
 * at or above the returned number. Stray dates can therefore only make us
 * start earlier than strictly needed, never later.
 *
 * Articles without a parseable Date header are ignored, the retrievers skip
 * them too as they have no usable timestamp.
 */
class Services_Retriever_StartArticleLocator
{
    /*
     * Number of headers requested per XOVER
     */
    const ChunkSize = 20000;

    /*
     * The retrievers filter on the posted timestamp, which is set by the
     * poster. Start a little earlier to be safe against small clock differences.
     */
    const SafetyMargin = 172800;

    /*
     * Report progress once every this many chunks
     */
    const ProgressEvery = 50;

    private $_transport;
    private $_progress;

    /*
     * $transport needs to provide getOverview($first, $last), $progress is an
     * optional callable which is called with the current and the last article number
     */
    public function __construct($transport, $progress = null)
    {
        $this->_transport = $transport;
        $this->_progress = $progress;
    }

    /*
     * Returns the article number to start retrieving from
     */
    public function locate($first, $last, $newerThan)
    {
        $first = (int) $first;
        $last = (int) $last;

        if (((int) $newerThan <= 0) || ($last <= $first)) {
            return $first;
        } // if

        $cutoff = (int) $newerThan - self::SafetyMargin;
        $chunk = 0;
        // Keep the configured limit (0 on the command line), only restart its timer for every chunk
        $timeLimit = (int) ini_get('max_execution_time');

        for ($from = $first; $from <= $last; $from += self::ChunkSize) {
            if ($timeLimit > 0) {
                set_time_limit($timeLimit);
            } // if
            if ((($chunk++ % self::ProgressEvery) == 0) && is_callable($this->_progress)) {
                call_user_func($this->_progress, $from, $last);
            } // if

            $hdrList = $this->_transport->getOverview($from, min($from + self::ChunkSize - 1, $last));

            $found = null;
            foreach ($hdrList as $hdr) {
                $stamp = strtotime($hdr['Date']);

                if (($stamp !== false) && ($stamp >= $cutoff) && (($found === null) || ($hdr['Number'] < $found))) {
                    $found = (int) $hdr['Number'];
                } // if
            } // foreach

            if ($found !== null) {
                SpotDebug::msg(SpotDebug::DEBUG, 'StartArticleLocator found startarticle = '.$found);

                return $found;
            } // if
        } // for

        // nothing posted on or after the requested date
        return $last;
    }

    // locate
} // Services_Retriever_StartArticleLocator
