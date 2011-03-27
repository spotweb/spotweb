<?php
require_once "lib/dbeng/db_abs.php";

class db_pdo extends db_abs {
	
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
        }
        
		$pattern = '/(\'?\%[ds]\'?)/';
        $matches = array();
        preg_match_all($pattern, $s, $matches);
        $s = preg_replace($pattern, '?', $s);
        
		$stmt = $this->_conn->prepare($s);
        $idx=1;
        foreach ($matches[1] as $m) {
            if (!isset($p[$idx-1])) {
                break;
            }
            if ($p[$idx-1] == null) {
                $stmt->bindValue($idx, '', PDO::PARAM_STR);
            } else {
                switch ($m) {
                    case '%d':
                        $stmt->bindParam($idx, $p[$idx-1], PDO::PARAM_INT);
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
}
?>