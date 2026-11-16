<?php

namespace Klaviyo\Reclaim\Test\Unit\Cron;

use Klaviyo\Reclaim\Cron\KlSyncs;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Webhook;
use Klaviyo\Reclaim\Model\ResourceModel\Syncs\CollectionFactory;
use PHPUnit\Framework\TestCase;

class KlSyncsTest extends TestCase
{
    private $statusUpdates = [];

    private function makeCollectionFactory(array $rows)
    {
        $statusUpdates = &$this->statusUpdates;
        $collection = new class ($rows, $statusUpdates) {
            private $rows;
            private $statusUpdates;

            public function __construct(array $rows, array &$statusUpdates)
            {
                $this->rows = $rows;
                $this->statusUpdates = &$statusUpdates;
            }

            public function getRowsForSync($status)
            {
                return $this;
            }

            public function getData()
            {
                return $this->rows;
            }

            public function updateRowStatus(array $ids, $status)
            {
                $this->statusUpdates[$status] = array_merge($this->statusUpdates[$status] ?? [], $ids);
            }
        };

        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        return $factory;
    }

    private function makeRow(int $id, int $storeId): array
    {
        return [
            'id' => $id,
            'topic' => 'Added To Cart',
            'user_properties' => json_encode(['$email' => 'buyer@example.com']),
            'payload' => json_encode(['time' => 1700000000, 'StoreId' => $storeId]),
        ];
    }

    private function makeCron(array $rows, Data $dataHelper, array $enabledByStore): KlSyncs
    {
        $scope = $this->getMockBuilder(ScopeSetting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scope->method('isEnabled')->willReturnCallback(
            function ($storeId) use ($enabledByStore) {
                return $enabledByStore[$storeId] ?? null;
            }
        );

        return new KlSyncs(
            $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock(),
            $this->makeCollectionFactory($rows),
            $dataHelper,
            $this->getMockBuilder(Webhook::class)->disableOriginalConstructor()->getMock(),
            $scope
        );
    }

    public function testSyncSkipsTrackApiCallsForStoresWithKlaviyoDisabled()
    {
        $dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataHelper->expects($this->once())
            ->method('klaviyoTrackEvent')
            ->with('Added To Cart', $this->anything(), $this->anything(), 1700000000, 2)
            ->willReturn([]);

        $cron = $this->makeCron([$this->makeRow(1, 1), $this->makeRow(2, 2)], $dataHelper, [1 => '0', 2 => '1']);
        $cron->sync();

        $this->assertSame([2], $this->statusUpdates['SYNCED']);
        $this->assertSame([1], $this->statusUpdates['RETRY']);
    }

    public function testSyncMakesNoTrackApiCallsWhenKlaviyoIsDisabledEverywhere()
    {
        $dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataHelper->expects($this->never())->method('klaviyoTrackEvent');

        $cron = $this->makeCron([$this->makeRow(1, 1)], $dataHelper, [1 => '0']);
        $cron->retry();

        $this->assertSame([1], $this->statusUpdates['FAILED']);
    }
}
