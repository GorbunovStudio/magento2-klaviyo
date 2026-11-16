<?php

namespace Klaviyo\Reclaim\Test\Unit\Helper;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoResourceConflictException;
use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Klaviyo\Reclaim\Test\Unit\Helper\Stubs\TestableDataHelper;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;

class DataSubscribeTest extends TestCase
{
    private const EMAIL = 'buyer@example.com';
    private const LIST_ID = 'custom-list';
    private const PROPERTIES = ['Brand' => 'Budsies'];

    private function makeHelper(KlaviyoV3Api $api): Data
    {
        $scope = $this->getMockBuilder(ScopeSetting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scope->method('getOptInSetting')->willReturn(null);
        $scope->method('getNewsletter')->willReturn('default-list');

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new TestableDataHelper(new Context(), $logger, $scope, $api);
    }

    private function makeApiMock()
    {
        return $this->getMockBuilder(KlaviyoV3Api::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function makeConflict(?string $duplicateProfileId): KlaviyoResourceConflictException
    {
        $body = $duplicateProfileId === null
            ? ['errors' => [['meta' => []]]]
            : ['errors' => [['meta' => ['duplicate_profile_id' => $duplicateProfileId]]]];

        return new KlaviyoResourceConflictException('conflict', 409, null, $body);
    }

    public function testConflictWithDuplicateProfileIdUpdatesCustomProperties()
    {
        $api = $this->makeApiMock();
        $api->method('searchProfileByEmail')->willReturn(false);
        $api->method('createProfile')->willThrowException($this->makeConflict('dup-id'));
        $api->expects($this->once())->method('addProfileToList')->with(self::LIST_ID, 'dup-id');
        $api->expects($this->once())
            ->method('updateProfile')
            ->with('dup-id', 'Ann', 'Lee', self::PROPERTIES);

        $this->makeHelper($api)->subscribeEmailToKlaviyoList(
            self::EMAIL,
            'Ann',
            'Lee',
            1,
            self::PROPERTIES,
            self::LIST_ID
        );
    }

    public function testConflictWithoutDuplicateProfileIdUpdatesCustomPropertiesOfFoundProfile()
    {
        $api = $this->makeApiMock();
        $api->method('searchProfileByEmail')
            ->willReturnOnConsecutiveCalls(false, ['profile_id' => 'found-id']);
        $api->method('createProfile')->willThrowException($this->makeConflict(null));
        $api->expects($this->once())->method('addProfileToList')->with(self::LIST_ID, 'found-id');
        $api->expects($this->once())
            ->method('updateProfile')
            ->with('found-id', 'Ann', 'Lee', self::PROPERTIES);

        $this->makeHelper($api)->subscribeEmailToKlaviyoList(
            self::EMAIL,
            'Ann',
            'Lee',
            1,
            self::PROPERTIES,
            self::LIST_ID
        );
    }

    public function testConflictWithoutCustomPropertiesDoesNotUpdateProfile()
    {
        $api = $this->makeApiMock();
        $api->method('searchProfileByEmail')->willReturn(false);
        $api->method('createProfile')->willThrowException($this->makeConflict('dup-id'));
        $api->expects($this->once())->method('addProfileToList')->with(self::LIST_ID, 'dup-id');
        $api->expects($this->never())->method('updateProfile');

        $this->makeHelper($api)->subscribeEmailToKlaviyoList(self::EMAIL, 'Ann', 'Lee', 1, null, self::LIST_ID);
    }

    public function testNewProfileIsCreatedWithCustomPropertiesWithoutSeparateUpdate()
    {
        $api = $this->makeApiMock();
        $api->method('searchProfileByEmail')->willReturn(false);
        $api->expects($this->once())
            ->method('createProfile')
            ->with([
                'email' => self::EMAIL,
                'first_name' => 'Ann',
                'last_name' => 'Lee',
                'properties' => self::PROPERTIES,
            ])
            ->willReturn(['profile_id' => 'new-id']);
        $api->expects($this->once())->method('addProfileToList')->with(self::LIST_ID, 'new-id');
        $api->expects($this->never())->method('updateProfile');

        $this->makeHelper($api)->subscribeEmailToKlaviyoList(
            self::EMAIL,
            'Ann',
            'Lee',
            1,
            self::PROPERTIES,
            self::LIST_ID
        );
    }

    public function testExistingProfileUpdatesCustomProperties()
    {
        $api = $this->makeApiMock();
        $api->method('searchProfileByEmail')->willReturn(['profile_id' => 'existing-id']);
        $api->expects($this->never())->method('createProfile');
        $api->expects($this->once())->method('addProfileToList')->with(self::LIST_ID, 'existing-id');
        $api->expects($this->once())
            ->method('updateProfile')
            ->with('existing-id', 'Ann', 'Lee', self::PROPERTIES);

        $this->makeHelper($api)->subscribeEmailToKlaviyoList(
            self::EMAIL,
            'Ann',
            'Lee',
            1,
            self::PROPERTIES,
            self::LIST_ID
        );
    }
}
