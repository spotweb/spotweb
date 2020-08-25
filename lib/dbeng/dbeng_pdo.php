<?php

abstract class dbeng_pdo extends dbeng_abs
{
    protected $_rows_changed;

    /**
     * @param string $s
     * @param array  $p
     *
     * @throws Exception When PDO statement cannot be created
     *
     * @return PDOStatement
     */
    private function prepareSql($s, $p)
    {
        if (empty($p)) {
            return $this->_conn->prepare($s);
        } // if

        $stmt = $this->_conn->prepare($s);
        if (!$stmt instanceof PDOStatement) {
            $x = $stmt->errorInfo();

            throw new SqlErrorException(implode(': ', $x), -1);
        }

        /*
         * Bind all parameters/values to the statement
         */
        foreach ($p as $k => $v) {
            $stmt->bindValue($k, $v[0], $v[1]);
        } // foreach

        return $stmt;
    }

    public function rawExec($s)
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        try {
            $stmt = $this->_conn->query($s);
        } catch (PDOException $x) {
            throw new SqlErrorException(implode(': ', $x->errorInfo), -1);
        } // catch
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$s]);

        return $stmt;
    }

    /**
     * Execute the query and saves the rowcount in a property for later retrieval.
     *
     * @param string $s
     * @param array  $p
     *
     * @throws SqlErrorException SQL exception when an SQL error occurs during execution
     *
     * @return PDOStatement
     */
    public function exec($s, $p = [])
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        try {
            $stmt = $this->prepareSql($s, $p);
            $stmt->execute();
        } catch (PDOException $x) {
            throw new SqlErrorException(implode(': ', $x->errorInfo), -1);
        } // catch
        $this->_rows_changed = $stmt->rowCount();
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$s, $p]);

        return $stmt;
    }

    /*
     * INSERT or UPDATE statement, doesn't return anything. Exception
     * thrown if a error occurs
     */
    public function modify($s, $p = [])
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);

        $res = $this->exec($s, $p);
        $res->closeCursor();
        unset($res);

        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$s, $p]);
    }

    // modify

    /*
     * Begins an transaction
     */
    public function beginTransaction()
    {
        $this->_conn->beginTransaction();
    }

    // beginTransaction

    /*
     * Commits an transaction
     */
    public function commit()
    {
        $this->_conn->commit();
    }

    // commit

    /*
     * Rolls back an transaction
     */
    public function rollback()
    {
        $this->_conn->rollback();
    }

    // rollback

    public function rows()
    {
        return $this->_rows_changed;
    }

    // rows()

    public function lastInsertId($tableName)
    {
        return $this->_conn->lastInsertId($tableName.'_id_seq');
    }

    // lastInsertId

    /**
     * Executes the query with $params as parameters. All parameters are
     * parsed through sthe safe() function to prevent SQL injection.
     *
     * Returns a single associative array when query succeeds, returns
     * an exception when the query fails.
     *
     * @param array $s
     * @param array $p
     *
     * @return array
     */
    public function singleQuery($s, $p = [])
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);
        $stmt = $this->exec($s, $p);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        unset($stmt);
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$s, $p]);

        //return $row[0] ?? NULL; <-- Does not work for PHP5.6 in order to remain compatible we use this for now:
        if ($row) {
            return $row[0];
        } else {
            return null;
        }
    }

    // singleQuery

    /**
     * Executes the query with $params as parameters. All parameters are
     * parsed through sthe safe() function to prevent SQL injection.
     *
     *
     * Returns an array of associative arrays when query succeeds, returns
     * an exception when the query fails.
     *
     * @param string $s
     * @param array  $p
     *
     * @return array
     */
    public function arrayQuery($s, $p = [])
    {
        SpotTiming::start(__CLASS__.'::'.__FUNCTION__);
        $stmt = $this->exec($s, $p);
        $tmpArray = $stmt->fetchAll();
        $stmt->closeCursor();
        unset($stmt);
        SpotTiming::stop(__CLASS__.'::'.__FUNCTION__, [$s, $p]);

        return $tmpArray;
    }

    // arrayQuery

    /**
     * Escape a string for insertion in a query.
     *
     * @param $s
     *
     * @return string
     */
    public function safe($s)
    {
        if (is_int($s) || is_float($s)) {
            return $s;
        } else {
            return $this->_conn->quote($s);
        } // else
    }

    // safe

    /*
     * Transforms an array of values to an list usable by an
     * IN statement
     */
    public function batchInsert($ar, $sql, $typs, $fields)
    {
        $this->beginTransaction();

        /*
         * Sanity check
         */
        if (count($typs) != count($fields)) {
            exit('SQL wrong. Nr of types='.count($typs).' nr of fields='.count($fields).' sql='.$sql);
        } // if

        /*
         * Databases usually have a maximum packet size length,
         * so just sending down 100kbyte of text usually ends
         * up in tears.
         */
        $chunks = array_chunk($ar, $this->_batchInsertChunks);
        foreach ($chunks as $items) {
            $insertArray = [];

            /*
             * The amount of placeholders might change
             * between the first N chunks and the last one
             * so we need to prepare it
             */
            $placeHolderPerRow = '('.substr(str_repeat('?,', count($fields)), 0, -1).'),';
            $placeHolders = substr(str_repeat($placeHolderPerRow, count($items)), 0, -1);

            $stmt = $this->_conn->prepare($sql.$placeHolders);
            if (!$stmt instanceof PDOStatement) {
                $x = $stmt->errorInfo();

                throw new SqlErrorException(implode(': ', $x), -1);
            } // if

            foreach ($items as $item) {
                /*
                 * Add this items' fields to an array in
                 * the correct order and nicely escaped
                 * from any injection
                 */
                foreach ($fields as $field) {
                    array_push($insertArray, $item[$field]);
                } // foreach
            } // foreach

            // Actually insert the batch
            if (!empty($insertArray)) {
                try {
                    $stmt->execute($insertArray);
                } catch (PDOException $x) {
                    throw new SqlErrorException(implode(': ', $x->errorInfo), -1);
                } // catch
            } // if
        } // foreach
        $this->commit();
    }

    // batchInsert
} // class
