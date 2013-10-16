<?php

class Services_Posting_Editor {
    private $_daoFactory;
    private $_currentSession;
    private $_spotValidator;

    function __construct(Dao_Factory $daoFactory, $currentSession) {
        $this->_daoFactory = $daoFactory;
        $this->_currentSession = $currentSession;

        $this->_spotValidator = new Services_Posting_Validator();
    } # ctor

    /*
     * Delete a spot from the database
     */
    public function deleteSpot($messageId) {
        # remove the spot from the database
        $daoSpot = $this->_daoFactory->getSpotDao();

        $spotMsgIdList = array($messageId => true);
        $daoSpot->removeSpots($spotMsgIdList);
    } # deleteSpot

    public function updateSpot($messageId, $fullSpotXml) {
        # parse the fullspot xml
        $svcFmtParsing = new Services_Format_Parsing();
        $updatedFullSpot = $svcFmtParsing->parseFull($fullSpotXml);

        /*
         * add the message id and updated fullspot xml because they are not added
         * to the spot when parsing the updated fullspot xml
         */
        $updatedFullSpot['messageid'] = $messageId;
        $updatedFullSpot['fullxml'] = $fullSpotXml;

        # finally store the updated spot in the database
        $daoSpot = $this->_daoFactory->getSpotDao();
        $daoSpot->updateSpot($updatedFullSpot, $this->_currentSession['user']['username']);
    } # updateSpot

    /*
     * Validate the data entered by the user, merge the original
     * fullspot with the data entered by the user and create the
     * updated fullspot xml that will be stored in the database.
     *
     * The following fields can be merged into the fullspot:
     * 'title', 'body', 'tag', 'website', 'category', 'subcata',
     * 'subcatb', 'subcatc', 'subcatd' and 'subcatz'
     */
    public function updateSpotXml($fullSpot, $updatesToApply) {
        $result = new Dto_FormResult();
        /*
         * before we merge we first want to clean the form from the stuff
         * we don't want to merge with the original spot
         */
        $spot = $this->cleanseUpdates($updatesToApply);

        /*
         * subcat must be an array so let's make it an array if it is not,
         * otherwise we get in trouble in the verifyCategories() method
         */
        if (!is_array($spot['subcatb'])) { $spot['subcatb'] = array(); }
        if (!is_array($spot['subcatc'])) { $spot['subcatc'] = array(); }
        if (!is_array($spot['subcatd'])) { $spot['subcatd'] = array(); }

        # Verify several properties from the caller
        $result->addData('spot', $spot);
        $result = $this->_spotValidator->verifyTitle($result);
        $result = $this->_spotValidator->verifyBody($result);
        $result = $this->_spotValidator->verifyCategories($result);
        $result = $this->_spotValidator->verifyWebsite($result);
        $result = $this->_spotValidator->verifyTag($result);

        /*
         * Retrieve the spot information from the result,
         * and remove it again. We do not want to send the
         * whole spot back to the caller
         */
        $spot = $result->getData('spot');
        $result->removeData('spot');

        if ($result->isSuccess()) {

            # We now merge the cleaned edit form into the original spot
            $spot = array_merge($fullSpot, $spot);

            $imageInfo = array('height' => $spot['image']['height'],
                'width' => $spot['image']['width'],
                'segments' => $spot['image']['segment']);

            $nzbSegmentList = $spot['nzb'];

            # Parse the updated spot to an XML structure
            $spotCreator = new Services_Format_Creation();
            $spotXml = $spotCreator->convertSpotToXml($spot, $imageInfo, $nzbSegmentList);

            $result->addData('spotxml', $spotXml);
        } # if

        return $result;
    } # updateSpotXml

    /*
     * remove all fields from the array that we do not want to merge
     * with the original fullspot
     */
    private function cleanseUpdates($updatesToApply) {
        # Only keep the fields we want to merge
        $validFields = array('title', 'body', 'tag', 'website', 'category', 'subcata', 'subcatb', 'subcatc', 'subcatd', 'subcatz');
        foreach($updatesToApply as $key => $value) {
            if (in_array($key, $validFields) === false) {
                unset($updatesToApply[$key]);
            } # if
        } # foreach

        return $updatesToApply;
    } # cleanseEditForm

} # class Services_Posting_Editor
