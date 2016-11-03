<?php

class NNTPException extends Exception
{
    /**
     * Construct the NNTP exception.
     *
     * @param string    $message  The Exception message.
     * @param int       $code     The Exception code.
     * @param Exception $previous The previous exception used for exception chaining.
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if ($message == '' && $code != 0) {
            global $responseCodes;

            $message = $responseCodes[$code];
        }

        parent::__construct($message, $code, $previous);
    }
}
