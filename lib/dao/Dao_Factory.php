<?php

abstract class Dao_Factory {
    /**
     * @param dbeng_abs $conn
     * @return void
     */
    abstract public function setConnection(dbeng_abs $conn);

    /**
     * @return dbeng_abs
     */
    abstract public function getConnection();

    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_Spot
     */
	abstract public function getSpotDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_User
     */
	abstract public function getUserDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_Cache
     */
	abstract public function getCacheDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_Audit
     */
	abstract public function getAuditDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_UserFilter
     */
    abstract public function getUserFilterDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_Session
     */
	abstract public function getSessionDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_BlackWhiteList
     */
    abstract public function getBlackWhiteListDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_Notification
     */
	abstract public function getNotificationDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_Comment
     */
	abstract public function getCommentDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_SpotReport
     */
	abstract public function getSpotReportDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_Setting
     */
	abstract public function getSettingDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_UserFilterCount
     */
	abstract public function getUserFilterCountDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_SpotStateList
     */
    abstract public function getSpotStateListDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_UsenetState
     */
	abstract public function getUsenetStateDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_Base_ModeratedRingBuffer
     */
    abstract public function getModeratedRingBufferDao();
    /**
     * Factory method which returns specified DAO class
     *
     * @return Dao_DebugLog
     */
    abstract public function getDebugLogDao();

    /**
     * Factory class which instantiates the specified DAO factory object
     *
     * @param $which String specifying which DB specific factory to return
     * @throws Exception Throws exception when unknown database engine is asked
     * @return Dao_Base_Factory
     */
	public static function getDAOFactory($which) {
		switch($which) {
            case 'pdo_pgsql' 	        :
			case 'postgresql'			: return new Dao_Postgresql_Factory(); break;
            case 'pdo_mysql' 	        :
			case 'mysql'				: return new Dao_Mysql_Factory(); break;
			case 'sqlite'				: return new Dao_Sqlite_Factory(); break;

			default						: throw new Exception("Unknown DAO factory specified");
		} // switch
	} # getDayFactory()

} # Dao_Factory
