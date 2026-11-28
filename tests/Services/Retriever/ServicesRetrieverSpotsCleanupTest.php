<?php

use PHPUnit\Framework\TestCase;

class ServicesRetrieverSpotsCleanupTestSettings
{
    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function get($name)
    {
        return $this->settings[$name];
    }
}

class ServicesRetrieverSpotsCleanupTestRunner extends Services_Retriever_Spots
{
    public function displayStatus($cat, $txt)
    {
    }

    public function hasMemoryPressure()
    {
        return false;
    }
}

class ServicesRetrieverSpotsCleanupTest extends TestCase
{
    public function testKnownSpotAtEndOfRunDoesNotTriggerExtraSpotCleanup()
    {
        $messageId = 'known-spot@example.invalid';
        $header = ['Number' => 1, 'Message-ID' => $messageId];

        $spotDao = $this->createMock(Dao_Spot::class);
        $spotDao->expects($this->once())
            ->method('matchSpotMessageIds')
            ->with([$header])
            ->willReturn(['spot' => [$messageId => 1], 'fullspot' => []]);
        $spotDao->expects($this->once())->method('addSpots')->with([], []);
        $spotDao->expects($this->never())->method('removeExtraSpots');

        $cacheDao = $this->createMock(Dao_Cache::class);
        $cacheDao->expects($this->once())->method('getMassCacheRecords')->with([$header])->willReturn([]);

        $modListDao = $this->createMock(Dao_ModeratedRingBuffer::class);
        $modListDao->expects($this->once())->method('matchAgainst')->with([$header])->willReturn([]);
        $modListDao->expects($this->once())->method('deleteOldest');

        $usenetStateDao = $this->createMock(Dao_UsenetState::class);
        $usenetStateDao->expects($this->once())
            ->method('setMaxArticleId')
            ->with(Dao_UsenetState::State_Spots, 1, $messageId);

        $transport = $this->createMock(Services_Nntp_PipelinedTransport::class);
        $transport->expects($this->once())->method('getOverview')->with(1, 1001)->willReturn([$header]);

        $runner = (new ReflectionClass(ServicesRetrieverSpotsCleanupTestRunner::class))->newInstanceWithoutConstructor();
        $this->setProperty(Services_Retriever_Base::class, '_settings', $runner, new ServicesRetrieverSpotsCleanupTestSettings([
            'retention'           => 0,
            'retentiontype'       => 'everything',
            'retrieve_newer_than' => 0,
            'spot_moderation'     => 'disable',
        ]));
        $this->setProperty(Services_Retriever_Base::class, '_usenetStateDao', $runner, $usenetStateDao);
        $this->setProperty(Services_Retriever_Base::class, '_svcNntpTextPipelined', $runner, $transport);
        $this->setProperty(Services_Retriever_Base::class, '_msgdata', $runner, ['first' => 1, 'last' => 2]);
        $this->setProperty(Services_Retriever_Spots::class, '_spotDao', $runner, $spotDao);
        $this->setProperty(Services_Retriever_Spots::class, '_cacheDao', $runner, $cacheDao);
        $this->setProperty(Services_Retriever_Spots::class, '_modListDao', $runner, $modListDao);
        $this->setProperty(Services_Retriever_Spots::class, '_retrieveFull', $runner, false);
        $this->setProperty(Services_Retriever_Spots::class, '_prefetch_image', $runner, false);
        $this->setProperty(Services_Retriever_Spots::class, '_prefetch_nzb', $runner, false);
        $this->setProperty(Services_Retriever_Base::class, '_retro', $runner, false);

        $runner->loopTillEnd(1);
    }

    private function setProperty($className, $propertyName, $object, $value)
    {
        $property = new ReflectionProperty($className, $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
