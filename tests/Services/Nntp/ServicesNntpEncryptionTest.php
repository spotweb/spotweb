<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_Encryption.php';

class ServicesNntpEncryptionTest extends TestCase
{
    public function testHistoricalLabelsNormalizeToImplicitTls()
    {
        foreach (['ssl', 'tls', 'SSL', 'TLS'] as $mode) {
            $this->assertSame(Services_Nntp_Encryption::ImplicitTls, Services_Nntp_Encryption::normalize($mode));
            $this->assertTrue(Services_Nntp_Encryption::isValid($mode));
        }
        $this->assertSame(Services_Nntp_Encryption::StartTls, Services_Nntp_Encryption::normalize('starttls'));
    }

    public function testPlaintextAndInvalidModesRemainDistinct()
    {
        foreach ([false, null, 0, '', '0', 'false'] as $mode) {
            $this->assertFalse(Services_Nntp_Encryption::normalize($mode));
            $this->assertTrue(Services_Nntp_Encryption::isValid($mode));
        }
        foreach (['unknown', 'sslv3', true, [], 123] as $mode) {
            $this->assertNull(Services_Nntp_Encryption::normalize($mode));
            $this->assertFalse(Services_Nntp_Encryption::isValid($mode));
        }
    }

    public function testCryptoPolicyAllowsOnlyTls12AndTls13()
    {
        $expected = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $expected |= constant('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT');
        }
        $this->assertSame($expected, Services_Nntp_Encryption::cryptoMethod());
    }
}
