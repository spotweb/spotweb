<?php

abstract class dbeng_abs
{
    protected $_batchInsertChunks = 500;

    /*
     * Factory class which instantiates the specified DAO factory object
     */
    public static function getDbFactory($engine)
    {
        /*
         * Erase username/password so it won't show up in any stacktrace,
         * only erase them if they exist (eg: sqlite has no username and
         * password)
         */
        switch ($engine) {
            case 'mysql':
            case 'pdo_mysql': return new dbeng_pdo_mysql();
            case 'pdo_pgsql': return new dbeng_pdo_pgsql();
            case 'pdo_sqlite': return new dbeng_pdo_sqlite();
            default: throw new Exception('Unknown database engine ('.$engine.') factory specified');
        } // switch
    }

    // getDbFactory()

    /*
     * Connects to the database
     */
    abstract public function connect($host, $user, $pass, $db, $port, $schema);

    /*
     * Executes the query and discards any output. Returns true of no
     * error was found. No handling of the SQL statement is done
     */
    abstract public function rawExec($sql);

    /*
     * Executes the query with $params as parameters. All parameters are
     * parsed through the safe() function to prevent SQL injection.
     *
     * Returns a single associative array when query succeeds, returns
     * an exception when the query fails.
     */
    abstract public function singleQuery($sql, $params = []);

    /*
     * Executes the query with $params as parameters. All parameters are
     * parsed through sthe safe() function to prevent SQL injection.
     *
     * Returns an array of associative arrays when query succeeds, returns
     * an exception when the query fails.
     */
    abstract public function arrayQuery($sql, $params = []);

    /*
     * Database specific 'escape' or 'safe' function to escape strings
     */
    abstract public function safe($s);

    /*
     * Returns the amount of effected rows
     */
    abstract public function rows();

    /*
     * Begins an transaction
     */
    abstract public function beginTransaction();

    /*
     * Commits an transaction
     */
    abstract public function commit();

    /*
     * Rolls back an transaction
     */
    abstract public function rollback();

    /*
     * Returns the last insertid
     */
    abstract public function lastInsertId($tableName);

    /*
     * Transforms an array of values to an list usable by an
     * IN statement
     */
    abstract public function batchInsert($ar, $sql, $typs, $fields);

    /*
     * Executes the query and returns the (resource or handle)
     */
    abstract public function exec($s, $p = []);

    /*
     * INSERT or UPDATE statement, doesn't return anything. Exception
     * thrown if a error occurs
     */
    abstract public function modify($s, $p = []);

    /*
     * Transforms an array of keys to an list usable by an
     * IN statement
     */
    public function arrayKeyToIn($ar)
    {
        $tmpList = '';

        foreach ($ar as $k => $v) {
            $tmpList .= $this->safe((string) $k).',';
        } // foreach

        return substr($tmpList, 0, -1);
    }

    // arrayKeyToIn

    /*
     * Transforms an array of keys to an list usable by an
     * IN statement
     */
    public function arrayKeyToInForComments($ar)
    {
        $tmpList = '';
        foreach ($ar as $k => $v) {
            // Exclude messageid's from spots which are disposed by the owner, only process real disposes
            if ($v['spotterid'] == '') {
                $tmpList .= $this->safe($k).',';
            }
        } // foreach

        return substr($tmpList, 0, -1);
    }

    // arrayKeyToIn

    /*
     * Transforms an array of values to an list usable by an
     * IN statement
     */
    public function arrayValToIn($ar, $val)
    {
        $tmpList = '';

        foreach ($ar as $v) {
            $tmpList .= $this->safe((string) $v[$val]).',';
        } // foreach

        return substr($tmpList, 0, -1);
    }

    // arrayValToIn
} // dbeng_abs
