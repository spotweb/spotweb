<?php

use Imdb\Parsing;

class ParsingTest extends PHPUnit\Framework\TestCase
{
    public function test_table()
    {
        $result = Parsing::table("<table><tr><td>a</td><td>b</td></tr><tr><td>c</td><td>d</td></tr></table>", "//table");

        $this->assertCount(2, $result);

        $this->assertEquals($result, [
            ['a', 'b'],
            ['c', 'd']
        ]);
    }

    public function test_table_no_table()
    {
        $result = Parsing::table("<div></div>", "//table");

        $this->assertCount(0, $result);
    }
}
