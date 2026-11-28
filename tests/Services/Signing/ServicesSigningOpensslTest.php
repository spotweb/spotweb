<?php

use PHPUnit\Framework\TestCase;

class ServicesSigningOpensslTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Requires the OpenSSL extension');
        }
    }

    public function testEncodeUnsignedIntegerProducesMinimalPositiveDer()
    {
        $signing = new Services_Signing_Openssl();

        $this->assertSame("\x00\x80\x01", $signing->_encodeUnsignedInteger("\x80\x01"));
        $this->assertSame("\x7f\x01", $signing->_encodeUnsignedInteger("\x7f\x01"));
        $this->assertSame("\x01\x00\x01", $signing->_encodeUnsignedInteger("\x00\x00\x01\x00\x01"));
        $this->assertSame("\x00\x80", $signing->_encodeUnsignedInteger("\x00\x00\x80"));
        $this->assertSame("\x00", $signing->_encodeUnsignedInteger(''));
    }

    /**
     * @dataProvider keyEncodingProvider
     */
    public function testSignatureVerifiesWithRawAndPaddedKeyComponents($padding)
    {
        $privateKey = openssl_pkey_new(['private_key_bits' => 1024, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertNotFalse($privateKey, 'Unable to generate a key; is OPENSSL_CONF set?');
        $details = openssl_pkey_get_details($privateKey)['rsa'];

        $payload = '<fixture@example.invalid>';
        $this->assertTrue(openssl_sign($payload, $signature, $privateKey));

        $signing = new Services_Signing_Openssl();
        $this->assertSame(1, $signing->verifyComment([
            'messageid'      => 'fixture@example.invalid',
            'user-signature' => base64_encode($signature),
            'user-key'       => [
                'modulo'   => base64_encode($padding.$details['n']),
                'exponent' => base64_encode($padding.$details['e']),
            ],
        ]));
    }

    public function keyEncodingProvider()
    {
        return [
            'raw components'           => [''],
            'redundant leading zeroes' => ["\x00\x00"],
        ];
    }
}
