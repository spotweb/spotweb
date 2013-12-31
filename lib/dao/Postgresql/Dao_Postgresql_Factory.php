<?php

 class Dao_Postgresql_Factory extends Dao_Factory {
 	private $_conn;
    private $_cachePath;

    /*
     * Actual cachepath to use
     */
    public function setCachePath($cachePath) {
        $this->_cachePath = $cachePath;
    } # setCachePath

    /*
     * Returns the currently configured cachepath
     */
    public function getCachePath() {
        return $this->_cachePath;
    } # getCachePath

 	/*
 	 * Actual connection object to be used in
 	 * data retrieval
 	 */
	public function setConnection(dbeng_abs $conn) {
		$this->_conn = $conn;
	} # setConnection

	/*
	 * Returns the currently passed connection object
	 */
	public function getConnection() {
		return $this->_conn;
	} # getConnection


	public function getSpotDao() {
		return new Dao_Postgresql_Spot($this->_conn);
	} # getSpotDao

	public function getUserDao() {
		return new Dao_Postgresql_User($this->_conn);
	} # getUserDao

	public function getCacheDao() {
		return new Dao_Postgresql_Cache($this->_conn, $this->getCachePath());
	} # getCacheDao

	public function getAuditDao() {
		return new Dao_Postgresql_Audit($this->_conn);
	} # getAuditDao

	public function getUserFilterDao() {
		return new Dao_Postgresql_UserFilter($this->_conn);
	} # getUserFilterDao

	public function getSessionDao() {
		return new Dao_Postgresql_Session($this->_conn);
	} # getSessionDao

	public function getBlackWhiteListDao() {
		return new Dao_Postgresql_BlackWhiteList($this->_conn);
	} # getBlackWhiteListDao

	public function getNotificationDao() {
		return new Dao_Postgresql_Notification($this->_conn);
	} # getNotificationDao
		
	public function getCommentDao() {
		return new Dao_Postgresql_Comment($this->_conn);
	} # getCommentDao

	public function getSpotReportDao() {
		return new Dao_Postgresql_SpotReport($this->_conn);
	} # getSpotReportDao

	public function getSettingDao() {
		return new Dao_Postgresql_Setting($this->_conn);
	} # getSettingDao

	public function getUserFilterCountDao() {
		return new Dao_Postgresql_UserFilterCount($this->_conn);
	} # getSettingDao

	public function getSpotStateListDao() {
		return new Dao_Postgresql_SpotStateList($this->_conn);
	} # getSpotStateListDao

	public function getUsenetStateDao() {
		return new Dao_Postgresql_UsenetState($this->_conn);
	} # getUsenetStateDao

    public function getModeratedRingBufferDao() {
        return new Dao_Postgresql_ModeratedRingBuffer($this->_conn);
    } # getModeratedRingBufferDao

     public function getDebugLogDao() {
         return new Dao_Postgresql_DebugLog($this->_conn);
     } # getDebugLogDao

} // Dao_Postgresql_Factory
