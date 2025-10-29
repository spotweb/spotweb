<?php

class dbfts_sqlite extends dbfts_abs
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
         * parser used for SQLite by us, so for now we strip those.
         */
        if (strpos('+-~<>', $searchTerm[0]) !== false) {
            $searchTerm = substr($searchTerm, 1);
        } // if

        $searchTerm = str_replace(
            ['-', '+'],
            [' NOT ', ' AND '],
            $searchTerm
        );
        if ($searchTerm[0] !== '"') {
            $searchTerm = '"'.$searchTerm.'"';
        }

        return $searchTerm;
    }

    /*
     * Constructs a query part to match textfields. Abstracted so we can use
     * a database specific FTS engine if one is provided by the DBMS
     */
    public function createTextQuery($searchFields, $additionalFields)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);
        //var_dump($searchFields);
        /*
         * Initialize some basic values which are used as return values to
         * make sure always return a valid set
         */
        $fieldIndex = ['title' => 1, 'poster' => 2, 'tag' => 3];

        // Determine additional table to use
        $tmpField = explode('.', $searchFields[0]['fieldname']);
        $columnField = strtolower($tmpField[1]);

        $idxnum = $fieldIndex[$columnField];
        if (empty($idxnum)) {
            throw new NotImplementedException('Undefined SQLite FTS column: '.$columnField);
        }

        $additionalTables = [' idx_fts_spots AS idx_fts_spots_'.$idxnum];
        $filterValueSql = [];
        $matchList = [];

        /*
         * sqlite can only use one WHERE clause for all textstring matches,
         * if you exceed this it throws an unrelated error and refuses the query
         * so we have to collapse all textqueries into one query
         */

        foreach ($searchFields as $searchItem) {
            $searchValue = trim($searchItem['value']);

            /*
             * Do some preparation for the searchvalue, test cases:
             *
             * +"Revolution (2012)" +"Season 2"
             */
            $searchValue = $this->prepareFtsQuery($searchValue);

            /*
             * The caller usually provides an expiciet table.fieldname
             * for the select, but sqlite doesn't recgnize this in its
             * MATCH statement so we remove it and hope there is no
             * ambiguity
             */
            $tmpField = explode('.', $searchItem['fieldname']);
            $field = $tmpField[1];
            $safeValue = $this->_db->safe($searchValue);
            $matchList[] = $field.':'.substr($safeValue, 1, -1);
        } // foreach

        // add one WHERE MATCH conditions with all conditions

        $filterValueSql[] = '(idx_fts_spots_'.$idxnum.'.rowid = s.rowid) AND '.' (idx_fts_spots_'.$idxnum.'.'.$columnField." MATCH '".implode(' ', $matchList)."') ";

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$filterValueSql, $additionalTables]);

        return ['filterValueSql' => $filterValueSql,
            'additionalTables'   => $additionalTables,
            'additionalFields'   => $additionalFields,
            'sortFields'         => [], ];
    }

    // createTextQuery()
} // dbfts_sqlite
