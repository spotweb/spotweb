<?php
abstract class dbeng_pdo extends dbeng_abs {
	
	/**
     * Om niet alle queries te hoeven herschrijven heb ik hier een kleine parser
     * ingebouwd die de queries herschrijft naar PDO formaat.
     *
     * De functie bindt ook alle parameters aan het statement met daarbij
     * behorende PDO::PARAM_*
     *
     * @param string $s
     * @param array $p
     * @return PDOStatement
     */
	public function prepareSql($s, $p) {
		if (empty($p)) {
            return $this->_conn->prepare($s);
        } # if
        
		$pattern = '/(\'?\%[ds]\'?)/';
        $matches = array();
        preg_match_all($pattern, $s, $matches);
        $s = preg_replace($pattern, '?', $s);
        
		$stmt = $this->_conn->prepare($s);
        $idx=1;
        foreach ($matches[1] as $m) {
            if (!isset($p[$idx-1])) {
                break;
            } # if
			
            if (is_null($p[$idx-1])) {	
                $stmt->bindValue($idx, null, PDO::PARAM_NULL);
            } else {
                switch ($m) {
                    case '%d':
						# we converteren expliciet naar strval, omdat PDO anders een 0 naar '' omzet
                        $stmt->bindParam($idx, strval($p[$idx-1]), PDO::PARAM_INT);
                        break;
                    default:
                        $stmt->bindParam($idx, $p[$idx-1], PDO::PARAM_STR);
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
		$stmt = $this->_conn->query($s);
		
		return $stmt;
	}
	
	/**
     * Deze functie voert het statement uit en plaatst het aantal rijen in
     * een var.
     *
     * @param string $s
     * @param array $p
     * @return PDOStatement
     */
    public function exec($s, $p = array()) {
        $stmt = $this->prepareSql($s, $p);
        $stmt->execute();
        $this->_rows_changed = $stmt->rowCount();
 
    	return $stmt;
    }

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
	 
	 /**
     * Fetch alleen het eerste resultaat
     * @param array $s
     * @param array $p
     * @return array
     */
	function singleQuery($s, $p = array()) {
		$stmt = $this->exec($s, $p);
        $row = $stmt->fetch();
        
		return $row[0];
	} # singleQuery
	
	/**
     * Fetch alle resultaten
     * @param string $s
     * @param array $p
     * @return array
     */
	function arrayQuery($s, $p = array()) {
		$stmt = $this->exec($s, $p);
		
		return $stmt->fetchAll();
	} # arrayQuery

	
} # class
