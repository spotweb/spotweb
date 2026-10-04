<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../lib/services/Nntp/Services_Nntp_ClientPool.php';
require_once __DIR__.'/../../Support/NntpConfigurationFixtureServer.php';

class NntpConfigurationParitySettingsSource implements Services_Settings_IContainer
{
    private $_settings;

    public function __construct(array $settings)
    {
        $this->_settings = $settings;
    }

    public function initialize(array $cfg)
    {
    }

    public function getAllSettings()
    {
        return $this->_settings;
    }

    public function remove($name)
    {
        unset($this->_settings[$name]);
    }

    public function set($name, $value)
    {
        $this->_settings[$name] = $value;
    }
}

class ServicesNntpConfigurationParityTest extends TestCase
{
    private $_certificate;
    private $_pids = [];

    protected function setUp(): void
    {
        if (!extension_loaded('openssl') || !function_exists('pcntl_fork')) {
            $this->markTestSkipped('Local TLS fixtures require OpenSSL and pcntl');
        }
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
        $certificate = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
        openssl_x509_export($certificate, $publicPem);
        openssl_pkey_export($key, $privatePem);
        $this->_certificate = tempnam(sys_get_temp_dir(), 'spotweb-fixture-cert-');
        file_put_contents($this->_certificate, $publicPem.$privatePem);
        $this->resetStatic(Services_Nntp_ClientPool::class, ['_instances' => []]);
        $this->resetStatic(Services_Settings_Container::class, ['_instance' => null, '_settings' => [], '_sources' => []]);
    }

    protected function tearDown(): void
    {
        foreach ($this->_pids as $pid) {
            pcntl_waitpid($pid, $status);
        }
        if ($this->_certificate !== null) {
            unlink($this->_certificate);
        }
        $this->resetStatic(Services_Nntp_ClientPool::class, ['_instances' => []]);
        $this->resetStatic(Services_Settings_Container::class, ['_instance' => null, '_settings' => [], '_sources' => []]);
    }

    public function testPlainConnectionAndBothAuthenticationSuccessResponses()
    {
        foreach (['password', 'user-only'] as $auth) {
            $transport = $this->transport(false, false, $auth);
            $transport->connect();
            $transport->sendNoop();
            $this->assertSame(1, $transport->getOpenedConnectionCount());
            $transport->quit();
        }
    }

    public function testAuthenticationRejectionIsReported()
    {
        $transport = $this->transport(false, false, 'password', 'wrong-fixture-password');
        try {
            $transport->connect();
            $this->fail('Authentication must not silently succeed');
        } catch (NntpException $exception) {
            $this->assertSame(481, $exception->getCode());
        } finally {
            $transport->disconnect();
        }
    }

    public function testPlainConnectionWithoutCredentials()
    {
        $transport = $this->transport(false, false, 'password', '', '');
        $transport->connect();
        $transport->sendNoop();
        $this->assertSame(1, $transport->getOpenedConnectionCount());
        $transport->quit();
    }

    public function testImplicitTlsAndStartTlsWithVerificationExplicitlyDisabled()
    {
        foreach (['ssl', 'tls'] as $mode) {
            $transport = $this->transport($mode, false);
            $transport->connect();
            $transport->sendNoop();
            $this->assertSame(1, $transport->getOpenedConnectionCount());
            $transport->quit();
        }
    }

    public function testVerificationEnabledRejectsUntrustedCertificate()
    {
        foreach (['ssl', 'tls'] as $mode) {
            $transport = $this->transport($mode, true);
            try {
                $transport->connect();
                $this->fail('Self-signed fixture certificate must be rejected');
            } catch (NntpException $exception) {
                $this->assertInstanceOf(NntpException::class, $exception);
            } finally {
                $transport->disconnect();
            }
        }
    }

    public function testStartTlsHandshakeHasABoundedTimeout()
    {
        $fixture = NntpConfigurationFixtureServer::start('tls-stall', $this->_certificate, 'password');
        $this->_pids[] = $fixture['pid'];
        $transport = new Services_Nntp_PipelinedTransport(['host' => $fixture['host'], 'port' => $fixture['port'], 'enc' => 'tls', 'verifyname' => false, 'user' => '', 'pass' => ''], 1);
        $started = microtime(true);
        try {
            $transport->connect();
            $this->fail('Stalled handshake must not succeed');
        } catch (NntpException $exception) {
            $this->assertLessThan(1.8, microtime(true) - $started);
        } finally {
            $transport->disconnect();
        }
    }

