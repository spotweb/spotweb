<?php

abstract class Dao_Factory {
	abstract public function setConnection($conn);
	abstract public function getConnection();

	abstract public function getSpotDao();
	abstract public function getSpotSearchDao();
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

	/*
	 * Factory class which instantiates the specified DAO factory object
	 */
	public static function getDAOFactory($which) {
		switch($which) {
			case 'postgresql'			: return new PostgresqlDaoFactory(); break;
			case 'mysql'				: return new MysqlDaoFactory(); break;
			case 'sqlite'				: return new SqliteDaoFactory(); break;

			default						: throw new Exception("Unknown DAO factory specified");
		} // switch
	} # getDayFactory()

} // Dao_Factory
