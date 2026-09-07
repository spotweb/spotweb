<?php

use PHPUnit\Framework\TestCase;

class SpotStructSpotsfullSchemaTest extends TestCase
{
    private function spotStructSource()
    {
        return file_get_contents(__DIR__.'/../../lib/dbstruct/SpotStruct_abs.php');
    }

    public function testSpotsfullExternalSignatureColumnsAreWidened()
    {
        $source = $this->spotStructSource();

        $this->assertStringContainsString(
            "\$this->validateColumn('usersignature', 'spotsfull', 'VARCHAR(512)', null, false, 'ascii');",
            $source
        );
        $this->assertStringContainsString(
            "\$this->validateColumn('userkey', 'spotsfull', 'VARCHAR(1024)', null, false, 'ascii');",
            $source
        );
        $this->assertStringContainsString(
            "\$this->validateColumn('xmlsignature', 'spotsfull', 'VARCHAR(512)', null, false, 'ascii');",
            $source
        );
    }

    public function testSpotsfullExternalSignatureColumnsNoLongerUseLegacyLimits()
    {
        $source = $this->spotStructSource();

        $this->assertStringNotContainsString(
            "\$this->validateColumn('usersignature', 'spotsfull', 'VARCHAR(255)', null, false, 'ascii');",
            $source
        );
        $this->assertStringNotContainsString(
            "\$this->validateColumn('userkey', 'spotsfull', 'VARCHAR(512)', null, false, 'ascii');",
            $source
        );
        $this->assertStringNotContainsString(
            "\$this->validateColumn('xmlsignature', 'spotsfull', 'VARCHAR(255)', null, false, 'ascii');",
            $source
        );
    }
}
