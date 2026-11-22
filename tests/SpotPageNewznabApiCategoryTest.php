<?php

use PHPUnit\Framework\TestCase;

class SpotPageNewznabApiCategoryTest extends TestCase
{
    private $page;

    protected function setUp(): void
    {
        $this->page = (new ReflectionClass(SpotPage_newznabapi::class))->newInstanceWithoutConstructor();
    }

    public function testMappedGameAndSoftwareCategoriesAreReturned()
    {
        $this->assertSame('4000|4050', $this->page->Cat2NewznabCat(2, 'a0', ''));
        $this->assertSame('4000|4020', $this->page->Cat2NewznabCat(3, 'a0', ''));
    }

    public function testImageCategoriesContinueToUseSubcategoryZ()
    {
        $this->assertSame('2000|2030', $this->page->Cat2NewznabCat(0, 'a0', 'z0'));
    }

    public function testUnmappedGameAndSoftwareCategoriesReturnEmptyStrings()
    {
        $this->assertSame('', $this->page->Cat2NewznabCat(2, 'a999', 'z0'));
        $this->assertSame('', $this->page->Cat2NewznabCat(3, 'a999', 'z0'));
    }

    public function testMissingAdditionalCategoryReturnsEmptyString()
    {
        $this->assertSame('', $this->page->Cat2NewznabCat(2, 'b999', 'z0'));
    }

    public function testMappedAdditionalCategoryIsPreserved()
    {
        $this->assertSame('5000', $this->page->Cat2NewznabCat(2, 'b4', 'z0'));
    }
}
