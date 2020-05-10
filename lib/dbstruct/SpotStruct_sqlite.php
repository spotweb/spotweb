<?php

class SpotStruct_sqlite extends SpotStruct_abs
{
    /*
     * Optimize / analyze (database specific) a number of hightraffic
     * tables.
     * This function does not modify any schema or data
     */
    public function analyze()
    {
        $this->_dbcon->rawExec('ANALYZE spots');
        $this->_dbcon->rawExec('ANALYZE spotsfull');
        $this->_dbcon->rawExec('ANALYZE commentsxover');
        $this->_dbcon->rawExec('ANALYZE commentsfull');
        $this->_dbcon->rawExec('ANALYZE spotstatelist');
        $this->_dbcon->rawExec('ANALYZE sessions');
        $this->_dbcon->rawExec('ANALYZE filters');
        $this->_dbcon->rawExec('ANALYZE spotteridblacklist');
        $this->_dbcon->rawExec('ANALYZE filtercounts');
        $this->_dbcon->rawExec('ANALYZE users');
        $this->_dbcon->rawExec('ANALYZE cache');
        $this->_dbcon->rawExec('ANALYZE moderatedringbuffer');
        $this->_dbcon->rawExec('ANALYZE usenetstate');
    }

    // analyze

    public function resetdb()
    {
        $this->_dbcon->rawExec('PRAGMA FOREIGN_KEYS = OFF');
        $this->_dbcon->rawExec('TRUNCATE TABLE spots');
        $this->_dbcon->rawExec('TRUNCATE TABLE spotsposted');
        $this->_dbcon->rawExec('TRUNCATE TABLE spotsfull');
        $this->_dbcon->rawExec('TRUNCATE TABLE commentsxover');
        $this->_dbcon->rawExec('TRUNCATE TABLE commentsfull');
        $this->_dbcon->rawExec('TRUNCATE TABLE spotstatelist');
        $this->_dbcon->rawExec('TRUNCATE TABLE spotteridblacklist');
        $this->_dbcon->rawExec('TRUNCATE TABLE filtercounts');
        $this->_dbcon->rawExec('TRUNCATE TABLE reportsposted');
        $this->_dbcon->rawExec('TRUNCATE TABLE reportsxover');
        $this->_dbcon->rawExec('TRUNCATE TABLE cache');
        $this->_dbcon->rawExec('TRUNCATE TABLE moderatedringbuffer');
        $this->_dbcon->rawExec('TRUNCATE TABLE usenetstate');
        $this->_dbcon->rawExec('PRAGMA FOREIGN_KEYS = ON');
    }

    // resetdb

    public function clearcache()
    {
        $this->_dbcon->rawExec('TRUNCATE TABLE cache');
    }

    // clearcache

    /*
     * Returns a database specific representation of a boolean value
     */
    public function bool2dt($b)
    {
        if ($b) {
            return '1';
        } // if

        return '0';
    }

    // bool2dt

    /*
     * Converts a 'spotweb' internal datatype to a
     * database specific datatype
     */
    public function swDtToNative($colType)
    {
        switch (strtoupper($colType)) {
            case 'INTEGER': $colType = 'INTEGER'; break;
            case 'INTEGER UNSIGNED': $colType = 'INTEGER'; break;
            case 'BIGINTEGER': $colType = 'BIGINT'; break;
            case 'BIGINTEGER UNSIGNED': $colType = 'BIGINT'; break;
            case 'BOOLEAN': $colType = 'BOOLEAN'; break;
            case 'MEDIUMBLOB': $colType = 'BLOB'; break;
        } // switch

        return $colType;
    }

    // swDtToNative

    /*
     * Converts a database native datatype to a spotweb native
     * datatype
     */
    public function nativeDtToSw($colInfo)
    {
        switch (strtolower($colInfo)) {
            case 'blob': $colInfo = 'MEDIUMBLOB'; break;
        } // switch

        return $colInfo;
    }

    // nativeDtToSw

    /* checks if an index exists */
    public function indexExists($idxname, $tablename)
    {
        $q = $this->_dbcon->arrayQuery('PRAGMA index_info('.$idxname.')');

        return !empty($q);
    }

    // indexExists

    /* checks if a column exists */
    public function columnExists($tablename, $colname)
    {
        $q = $this->_dbcon->arrayQuery('PRAGMA table_info('.$tablename.')');

        $foundCol = false;
        foreach ($q as $row) {
            if ($row['name'] == $colname) {
                $foundCol = true;
                break;
            } // if
        } // foreach

        return $foundCol;
    }

    // columnExists

    /* controleert of een full text index bestaat */
    /* checks if a fts text index exists */
    public function ftsExists($ftsname, $tablename, $colList)
    {
        foreach ($colList as $colName) {
            $colInfo = $this->getColumnInfo($ftsname, $colName);

            if (empty($colInfo)) {
                return false;
            } // if
        } // foreach

        return true;
    }

