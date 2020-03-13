<?php

class dbfts_pgsql extends dbfts_abs
{
    /**
     * Prepare (mangle) an FTS query to make sure we can use them.
     *
     * @param $searchTerm
     *
     * @return string
     */
    private function prepareFtsQuery($searchTerm)
    {
        /*
         * + signs get incorrectly interpreted by the query
         * parser used for PostgreSQL by us, so for now we strip those.
         */
        if (strpos('+-~<>', $searchTerm[0]) !== false) {
            $searchTerm = substr($searchTerm, 1);
        } // if

        $searchTerm = str_replace(
            ['-', '+'],
            [' NOT ', ' AND '],
            $searchTerm
        );

        return $searchTerm;
    }

    // prepareFtsQuery

    /*
     * Constructs a query part to match textfields. Abstracted so we can use
     * a database specific FTS engine if one is provided by the DBMS
     */
    public function createTextQuery($searchFields, $additionalFields)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        /*
         * Initialize some basic values which are used as return values to
         * make sure always return a valid set
         */
        $filterValueSql = [];
        $sortFields = [];
        $addFields = [];

        foreach ($searchFields as $searchItem) {
            $searchValue = trim($searchItem['value']);
            $field = $searchItem['fieldname'];

            /*
             * if we get multiple textsearches, we sort them per order
             * in the system
             */
            $tmpSortCounter = count($additionalFields) + count($addFields);

            // Prepare the to_tsvector and to_tsquery strings
            $ts_vector = "to_tsvector('english',regexp_replace(".$field.",'(.)\\.(.)','\\1 \\2','g'))";

            /*
             * Inititialize Digital Stratum's FTS2 parser so we can
             * give the user somewhat normal search query parameters
             */
            $o_parse = new parse_model();
            $o_parse->debug = false;
            $o_parse->upper_op_only = true;
            $o_parse->use_prepared_sql = false;
            $o_parse->set_default_op('OR');

            /*
             * Do some preparation for the searchvalue, test cases:
             *
             * +"Revolution (2012)" +"Season 2"
             */
            $searchValue = $this->prepareFtsQuery($searchValue);

            /*
             * First try to the parse the query using this library,
             * if that fails, fall back to letting PostgreSQL crudely
             * parse it
             */
            if ($o_parse->parse($searchValue, $field) === false) {
                $ts_query = "plainto_tsquery('Dutch', ".$this->_db->safe(strtolower($searchValue).':*').')';
                $filterValueSql[] = ' '.$ts_vector.' @@ '.$ts_query;
                $addFields[] = ' ts_rank('.$ts_vector.', '.$ts_query.') AS searchrelevancy'.$tmpSortCounter;
            } else {
                $queryPart = [];

                if (!empty($o_parse->tsearch)) {
                    //$ts_query = "to_tsquery('Dutch', " . $this->_db->safe($o_parse->tsearch.":*") . ")";
                    $ts_query = "to_tsquery('english', ".$this->_db->safe($o_parse->tsearch).')';
                    $queryPart[] = ' '.$ts_vector.' @@ '.$ts_query;
                    $addFields[] = ' ts_rank('.$ts_vector.', '.$ts_query.') AS searchrelevancy'.$tmpSortCounter;
                } // if

                if (!empty($o_parse->ilike)) {
                    $re = '/(\'%\S+?)([ ])(\S+?%\')/';
                    $ne = preg_replace($re, '$1_$3', $o_parse->ilike);
                    $queryPart[] = $ne;
                } // if

                /*
                 * Add the textqueries with an AND per search term
                 */
                if (!empty($queryPart)) {
                    $filterValueSql[] = ' ('.implode(' AND ', $queryPart).') ';
                } // if
            } // else

            $sortFields[] = ['field' => 'searchrelevancy'.$tmpSortCounter,
                'direction'          => 'DESC',
                'autoadded'          => true,
                'friendlyname'       => null, ];
        } // foreach

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$filterValueSql, $addFields, $sortFields]);

        return ['filterValueSql' => $filterValueSql,
            'additionalTables'   => [],
            'additionalFields'   => $addFields,
            'sortFields'         => $sortFields, ];
    }

    // createTextQuery()
} // dbfts_abs
