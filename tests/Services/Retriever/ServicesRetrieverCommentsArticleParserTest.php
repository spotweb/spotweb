<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Retriever/Services_Retriever_CommentsArticleParser.php';

class ServicesRetrieverCommentsArticleParserTest extends TestCase
{
    private static $_key;
    private static $_publicKey;

    protected function setUp(): void
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('Signature fixtures require OpenSSL');
        }
        if (self::$_key === null) {
            self::$_key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            $this->assertNotFalse(self::$_key);
            self::$_publicKey = openssl_pkey_get_details(self::$_key)['rsa'];
        }
    }

    public function testMessageIdSignatureVerifies()
    {
        $article = $this->article(['First line', '', 'Last line'], false);
        $comment = (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', $article);

        $this->assertTrue((bool) $comment['verified']);
        $this->assertNotSame('', $comment['spotterid']);
        $this->assertSame("First line\r\n\r\nLast line", $comment['body']);
    }

    public function testBodyAndSenderSignatureReceivesOriginalLines()
    {
        $article = $this->article(['First line', '', 'Last line']);
        $comment = (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', $article);

        $this->assertTrue((bool) $comment['verified']);
        $this->assertNotSame('', $comment['spotterid']);
        $this->assertSame('Fixture Author', $comment['fromhdr']);
        $this->assertSame("First line\r\n\r\nLast line", $comment['body']);
        $this->assertSame(['First line', '', 'Last line'], $article['body']);
    }

    public function testBodyIsVerifiedBeforeEncodingConversionAndTruncation()
    {
        $raw = str_repeat("caf\xC3\xA9 ", 3000);
        $article = $this->article([$raw]);
        $comment = (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', $article);

        $this->assertTrue((bool) $comment['verified']);
        $this->assertSame(substr(mb_convert_encoding($raw, 'ISO-8859-1', 'UTF-8'), 0, 10240), $comment['body']);
        $this->assertSame(10240, strlen($comment['body']));
    }

    public function testTamperingBeyondStoredBodyLimitFailsVerification()
    {
        $article = $this->article([str_repeat('a', 12000)]);
        $article['body'][0] .= 'tampered';
        $comment = (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', $article);

        $this->assertFalse((bool) $comment['verified']);
        $this->assertSame('', $comment['spotterid']);
        $this->assertSame(str_repeat('a', 10240), $comment['body']);
    }

    public function testInvalidSignatureDoesNotCrashOrBecomeVerified()
    {
        $article = $this->article(['Original body']);
        $comment = (new Services_Retriever_CommentsArticleParser())->parse('different@example.invalid', $article);

        $this->assertFalse((bool) $comment['verified']);
        $this->assertSame('', $comment['spotterid']);
        $this->assertSame('Original body', $comment['body']);
    }

    public function testEmptyBodyCanBeVerified()
    {
        $comment = (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', $this->article([]));

        $this->assertTrue((bool) $comment['verified']);
        $this->assertSame('', $comment['body']);
    }

    public function testUnsignedCommentRemainsUnverified()
    {
        $comment = (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', ['header' => [], 'body' => ['Unsigned body']]);

        $this->assertFalse($comment['verified']);
        $this->assertSame('', $comment['spotterid']);
        $this->assertSame('Unsigned body', $comment['body']);
    }

    public function testMalformedArticleStructureIsRejectedExplicitly()
    {
        foreach ([[], ['header' => []], ['header' => 'not lines', 'body' => []], ['header' => [], 'body' => null], ['header' => [], 'body' => 'not lines']] as $article) {
            try {
                (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', $article);
                $this->fail('Malformed article structure must be rejected');
            } catch (UnexpectedValueException $exception) {
                $this->assertStringContainsString('comment', $exception->getMessage());
            }
        }
    }

    public function testNonStringLinesAreRejectedExplicitly()
    {
        foreach (['header', 'body'] as $section) {
            foreach ([null, [], 123] as $line) {
                $article = ['header' => [], 'body' => []];
                $article[$section][] = $line;

                try {
                    (new Services_Retriever_CommentsArticleParser())->parse('fixture@example.invalid', $article);
                    $this->fail('Non-string article lines must be rejected');
                } catch (UnexpectedValueException $exception) {
                    $this->assertStringContainsString($section, $exception->getMessage());
                }
            }
        }
    }

    private function article(array $body, $includeBody = true)
    {
        $payload = '<fixture@example.invalid>';
        if ($includeBody) {
            $payload .= implode("\r\n", $body)."\r\n".'Fixture Author';
        }
        $this->assertTrue(openssl_sign($payload, $signature, self::$_key));
        $format = new Services_Format_Util();

        return [
            'header' => [
                'From: Fixture Author <author@example.invalid>',
                'X-User-Key: <RSAKeyValue><Exponent>'.base64_encode(self::$_publicKey['e']).'</Exponent><Modulus>'.base64_encode(self::$_publicKey['n']).'</Modulus></RSAKeyValue>',
                'X-User-Signature: '.$format->spotPrepareBase64(base64_encode($signature)),
            ],
            'body' => $body,
        ];
    }
}
