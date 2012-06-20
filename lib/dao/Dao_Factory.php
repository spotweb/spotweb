<?php

abstract class Dao_Factory {
	abstract public function setConnection($conn);
	abstract public function getConnection();

	abstract public function getSpotDao();
	abstract public function getUserDao();
	abstract public function getCacheDao();
	abstract public function getAuditDao();
	abstract public function getUserFilterDao();
	abstract public function getSessionDao();
	abstract public function getBlackWhiteListDao();
	abstract public function getNotificationDao();
	abstract public function getCommentDao();
	abstract public function getSpotReportDao();
	abstract public function getSettingDao();
	abstract public function getUserFilterCountDao();
	abstract public function getSpotStateListDao();
	abstract public function getNntpDao();

	/*
	 * Factory class which instantiates the specified DAO factory object
	 */
	public static function getDAOFactory($which) {
		switch($which) {
			case 'postgresql'			: return new Dao_Postgresql_Factory(); break;
			case 'mysql'				: return new Dao_Mysql_Factory(); break;
			case 'sqlite'				: return new Dao_Sqlite_actory(); break;

			default						: throw new Exception("Unknown DAO factory specified");
		} // switch
	} # getDayFactory()

} // Dao_Factory
