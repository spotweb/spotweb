<?php

class Dao_Base_Setting implements Dao_Setting
{
    protected $_conn;

    /*
     * constructs a new Dao_Base_Comment object,
     * connection object is given
     */
    public function __construct(dbeng_abs $conn)
    {
        $this->_conn = $conn;
    }

    // ctor

    /*
     * Retrieves all settings from the database
     */
    public function getAllSettings()
    {
        $tmpSettings = [];

        $dbSettings = $this->_conn->arrayQuery('SELECT name, value, serialized FROM settings');
        foreach ($dbSettings as $item) {
            if ($item['serialized']) {
                $item['value'] = unserialize($item['value']);
            } // if

            $tmpSettings[$item['name']] = $item['value'];
        } // foreach

        return $tmpSettings;
    }

    // getAllSettings

    /*
     * Removes a setting from the database
     */
    public function removeSetting($name)
    {
        $this->_conn->exec(
            'DELETE FROM settings WHERE name = :name',
            [
                ':name' => [$name, PDO::PARAM_STR],
            ]
        );
    }

    // removeSetting

    /*
     * Update setting
     */
    public function updateSetting($name, $value)
    {
        // When necessary, serialize the data
        if ((is_array($value) || is_object($value))) {
            $value = serialize($value);
            $serialized = true;
        } else {
            $serialized = false;
        } // if

        $this->_conn->exec(
            'UPDATE settings SET value = :value, serialized = :serialized WHERE name = :name',
            [
                ':value'      => [$value, PDO::PARAM_STR],
                ':serialized' => [$serialized, PDO::PARAM_BOOL],
                ':name'       => [$name, PDO::PARAM_STR],
            ]
        );

        if ($this->_conn->rows() == 0) {
            $this->_conn->modify(
                'INSERT INTO settings(name,value,serialized) VALUES(:name, :value, :serialized)',
                [
                    ':name'       => [$name, PDO::PARAM_STR],
                    ':value'      => [$value, PDO::PARAM_STR],
                    ':serialized' => [$serialized, PDO::PARAM_BOOL],
                ]
            );
        } // if
    }

    // updateSetting

    /*
     * Returns the current schema version
     */
    public function getSchemaVer()
    {
        return $this->_conn->singleQuery("SELECT value FROM settings WHERE name = 'schemaversion'");
    }

    // getSchemaVer
} // Dao_Base_Setting
