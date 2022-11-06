<?php
/**
 * throw exceptions based on E_* error types.
 */
set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }
    switch ($err_no) {
        case E_ERROR:               throw new Exception($err_msg, $err_no);
        case E_WARNING:             throw new WarningException($err_msg, $err_no);
        case E_PARSE:               throw new ParseException($err_msg, $err_no);
        case E_NOTICE:              throw new NoticeException($err_msg, $err_no);
        case E_CORE_ERROR:          throw new CoreErrorException($err_msg, $err_no);
        case E_CORE_WARNING:        throw new CoreWarningException($err_msg, $err_no);
        case E_COMPILE_ERROR:       throw new CompileErrorException($err_msg, $err_no);
        case E_COMPILE_WARNING:     throw new CoreWarningException($err_msg, $err_no);
        case E_USER_ERROR:          throw new UserErrorException($err_msg, $err_no);
        case E_USER_WARNING:        throw new UserWarningException($err_msg, $err_no);
        case E_USER_NOTICE:         throw new UserNoticeException($err_msg, $err_no);
        case E_STRICT:              throw new StrictException($err_msg, $err_no);
        case E_RECOVERABLE_ERROR:   throw new RecoverableErrorException($err_msg, $err_no);
        case E_DEPRECATED:          throw new DeprecatedException($err_msg, $err_no);
        case E_USER_DEPRECATED:     throw new UserDeprecatedException($err_msg, $err_no);
    }
});

class WarningException extends Exception
{
}
class ParseException extends Exception
{
}
class NoticeException extends Exception
{
}
class CoreErrorException extends Exception
{
}
class CoreWarningException extends Exception
{
}
class CompileErrorException extends Exception
{
}
class CompileWarningException extends Exception
{
}
class UserErrorException extends Exception
{
}
class UserWarningException extends Exception
{
}
class UserNoticeException extends Exception
{
}
class StrictException extends Exception
{
}
class RecoverableErrorException extends Exception
{
}
class DeprecatedException extends Exception
{
}
class UserDeprecatedException extends Exception
{
}
