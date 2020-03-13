<?php

class Dao_Base_UsenetState implements Dao_UsenetState
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Cache object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Make sure all records are actually created so we can
     * always just update instead of trying to insert/update.
     */
    public function initialize()
    {
        /*
         * And create all infotype's in the database
         */
        foreach (['Base', 'Spots', 'Comments', 'Reports'] as $infoType) {
            $result = $this->_conn->arrayQuery(
                'SELECT 1 FROM usenetstate WHERE infotype = :infotype',
                [
                    ':infotype' => [$infoType, PDO::PARAM_STR],
                ]
            );

            if (empty($result)) {
                $this->_conn->modify(
                    'INSERT INTO usenetstate(infotype) VALUES(:infotype)',
                    [
                        ':infotype' => [$infoType, PDO::PARAM_STR],
                    ]
                );
            } // if
        } // foreach
    }

    // initialize

    /*
     * Update of insert the maximum article id in de database.
     */
    public function setMaxArticleId($infoType, $articleNumber, $messageId)
    {
        $this->_conn->exec(
            'UPDATE usenetstate SET curarticlenr = :curarticlenr, curmessageid = :curmessageid WHERE infotype = :infotype',
            [
                ':curarticlenr' => [$articleNumber, PDO::PARAM_INT],
                ':curmessageid' => [$messageId, PDO::PARAM_STR],
                ':infotype'     => [$infoType, PDO::PARAM_STR],
            ]
        );
    }

    // setMaxArticleId()

    /*
     * Retrieves the current article (of the NNTP server), if it doesn't
     * exist yet, we create the record and return a 0
     */
    public function getLastArticleNumber($infoType)
    {
        return $this->_conn->singleQuery(
            'SELECT curarticlenr FROM usenetstate WHERE infotype = :infotype',
            [
                ':infotype' => [$infoType, PDO::PARAM_STR],
            ]
        );
    }

    // getLastArticleNumber

    public function getLastMessageId($infoType)
    {
        return $this->_conn->singleQuery(
            'SELECT curmessageid FROM usenetstate WHERE infotype = :infotype',
            [
                ':infotype' => [$infoType, PDO::PARAM_STR],
            ]
        );
    }

    // getLastMessageId

    /*
     * Is the retriever already running?
     */
    public function isRetrieverRunning()
    {
        $nowRunning = $this->_conn->singleQuery("SELECT nowrunning FROM usenetstate WHERE infotype = 'Base'");

        return (!empty($nowRunning)) && ($nowRunning > (time() - 900));
    }

    // isRetrieverRunning

    /*
     * Marks the retriever as running
     */
    public function setRetrieverRunning($isRunning)
    {
        if ($isRunning) {
            $runTime = time();
        } else {
            $runTime = 0;
        } // if

        $this->_conn->exec(
            "UPDATE usenetstate SET nowrunning = :nowrunning WHERE infotype = 'Base'",
            [
                ':nowrunning' => [$runTime, PDO::PARAM_INT],
            ]
        );
    }

    // setRetrieverRunning

    /*
     * Updates the timestamp of the last run of the retriever
     */
    public function setLastUpdate($infoType)
    {
        return $this->_conn->modify(
            'UPDATE usenetstate SET lastretrieved = :lastretrieved WHERE infotype = :infotype',
            [
                ':lastretrieved' => [time(), PDO::PARAM_INT],
                ':infotype'      => [$infoType, PDO::PARAM_STR],
            ]
        );
    }

    // getLastUpdate

    /*
     * Returns the lastrun timestamp for the server
     */
    public function getLastUpdate($infoType)
    {
        return $this->_conn->singleQuery(
            'SELECT lastretrieved FROM usenetstate WHERE infotype = :infotype',
            [
                ':infotype' => [$infoType, PDO::PARAM_STR],
            ]
        );
    }

    // getLastUpdate
} // Dao_Base_UsenetState
