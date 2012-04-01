<?php
abstract class dbeng_pdo extends dbeng_abs {
	
	/**
     * We don't want to rewrite all queries, so a small parser is written
	 * which rewrites the queries. Ugly, but it works.
     *
     * @param string $s
     * @param array $p
     * @return PDOStatement
     */
	public function prepareSql($s, $p) {
		if (empty($p)) {
            return $this->_conn->prepare($s);
        } # if

		$pattern = '/(\'?\%[dsb]\'?)/';
        $matches = array();
        preg_match_all($pattern, $s, $matches);
        $s = preg_replace($pattern, '?', $s);

		$stmt = $this->_conn->prepare($s);
        $idx=1;
		$totalCount = count($p);
        foreach ($matches[1] as $m) {
			if ($idx > ($totalCount+1)) {
                break;
            } # if
			
            if (is_null($p[$idx-1])) {	
                $stmt->bindValue($idx, null, PDO::PARAM_NULL);
            } else {
                switch ($m) {
                    case '%d': {
						# We convet explicitly to strval because PDO changes a zero to an '' 
						$tmpVal = strval($p[$idx-1]);
                        $stmt->bindParam($idx, $tmpVal, PDO::PARAM_INT);
                        break;
					} 
					case "'%b'": {
						$stmt->bindParam($idx, $p[$idx-1], PDO::PARAM_LOB);
						break;
					} 
                    default: {
                        $stmt->bindParam($idx, $p[$idx-1], PDO::PARAM_STR);
					} 
                }
            }
            $idx++;
        }
		
		if (!$stmt instanceof PDOStatement) {
        	throw new Exception(print_r($stmt, true));
        }
        
        return $stmt;
	}
	public function rawExec($s) {
		SpotTiming::start(__FUNCTION__);
		try {
			$stmt = $this->_conn->query($s);
		} catch(PDOException $x) {
			throw new SqlErrorException( $x->errorInfo[0] . ': ' . $x->errorInfo[2], -1);
		} # catch
		SpotTiming::stop(__FUNCTION__,array($s));
		
		return $stmt;
	}

	/*
	 * Returns a database specific representation of a boolean value
	 */
	function bool2dt($b) {
		if ($b) {
			return '1';
		} # if
		
		return '0';
	} # bool2dt

	/**
     * Execute the query and saves the rowcount in a property for later retrieval
     *
     * @param string $s
     * @param array $p
     * @return PDOStatement
     */
    public function exec($s, $p = array()) {
		SpotTiming::start(__FUNCTION__);
		try {
			$stmt = $this->prepareSql($s, $p);
			$stmt->execute();
		} catch(PDOException $x) {
			throw new SqlErrorException( $x->errorInfo[0] . ': ' . $x->errorInfo[2], -1);
		} # catch
        $this->_rows_changed = $stmt->rowCount();
		SpotTiming::stop(__FUNCTION__, array($s, $p));
 
    	return $stmt;
    }

	/*
	 * INSERT or UPDATE statement, doesn't return anything. Exception 
	 * thrown if a error occurs
	 */
	function modify($s, $p = array()) {
		SpotTiming::start(__FUNCTION__);
		
		$res = $this->exec($s, $p);
        $res->closeCursor();
		unset($res);
		
		SpotTiming::stop(__FUNCTION__, array($s,$p));
	} # modify
	
	/* 
	 * Begins an transaction
	 */
	function beginTransaction() {
		$this->_conn->beginTransaction();
	} # beginTransaction
	
	/* 
	 * Commits an transaction
	 */
	function commit() {
		$this->_conn->commit();
	} # commit
	
	/* 
	 * Rolls back an transaction
	 */
	function rollback() {
		$this->_conn->rollback();
	} # rollback
	
    function rows() {
		return $this->_rows_changed;
	} # rows()

	function lastInsertId($tableName) {
		return $this->_conn->lastInsertId($tableName . "_id_seq");
	} # lastInsertId
	
	 /**
	 * Executes the query with $params as parameters. All parameters are 
	 * parsed through sthe safe() function to prevent SQL injection.
	 *
	 * Returns a single associative array when query succeeds, returns 
	 * an exception when the query fails.
	 *
     * @param array $s
     * @param array $p
     * @return array
     */
	function singleQuery($s, $p = array()) {
		SpotTiming::start(__FUNCTION__);
		$stmt = $this->exec($s, $p);
        $row = $stmt->fetch();
        $stmt->closeCursor();
		unset($stmt);
		SpotTiming::stop(__FUNCTION__, array($s,$p));
        
		return $row[0];
	} # singleQuery
	
	/**
	 * Executes the query with $params as parameters. All parameters are 
	 * parsed through sthe safe() function to prevent SQL injection.
	 *
	 *
	 * Returns an array of associative arrays when query succeeds, returns 
	 * an exception when the query fails.
	 *
     * @param string $s
     * @param array $p
     * @return array
     */
	function arrayQuery($s, $p = array()) {
		SpotTiming::start(__FUNCTION__);
		$stmt = $this->exec($s, $p);
		$tmpArray = $stmt->fetchAll();

        $stmt->closeCursor();
		unset($stmt);
		SpotTiming::stop(__FUNCTION__, array($s,$p));

		return $tmpArray;
	} # arrayQuery

} # class
