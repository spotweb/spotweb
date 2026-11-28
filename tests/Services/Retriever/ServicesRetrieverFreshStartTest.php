<?php

use PHPUnit\Framework\TestCase;

/**
 * Only a regular first retrieve of spots or comments may skip old articles, a
 * --retro run, an incremental run and the reports always follow the old logic.
 */
class ServicesRetrieverFreshStartTest extends TestCase
{
    public function testFreshSpotsRetrieveStartsAtLocatedArticle()
    {
        $retriever = $this->newRetriever(Services_Retriever_Spots::class, false, 0, ['locateStartArticle']);
        $retriever->expects($this->once())->method('locateStartArticle')->willReturn(4458490);
        $retriever->expects($this->once())->method('loopTillEnd')->with(4458490)->willReturn(1);

        $retriever->perform();
    }

    public function testFreshCommentsRetrieveStartsAtLocatedArticle()
    {
        $retriever = $this->newRetriever(Services_Retriever_Comments::class, false, 0, ['locateStartArticle']);
        $retriever->expects($this->once())->method('locateStartArticle')->willReturn(22179085);
        $retriever->expects($this->once())->method('loopTillEnd')->with(22179085)->willReturn(1);

        $retriever->perform();
    }

    public function testRetroRetrieveStartsAtFirstArticle()
    {
        $retriever = $this->newRetriever(Services_Retriever_Spots::class, true, 0, ['locateStartArticle']);
        $retriever->expects($this->never())->method('locateStartArticle');
        $retriever->expects($this->once())->method('loopTillEnd')->with(0)->willReturn(1);

        $retriever->perform();
    }

    public function testIncrementalRetrieveDoesNotLocate()
    {
        $retriever = $this->newRetriever(Services_Retriever_Spots::class, false, 5000, ['locateStartArticle', 'searchMessageId']);
        $retriever->expects($this->never())->method('locateStartArticle');
        $retriever->expects($this->once())->method('searchMessageId')->willReturn(4999);
        $retriever->expects($this->once())->method('loopTillEnd')->with(4999)->willReturn(1);

        $retriever->perform();
    }

    public function testReportsAlwaysStartAtFirstArticle()
    {
        $retriever = $this->newRetriever(Services_Retriever_Reports::class, false, 0, []);
        $retriever->expects($this->once())->method('loopTillEnd')->with(0)->willReturn(1);

        $retriever->perform();
    }

    private function newRetriever($class, $retro, $lastArticleNr, array $extraMethods)
    {
        $retriever = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->setMethods(array_merge(['connect', 'getLastArticleNumber', 'getLastMessageId', 'getRecentRetrievedMessageIdList', 'loopTillEnd', 'quit'], $extraMethods))
            ->getMock();
        $retriever->method('getLastArticleNumber')->willReturn($lastArticleNr);

        $settings = $this->createMock(Services_Settings_Container::class);
        $settings->method('get')->willReturn(1000);

        $this->setProperty($retriever, '_retro', $retro);
        $this->setProperty($retriever, '_settings', $settings);
        $this->setProperty($retriever, '_textServer', ['buggy' => false]);
        $this->setProperty($retriever, '_usenetStateDao', $this->createMock(Dao_UsenetState::class));

        return $retriever;
    }

    private function setProperty($object, $name, $value)
    {
        $property = new ReflectionProperty(Services_Retriever_Base::class, $name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
