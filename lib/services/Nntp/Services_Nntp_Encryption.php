<?php

/* Shared configuration semantics and TLS policy; wire negotiation stays in the transport. */
class Services_Nntp_Encryption
{
    const ImplicitTls = 'tls';
    const StartTls = 'starttls';

    public static function normalize($mode)
    {
        if ($mode === false || $mode === null || $mode === 0) {
            return false;
        }
        if (!is_string($mode)) {
            return null;
        }

        switch (strtolower($mode)) {
            case '':
            case '0':
            case 'false':
                return false;
            case 'ssl':
            case 'tls':
                // Both historical labels establish encryption before the NNTP greeting.
                return self::ImplicitTls;
            case 'starttls':
                return self::StartTls;
            default:
                return null;
        }
    }

    public static function isValid($mode)
    {
        return self::normalize($mode) !== null;
    }

    public static function cryptoMethod()
    {
        $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $method |= constant('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT');
        }

        return $method;
    }
}
