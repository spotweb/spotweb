<?php

class Dto_FormResult
{
    public $_result;
    public $_data;
    public $_errors;
    public $_warnings;
    public $_info;

    public function __construct($result = 'success')
    {
        $this->setResult($result);
        $this->_data = [];
        $this->_errors = [];
        $this->_warnings = [];
        $this->_info = [];
    }

    // ctor

    /*
     * Set a specific result
     */
    public function setResult($s)
    {
        $validResults = ['success' => true,
            'warning'              => true,
            'failure'              => true,
            'notsubmitted'         => true, ];

        if (!isset($validResults[$s])) {
            throw new Exception('Invalid result ('.$s.') chosen');
        } // if

        $this->_result = $s;
    }

    // setResult

    /*
     * Returns true when the form is in success
     * state
     */
    public function isSuccess()
    {
        return $this->_result == 'success';
    }

    // isSuccess

    /*
     * Returns true when the form is in error
     * state
     */
    public function isError()
    {
        return $this->_result == 'error';
    }

    // isError

    /*
     * Returns the current result of this form
     */
    public function getResult()
    {
        return $this->_result;
    }

    // getResult

    /*
     * Add an error to the list of errors
     */
    public function addError($s)
    {
        if (empty($s)) {
            return;
        } // if

        $this->setResult('failure');

        if (is_array($s)) {
            $this->_errors += $s;
        } else {
            $this->_errors[] = $s;
        } // else
    }

    // addError

    /*
     * Add an info field to the list of infomessages
     */
    public function addInfo($s)
    {
        if (empty($s)) {
            return;
        } // if

        if (is_array($s)) {
            $this->_info += $s;
        } else {
            $this->_info[] = $s;
        } // else
    }

    // addInfo

    /*
     * Add an warning filed to the list of warningmessages
     */
    public function addWarning($s)
    {
        if (empty($s)) {
            return;
        } // if

        /*
         * Error trumps warnings
         */
        if ($this->getResult() != 'error') {
            $this->setResult('warning');
        } // if

        if (is_array($s)) {
            $this->_warnings += $s;
        } else {
            $this->_warnings[] = $s;
        } // else
    }

    // addWarning

    /*
     * add a data field to the result
     */
    public function addData($field, $value)
    {
        $this->_data[$field] = $value;
    }

    // addData

    /*
     * remove a data field from the result
     */
    public function removeData($field)
    {
        unset($this->_data[$field]);
    }

    // removeData

    /*
     * Return a list of data fields
     */
    public function getData($field = null)
    {
        if (($field === null) or (empty($this->_data[$field]))) {
            return $this->_data;
        } // if

        return $this->_data[$field];
    }

    // getData

    /*
     * Return a list of errors
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    // getErrors

    /*
     * Return a list of info fields
     */
    public function getInfo()
    {
        return $this->_info;
    }

    // getInfo

    /*
     * Returns a list of warnings
     */
    public function getWarnings()
    {
        return $this->_warnings;
    }

    // getWarnings

    /*
     * Merge the result object from
     * another instance into this one
     */
    public function mergeResult($result)
    {
        foreach ($result->getInfo() as $info) {
            $this->addInfo($info);
        } // if

        foreach ($result->getWarnings() as $warning) {
            $this->addWarning($warning);
        } // if

        foreach ($result->getErrors() as $error) {
            $this->addError($error);
        } // if

        $dataFields = $result->getData();
        foreach ($dataFields as $dataKey => $dataVal) {
            $this->addData($dataKey, $dataVal);
        } // if
    }

    // mergeResult

    /*
     * Returns true when a form was tried to be submitted
     */
    public function isSubmitted()
    {
        return $this->_result != 'notsubmitted';
    }

    // isSubmitted

    /*
     * Convert this struct to JSON
     */
    public function toJSON()
    {
        return json_encode(
            ['result'      => $this->getResult(),
                'data'     => $this->getData(),
                'info'     => $this->getInfo(),
                'warnings' => $this->getWarnings(),
                'errors'   => $this->getErrors(), ]
        );
    }

    // toJSON
} // Dto_FormResult
