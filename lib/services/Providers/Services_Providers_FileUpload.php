<?php

class Services_Providers_FileUpload
{
    private $_formName = null;
    private $_fieldName = null;

    /**
     * @param $formName
     * @param $fieldName
     */
    public function __construct($formName, $fieldName)
    {
        $this->_formName = $formName;
        $this->_fieldName = $fieldName;
    }

    // ctor

    /**
     * Checks whether a file is actually uploaded by the user.
     *
     * @return bool
     */
    public function isUploaded()
    {
        return (isset($_FILES[$this->_formName])) && (isset($_FILES[$this->_formName]['name'][$this->_fieldName]));
    }

    // isUploaded

    /**
     * Did the file succesfully upload?
     *
     * @return bool
     */
    public function success()
    {
        if (!$this->isUploaded()) {
            return false;
        } // if

        return $_FILES[$this->_formName]['error'][$this->_fieldName] == UPLOAD_ERR_OK;
    }

    // success

    /**
     * Returns an correct error text.
     *
     * @return string
     */
    public function errorText()
    {
        if (!$this->isUploaded()) {
            return 'No file was uploaded';
        } // if

        switch ($_FILES[$this->_formName]['error'][$this->_fieldName]) {
            case UPLOAD_ERR_INI_SIZE: return "Uploaded file too large, check your PHP's INI file"; break;
            case UPLOAD_ERR_FORM_SIZE: return 'Uploaded file too large, exceeds maximum size of Spotweb'; break;
            case UPLOAD_ERR_PARTIAL: return 'File was only uploaded partially'; break;
            case UPLOAD_ERR_NO_FILE: return 'No file was uploaded'; break;
            case UPLOAD_ERR_NO_TMP_DIR: return 'Temp folder of PHP is missing, please fix your PHP.INI file'; break;
            case UPLOAD_ERR_CANT_WRITE: return 'Failed to write uploaded file to disk, please check your server config'; break;
            case UPLOAD_ERR_EXTENSION: return 'Some PHP extension interfered with uploaded, please check your server config'; break;

            default: return 'unk: '.$_FILES[$this->_formName]['error'][$this->_fieldName];
        } // switch
    }

    // errorText

    /**
     * Returns the filename of the tempfile PHP has saved for us.
     *
     * @return null|string
     */
    public function getTempName()
    {
        if (!$this->success()) {
            return null;
        } // if

        return $_FILES[$this->_formName]['tmp_name'][$this->_fieldName];
    }

    // getTempName
} // class Services_Providers_FileUpload
