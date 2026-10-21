<?php

/*
 * Finds the article number in a group from which on articles are posted
 * after a given date, so a first retrieve does not have to walk the whole
 * group starting at the very first article on the server.
 *
 * This is a binary search on the article numbers, using the Date header of
 * a small XOVER window as a probe. Posting dates are not strictly ordered
 * and articles can be missing, so the search errs on the early side: when in
 * doubt it starts earlier, never later. The retrievers still filter on the
 * actual spot timestamp, so starting a bit early costs a few extra headers.
 */
class Services_Retriever_StartArticleLocator
{
    /*
     * Number of headers requested per probe, this allows us to step over
     * expired or cancelled articles without an extra roundtrip
     */
    const ProbeSize = 50;

    /*
     * Search for a moment slightly before the requested date, as the Date
     * header is set by the poster and not every clock is correct
     */
    const SafetyMargin = 86400;

    private $_transport;

    public function __construct($transport)
    {
        $this->_transport = $transport;
    }

    /*
     * Returns the article number to start retrieving from. All articles
     * before the returned number are posted before $newerThan (minus the
     * safety margin), as far as the probes can tell.
     */
    public function locate($first, $last, $newerThan)
    {
        $first = (int) $first;
        $last = (int) $last;
        $cutoff = (int) $newerThan - self::SafetyMargin;

        if (($newerThan <= 0) || ($last - $first <= self::ProbeSize)) {
            return $first;
        } // if

        $low = $first;
        $high = $last;

        while ($high - $low > self::ProbeSize) {
            // Reset timelimit
            set_time_limit(120);

            $mid = $low + (int) (($high - $low) / 2);
            $probe = $this->probe($mid, min($mid + self::ProbeSize - 1, $high));

            /*
             * Only move the lower bound when we positively know this part
             * of the group is too old. An empty or undated window moves
             * the upper bound, so we can only end up starting too early.
             */
            if (($probe !== null) && ($probe['stamp'] < $cutoff)) {
                $low = $probe['number'];
            } else {
                $high = $mid;
            } // else
        } // while

        SpotDebug::msg(SpotDebug::DEBUG, 'StartArticleLocator found startarticle = '.$low);

        return $low;
    }

    // locate

    /*
     * Returns the article number and timestamp of the first header in the
     * range with a parseable Date header, or null if there is none
     */
    private function probe($first, $last)
    {
        $hdrList = $this->_transport->getOverview($first, $last);

        foreach ($hdrList as $hdr) {
            $stamp = strtotime($hdr['Date']);
            if (($stamp !== false) && ($hdr['Number'] >= $first)) {
                return ['number' => (int) $hdr['Number'], 'stamp' => $stamp];
            } // if
        } // foreach

        return null;
    }

    // probe
} // Services_Retriever_StartArticleLocator
