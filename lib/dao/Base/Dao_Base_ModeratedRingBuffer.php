<?php

class Dao_Base_ModeratedRingBuffer implements Dao_ModeratedRingBuffer
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Audit object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /**
     * @param array $messageIds
     *
     * @return bool
     */
    public function addToRingBuffer(array $messageIds)
    {
        // Empty list provided? Exit
        if (count($messageIds) == 0) {
            return;
        } // if

        // match the ones we are going to add with these
        $msgIdList = $this->_conn->arrayKeyToIn($messageIds);
        $alreadyAddedList = $this->_conn->arrayQuery('SELECT messageid FROM moderatedringbuffer WHERE messageid IN ('.$msgIdList.')');

        // remove the messageid's we already have
        foreach ($alreadyAddedList as $alreadyAdded) {
            if (isset($messageIds[$alreadyAdded['messageid']])) {
                unset($messageIds[$alreadyAdded['messageid']]);
            } // if
        } // foreach

        // convert the list of messageids to something which can be used
        $idList = [];
        foreach ($messageIds as $k => $v) {
            $idList[] = ['messageid' => $k];
        } // foreach

        // prepare a list of IN values
        $this->_conn->batchInsert(
            $idList,
            'INSERT INTO moderatedringbuffer(messageid) VALUES ',
            [PDO::PARAM_STR],
            ['messageid']
        );
    }

    // addToRingBuffer

    /**
     * @param array $messageIds
     *
     * @return array
     */
    public function matchAgainst(array $messageIds)
    {
        // Empty list provided? Exit
        if (count($messageIds) == 0) {
            return;
        } // if

        /*
         * Prepare the list of messageid's we want to match
         */
        $msgIdList = $this->_conn->arrayValToIn($messageIds, 'Message-ID');
        $rs = $this->_conn->arrayQuery('SELECT messageid FROM moderatedringbuffer WHERE messageid IN ('.$msgIdList.')');

        /*
         * split out the query in either a full comment or a comment,
         * for simple and fast matching in callers of this code
         */
        $idList = [];
        foreach ($rs as $msgids) {
            $idList[$msgids['messageid']] = 1;
        } // foreach

        return $idList;
    }

    // matchAgainst

    /**
     * @return void
     */
    public function deleteOldest()
    {
        $tmpValues = $this->_conn->arrayQuery('SELECT MIN(id) AS "min", MAX(id) AS "max" FROM moderatedringbuffer');
        $tmpValues = $tmpValues[0];

        /*
         * If we have more than 150000 items, delete them
         */
        if (($tmpValues['max'] - $tmpValues['min']) > 150000) {
            $this->_conn->modify(
                'DELETE FROM moderatedringbuffer WHERE id >:id',
                [
                    ':id' => [$tmpValues['max'] - 150000, PDO::PARAM_INT],
                ]
            );
        } // if
    }

    // deleteOldest
} // Dao_Base_ModeratedRingBuffer
