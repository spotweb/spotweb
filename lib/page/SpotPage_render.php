<?php

class SpotPage_render extends SpotPage_Abs
{
    private $_tplname;
    private $_params;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, $tplName, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_tplname = $tplName;
        $this->_params = $params;
    }

    // ctor

    /**
     * Removes any pottentially 'dangerous' characters from a string.
     *
     * @param $tpl
     *
     * @return string
     */
    private function sanitizeTplName($tpl)
    {
        $validChars = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $newName = '';
        for ($i = 0; $i < strlen($tpl); $i++) {
            if (strpos($validChars, $tpl[$i]) !== false) {
                $newName .= $tpl[$i];
            } // if
        } // for

        return $newName;
    }

    // sanitizeTplName

    /**
     * Actually render the template.
     */
    public function render()
    {
        // sanitize the template name
        $tplFile = $this->sanitizeTplName($this->_tplname);

        //- display stuff -#
        if (strlen($tplFile) > 0) {
            $this->template($tplFile, $this->_params);
        } // if
    }

    // render
} // class SpotPage_render