    public function testTrustedCertificateAndHostnameVerificationForBothTlsModes()
    {
        foreach (['ssl', 'tls'] as $mode) {
            foreach (['localhost' => 0, '127.0.0.1' => 1] as $host => $expectedExit) {
                $fixture = NntpConfigurationFixtureServer::start($mode, $this->_certificate, 'password');
                $this->_pids[] = $fixture['pid'];
                $server = ['host' => $host, 'port' => $fixture['port'], 'enc' => $mode, 'verifyname' => true, 'user' => 'fixture-user', 'pass' => 'fixture-password'];
                /* A separate PHP process allows a private fixture CA without
                 * changing the system trust store or production settings.
                 */
                $code = 'require "vendor/autoload.php"; require "lib/services/Nntp/Services_Nntp_ClientPool.php";'
                    .'$transport = new Services_Nntp_PipelinedTransport('.var_export($server, true).', 3);'
                    .'try { $transport->connect(); $transport->sendNoop(); $transport->quit(); exit(0); }'
                    .'catch (NntpException $e) { $transport->disconnect(); exit(1); }';
                $process = proc_open([PHP_BINARY, '-d', 'openssl.cafile='.$this->_certificate, '-r', $code], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 3));
                $this->assertIsResource($process);
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }
                $this->assertSame($expectedExit, proc_close($process), $mode.' hostname '.$host);
            }
        }
    }

    public function testRoleSpecificDefaultsMatchExistingPoolBehaviour()
    {
        $server = ['host' => 'fixture.invalid', 'port' => 563, 'enc' => 'ssl', 'user' => 'fixture-user', 'pass' => 'fixture-password', 'buggy' => true];
        $settings = $this->settings(['nntp_hdr' => $server, 'nntp_nzb' => $server, 'nntp_post' => $server]);
        foreach (['hdr' => false, 'bin' => true, 'post' => true] as $role => $expectedVerification) {
            $transport = Services_Nntp_ClientPool::pool($settings, $role);
            $actual = $this->server($transport);
            $this->assertSame($expectedVerification, $actual['verifyname'], $role.' certificate default');
            foreach ($server as $key => $value) {
                $this->assertSame($value, $actual[$key], $role.' preserves '.$key);
            }
            $this->assertSame(32, $actual['article_pipeline_depth']);
            $this->assertSame($transport, Services_Nntp_ClientPool::pool($settings, $role));
        }
    }

    public function testExplicitVerificationAndPipelineValuesArePreserved()
    {
        $base = ['host' => 'fixture.invalid', 'port' => 119, 'enc' => false, 'user' => '', 'pass' => '', 'buggy' => false];
        $settings = $this->settings([
            'nntp_hdr'  => $base + ['verifyname' => true, 'article_pipeline_depth' => 16],
            'nntp_nzb'  => $base + ['verifyname' => false, 'article_pipeline_depth' => 64],
            'nntp_post' => $base + ['verifyname' => false, 'article_pipeline_depth' => 1],
        ]);
        foreach (['hdr' => [true, 16], 'bin' => [false, 64], 'post' => [false, 1]] as $role => $expected) {
            $actual = $this->server(Services_Nntp_ClientPool::pool($settings, $role));
            $this->assertSame($expected[0], $actual['verifyname']);
            $this->assertSame($expected[1], $actual['article_pipeline_depth']);
        }
    }

    public function testUnconfiguredBinaryAndPostingServersReuseHeaderConnection()
    {
        $settings = $this->settings(['nntp_hdr' => ['host' => 'fixture.invalid', 'port' => 119, 'enc' => false], 'nntp_nzb' => [], 'nntp_post' => []]);
        $header = Services_Nntp_ClientPool::pool($settings, 'hdr');
        $this->assertSame($header, Services_Nntp_ClientPool::pool($settings, 'bin'));
        $this->assertSame($header, Services_Nntp_ClientPool::pool($settings, 'post'));
    }

    private function transport($mode, $verify, $auth = 'password', $password = 'fixture-password', $user = 'fixture-user')
    {
        $fixture = NntpConfigurationFixtureServer::start($mode, $this->_certificate, $auth);
        $this->_pids[] = $fixture['pid'];

        return new Services_Nntp_PipelinedTransport(['host' => $fixture['host'], 'port' => $fixture['port'], 'enc' => $mode, 'verifyname' => $verify, 'user' => $user, 'pass' => $password], 3);
    }

    private function settings(array $values)
    {
        $settings = new Services_Settings_Container();
        $settings->addSource(new NntpConfigurationParitySettingsSource($values));

        return $settings;
    }

    private function server($transport)
    {
        $property = new ReflectionProperty(Services_Nntp_PipelinedTransport::class, '_server');
        $property->setAccessible(true);

        return $property->getValue($transport);
    }

    private function resetStatic($class, array $values)
    {
        foreach ($values as $name => $value) {
            $property = new ReflectionProperty($class, $name);
            $property->setAccessible(true);
            $property->setValue(null, $value);
        }
    }
}
