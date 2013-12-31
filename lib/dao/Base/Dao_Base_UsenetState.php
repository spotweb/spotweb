<?php

class Dao_Base_UsenetState implements Dao_UsenetState {
	protected $_conn;

	/*
	 * constructs a new Dao_Base_Cache object, 
	 * connection object is given
	 */
	public function __construct(dbeng_abs $conn) {
		$this->_conn = $conn;
	} # ctor

    /*
     * Make sure all records are actually created so we can
     * always just update instead of trying to insert/update.
     */
    function initialize() {
        /*
         * And create all infotype's in the database
         */
        foreach(array('Base', 'Spots', 'Comments', 'Reports') as $infoType) {

            $result = $this->_conn->arrayQuery("SELECT 1 FROM usenetstate WHERE infotype = :infotype",
                array(
                    ':infotype' => array($infoType, PDO::PARAM_STR)
                ));

            if (empty($result)) {
                $this->_conn->modify("INSERT INTO usenetstate(infotype) VALUES(:infotype)",
                    array(
                        ':infotype' => array($infoType, PDO::PARAM_STR)
                    ));
            } # if

        } # foreach

    } # initialize
	
	/* 
	 * Update of insert the maximum article id in de database.
	 */
	function setMaxArticleId($infoType, $articleNumber, $messageId) {
		$this->_conn->exec("UPDATE usenetstate SET curarticlenr = :curarticlenr, curmessageid = :curmessageid WHERE infotype = :infotype",
            array(
                ':curarticlenr' => array($articleNumber, PDO::PARAM_INT),
                ':curmessageid' => array($messageId, PDO::PARAM_STR),
                ':infotype' => array($infoType, PDO::PARAM_STR)
            ));
	} # setMaxArticleId()

	/*
	 * Retrieves the current article (of the NNTP server), if it doesn't
	 * exist yet, we create the record and return a 0
	 */
	function getLastArticleNumber($infoType) {
		return $this->_conn->singleQuery("SELECT curarticlenr FROM usenetstate WHERE infotype = :infotype",
            array(
                ':infotype' => array($infoType, PDO::PARAM_STR)
            ));
	} # getLastArticleNumber

    function getLastMessageId($infoType) {
        return $this->_conn->singleQuery("SELECT curmessageid FROM usenetstate WHERE infotype = :infotype",
            array(
                ':infotype' => array($infoType, PDO::PARAM_STR)
            ));
    } # getLastMessageId

    /*
     * Is the retriever already running?
     */
	function isRetrieverRunning() {
		$nowRunning = $this->_conn->singleQuery("SELECT nowrunning FROM usenetstate WHERE infotype = 'Base'");
		return ((!empty($nowRunning)) && ($nowRunning > (time() - 900)));
	} # isRetrieverRunning

	/*
	 * Marks the retriever as running
	 */
	function setRetrieverRunning($isRunning) {
		if ($isRunning) {
			$runTime = time();
		} else {
			$runTime = 0;
		} # if

		$this->_conn->exec("UPDATE usenetstate SET nowrunning = :nowrunning WHERE infotype = 'Base'",
            array(
                ':nowrunning' => array($runTime, PDO::PARAM_INT)
            ));
	} # setRetrieverRunning

	/*
	 * Updates the timestamp of the last run of the retriever
	 */
	function setLastUpdate($infoType) {
		return $this->_conn->modify("UPDATE usenetstate SET lastretrieved = :lastretrieved WHERE infotype = :infotype",
            array(
                ':lastretrieved' => array(time(), PDO::PARAM_INT),
                ':infotype' => array($infoType, PDO::PARAM_STR)
            ));
	} # getLastUpdate

	/*
	 * Returns the lastrun timestamp for the server
	 */
	function getLastUpdate($infoType) {
		return $this->_conn->singleQuery("SELECT lastretrieved FROM usenetstate WHERE infotype = :infotype",
            array(
                ':infotype' => array($infoType, PDO::PARAM_STR)
            ));
	} # getLastUpdate

} # Dao_Base_UsenetState
