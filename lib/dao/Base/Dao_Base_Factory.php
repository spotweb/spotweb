<?php

 class Dao_Base_Factory extends Dao_Factory {

     /*
      * Actual cachepath to use
      */
     public function setCachePath($cachePath) {
         throw new NotImplementedException();
     } # setCachePath

     /*
      * Returns the currently configured cachepath
      */
     public function getCachePath() {
         throw new NotImplementedException();
     } # getCachePath

 	/*
 	 * Actual connection object to be used in
 	 * data retrieval
 	 */
	public function setConnection(dbeng_abs $conn) {
		throw new NotImplementedException();
	} # setConnection

	/**
	 * Returns the currently passed connection object
     *
     * @return dbeng_abs
	 */
	public function getConnection() {
		throw new NotImplementedException();
	} # getConnection


	public function getSpotDao() {
		return new Dao_Base_Spot($this->_conn);
	} # getSpotDao

	public function getUserDao() {
		return new Dao_Base_User($this->_conn);
	} # getUserDao

	public function getCacheDao() {
		return new Dao_Base_Cache($this->_conn, $this->_cachePath);
	} # getCacheDao

	public function getAuditDao() {
		return new Dao_Base_Audit($this->_conn);
	} # getAuditDao

	public function getUserFilterDao() {
		return new Dao_Base_UserFilter($this->_conn);
	} # getUserFilterDao

	public function getSessionDao() {
		return new Dao_Base_Session($this->_conn);
	} # getSessionDao

	public function getBlackWhiteListDao() {
		return new Dao_Base_BlackWhiteList($this->_conn);
	} # getBlackWhiteListDao

	public function getNotificationDao() {
		return new Dao_Base_Notification($this->_conn);
	} # getNotificationDao
		
	public function getCommentDao() {
		return new Dao_Base_Comment($this->_conn);
	} # getCommentDao

	public function getSpotReportDao() {
		return new Dao_Base_SpotReport($this->_conn);
	} # getSpotReportDao

	public function getSettingDao() {
		return new Dao_Base_Setting($this->_conn);
	} # getSettingDao

	public function getUserFilterCountDao() {
		return new Dao_Base_UserFilterCount($this->_conn);
	} # getSettingDao

	public function getSpotStateListDao() {
		return new Dao_Base_SpotStateList($this->_conn);
	} # getSpotStateListDao

	public function getUsenetStateDao() {
		return new Dao_Base_UsenetState($this->_conn);
	} # getUsenetStateDao

    public function getModeratedRingBufferDao() {
        return new Dao_Base_ModeratedRingBuffer($this->_conn);
    } # getModeratedRingBufferDao

     public function getDebugLogDao() {
         return new Dao_Base_DebugLog($this->_conn);
     } # getDebugLogDao

} // Dao_Base_Factory