    // ftsExists

    /* creates a full text index */
    public function createFts($ftsname, $tablename, $colList)
    {
        /*
         * Drop any tables (fts's are special tables/views in sqlite)
         * which are linked to this FTS because we cannot alter those
         * tables.
         *
         * This is rather slow, but it works
         */
        $this->dropTable($ftsname);
        $this->_dbcon->rawExec('DROP TRIGGER IF EXISTS '.$ftsname.'_insert');

        // and recreate the virtual table and link the update trigger to it
        $this->_dbcon->rawExec('CREATE VIRTUAL TABLE '.$ftsname." USING FTS5(CONTENT='spots',".implode(',', $colList).', columnsize=0)');

        $this->_dbcon->rawExec('INSERT INTO '.$ftsname.'(rowid, '.implode(',', $colList).') SELECT rowid,'.implode(',', $colList).' FROM '.$tablename);
        $this->_dbcon->rawExec('CREATE TRIGGER '.$ftsname.'_insert AFTER INSERT ON '.$tablename.' BEGIN
								   INSERT INTO '.$ftsname.'(rowid,'.implode(',', $colList).') VALUES (new.rowid, new.'.implode(', new.', $colList).');
								END');
        $this->_dbcon->rawExec('CREATE TRIGGER '.$ftsname.'_delete AFTER DELETE ON '.$tablename.' BEGIN
								   INSERT INTO '.$ftsname.'('.$ftsname.',rowid,'.implode(',', $colList).") VALUES('delete', old.rowid, old.".implode(', old.', $colList).');
								END');
    }

    // createFts

    /* drops a fulltext index */
    public function dropFts($ftsname, $tablename, $colList)
    {
        $this->dropTable($ftsname);
    }

    // dropFts

    /* returns FTS info  */
    public function getFtsInfo($ftsname, $tablename, $colList)
    {
        $ftsList = [];

        foreach ($colList as $num => $col) {
            $tmpColInfo = $this->getColumnInfo($ftsname, $col);

            if (!empty($tmpColInfo)) {
                $tmpColInfo['column_name'] = $tmpColInfo['COLUMN_NAME'];
                $ftsList[] = $tmpColInfo;
            } // if
        } // foreach

        return $ftsList;
    }

    // getFtsInfo

    /*
     * Adds an index, but first checks if the index doesn't
     * exist already.
     *
     * $idxType can be either 'UNIQUE', '' or 'FULLTEXT'
     */
    public function addIndex($idxname, $idxType, $tablename, $colList)
    {
        if (!$this->indexExists($idxname, $tablename)) {
            $this->_dbcon->rawExec('PRAGMA synchronous = OFF;');

            switch (strtolower($idxType)) {
                case '': $this->_dbcon->rawExec('CREATE INDEX '.$idxname.' ON '.$tablename.'('.implode(',', $colList).');'); break;
                case 'unique': $this->_dbcon->rawExec('CREATE UNIQUE INDEX '.$idxname.' ON '.$tablename.'('.implode(',', $colList).');'); break;
            } // switch
        } // if
    }

    // addIndex

    /* drops an index if it exists */
    public function dropIndex($idxname, $tablename)
    {
        /*
         * Make sure the table exists, else this will return an error
         * and return a fatal
         */
        if (!$this->tableExists($tablename)) {
            return;
        } // if

        if ($this->indexExists($idxname, $tablename)) {
            $this->_dbcon->rawExec('DROP INDEX '.$idxname);
        } // if
    }

    // dropIndex

    /* adds a column if the column doesn't exist yet */
    public function addColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation)
    {
        if (!$this->columnExists($tablename, $colName)) {
            // set the DEFAULT value
            if (strlen($colDefault) != 0) {
                $colDefault = 'DEFAULT '.$colDefault;
            } // if

            // We don't support collation in sqlite
            $colSetting = '';

            // Convert the column type to a type we use in sqlite
            $colType = $this->swDtToNative($colType);

            // and define the 'NOT NULL' part
            switch ($notNull) {
                case true: $nullStr = 'NOT NULL'; break;
                default: $nullStr = '';
            } // switch

            $this->_dbcon->rawExec('ALTER TABLE '.$tablename.
                        ' ADD COLUMN '.$colName.' '.$colType.' '.$colSetting.' '.$colDefault.' '.$nullStr);
        } // if
    }

    // addColumn

    /* drops a column */
    public function dropColumn($colName, $tablename)
    {
        if ($this->columnExists($tablename, $colName)) {
            throw new Exception('Dropping of columns is not supported in sqlite');
        } // if
    }

    // dropColumn

    /* checks if a table exists */
    public function tableExists($tablename)
    {
        $q = $this->_dbcon->arrayQuery('PRAGMA table_info('.$tablename.')');

        return !empty($q);
    }

    // tableExists

    /* creates an empty table with only an ID field. Collation should be either UTF8 or ASCII */
    public function createTable($tablename, $collation)
    {
        if (!$this->tableExists($tablename)) {
            $this->_dbcon->rawExec('CREATE TABLE '.$tablename.' (id INTEGER PRIMARY KEY ASC)');
        } // if
    }

    // createTable

    /* drop a table */
    public function dropTable($tablename)
    {
        if ($this->tableExists($tablename)) {
            $this->_dbcon->rawExec('DROP TABLE '.$tablename);
        } // if
    }

    // dropTable

    /* changes storage engine (sqlite doesn't know anything about storage engines) */
    public function alterStorageEngine($tablename, $engine)
    {
        // null operatie
    }

    // alterStorageEngine

    /* create a foreign key constraint - not supported in spotweb+sqlite */
    public function addForeignKey($tablename, $colname, $reftable, $refcolumn, $action)
    {
        // null
    }

    // addForeignKey

    /* drop a foreign key constraint */
    public function dropForeignKey($tablename, $colname, $reftable, $refcolumn, $action)
    {
        // null
    }

    // dropForeignKey

    /* rename a table */
    public function renameTable($tablename, $newTableName)
    {
        $this->_dbcon->rawExec('ALTER TABLE '.$tablename.' RENAME TO '.$newTableName);
    }

    // renameTable

    /* alters a column - does not check if the column doesn't adhere to the given definition */
    public function modifyColumn($colName, $tablename, $colType, $colDefault, $notNull, $collation, $what)
    {
        /*
         * if the change is either not null, charset or default we ignore the
         * change request because these re not worth dropping the whole database
         * for
         */
        if (($what == 'not null') || ($what == 'charset') | ($what == 'default')) {
            return;
        } // if

        // sqlite doesn't adhere types, so we can safely ignore those kind of changes
        if ($what == 'type') {
            return;
        } // if

        throw new Exception('sqlite does not support modifying the schema of a column');
    }

    // modifyColumn

    /* Returns in a fixed format, column information */
    public function getColumnInfo($tablename, $colname)
    {
        /*
         * sqlite doesn't know a real way to gather this information, so we ask
         * the table info and mangle the information in php to return a correct array
         */
        $q = $this->_dbcon->arrayQuery("PRAGMA table_info('".$tablename."')");

        // find the tablename
        $colIndex = -1;
        for ($i = 0; $i < count($q); $i++) {
            if ($q[$i]['name'] == $colname) {
                $colIndex = $i;
                break;
            } // if
        } // for

        // when the column cannot be found, it's empty
        if ($colIndex < 0) {
            return [];
        } // if

        // translate sqlite tpe of information to the mysql format
        $colInfo = [];
        $colInfo['COLUMN_NAME'] = $colname;
        $colInfo['COLUMN_DEFAULT'] = $q[$colIndex]['dflt_value'];
        $colInfo['NOTNULL'] = $q[$colIndex]['notnull'];
        $colInfo['COLUMN_TYPE'] = $this->nativeDtToSw($q[$colIndex]['type']);
        $colInfo['CHARACTER_SET_NAME'] = 'bin';
        $colInfo['COLLATION_NAME'] = 'bin';

        return $colInfo;
    }

    // getColumnInfo

    /* Returns in a fixed format, index information */
    public function getIndexInfo($idxname, $tablename)
    {
        /*
         * sqlite doesn't know a real way to gather this information, so we ask
         * the index info and mangle the information in php to return a correct array
         */
        $q = $this->_dbcon->arrayQuery("SELECT * FROM sqlite_master 
										  WHERE type = 'index' 
										    AND name = '".$idxname."' 
											AND tbl_name = '".$tablename."'");
        if (empty($q)) {
            return [];
        } // if

        // a index name is globally unique in the database
        $q = $q[0];

        // unique index?
        $tmpAr = explode(' ', $q['sql']);
        $isNotUnique = (strtolower($tmpAr[1]) != 'unique');

        // retrieve column list and definition
        preg_match_all("/\((.*)\)/", $q['sql'], $tmpAr);
        $colList = explode(',', $tmpAr[1][0]);
        $colList = array_map('trim', $colList);

        // and translate column information to the desired format
        $idxInfo = [];
        for ($i = 0; $i < count($colList); $i++) {
            $idxInfo[] = ['column_name' => $colList[$i],
                'non_unique'            => (int) $isNotUnique,
                'index_type'            => 'BTREE',
            ];
        } // foreach

        return $idxInfo;
    }

    // getIndexInfo
} // class
