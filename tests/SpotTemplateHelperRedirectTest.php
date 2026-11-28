<?php

use PHPUnit\Framework\TestCase;

class SpotTemplateHelperRedirectTest extends TestCase
{
    /** @dataProvider redirectTargets */
    public function testRedirectStaysOnConfiguredOrigin($target, $expected, $base = 'https://example.test/spotweb/')
    {
        $settings = $this->getMockBuilder(Services_Settings_Container::class)
            ->disableOriginalConstructor()->getMock();
        $settings->method('get')->with('spotweburl')->willReturn($base);
        $helper = new ReflectionClass(SpotTemplateHelper::class);
        $instance = $helper->newInstanceWithoutConstructor();
        $property = $helper->getProperty('_settings');
        $property->setAccessible(true);
        $property->setValue($instance, $settings);
        $method = $helper->getMethod('getSafeRedirectUrl');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($instance, $target));
    }

    public function redirectTargets()
    {
        $fallback = 'https://example.test/spotweb/';
        $cases = [
            ['https://evil.test/', $fallback],
            ['//evil.test/', $fallback],
            ['/\\evil.test/', $fallback],
            ["/\t/evil.test/", $fallback],
            ["\nhttps://evil.test/", $fallback],
            ['javascript:alert(1)', $fallback],
            ['https://example.test.evil.test/', $fallback],
            ['https://example.test@evil.test/', $fallback],
            ['https://user@example.test/', $fallback],
            ['http://example.test/spotweb/', $fallback],
            ['https://example.test:444/spotweb/', $fallback],
            ['//example.test/spotweb/', $fallback],
            ['data:text/html,hello', $fallback],
            ['https:///evil.test/', $fallback],
            [
                'https://example.test:8443/spotweb/',
                'https://example.test:8443/spotweb/',
                'https://example.test:8443/spotweb/',
            ],
            [
                'https://example.test/spotweb/',
                'https://example.test:8443/spotweb/',
                'https://example.test:8443/spotweb/',
            ],
            ['', $fallback],
            [null, $fallback],
            [[], $fallback],
            ['/', '/'],
            ['/spotweb/?page=index#spots', '/spotweb/?page=index#spots'],
            ['index.php?page=index', 'index.php?page=index'],
            ['?page=index', '?page=index'],
            ['https://example.test/spotweb/', 'https://example.test/spotweb/'],
            ['https://EXAMPLE.test:443/spotweb/', 'https://EXAMPLE.test:443/spotweb/'],
        ];
        foreach (array_merge(range(0, 32), [127]) as $code) {
            $cases[] = ['/'.chr($code).'/evil.test/', $fallback];
        }

        return $cases;
    }
}
