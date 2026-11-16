<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use Klaviyo\Reclaim\Api\Data\PropertiesInterfaceFactory;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Model\Properties;
use Klaviyo\Reclaim\Observer\NewsletterSubscribeObserver;
use Klaviyo\Reclaim\Test\Data\SampleCustomer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\TestCase;

class NewsletterSubscribeObserverTest extends TestCase
{
    private const STORE_ID = 2;

    private $dataHelper;
    private $scopeSetting;
    private $eventManager;
    private $properties;

    protected function setUp(): void
    {
        $this->dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $this->scopeSetting = $this->getMockBuilder(ScopeSetting::class)->disableOriginalConstructor()->getMock();
        $this->eventManager = $this->createMock(ManagerInterface::class);
        $this->properties = new Properties();
    }

    private function makeObserver(): NewsletterSubscribeObserver
    {
        $propertiesFactory = $this->createMock(PropertiesInterfaceFactory::class);
        $propertiesFactory->method('create')->willReturn($this->properties);

        return new NewsletterSubscribeObserver(
            $this->dataHelper,
            $this->scopeSetting,
            $this->createMock(CustomerRepositoryInterface::class),
            $this->eventManager,
            $propertiesFactory
        );
    }

    private function makeSubscriber(int $status)
    {
        $subscriber = $this->createMock(Subscriber::class);
        $subscriber->method('getId')->willReturn(10);
        $subscriber->method('getStatus')->willReturn($status);
        $subscriber->method('getStoreId')->willReturn(self::STORE_ID);
        $subscriber->method('getEmail')->willReturn(SampleCustomer::CUSTOMER_EMAIL);
        $subscriber->method('getFirstname')->willReturn('Ann');
        $subscriber->method('getLastname')->willReturn('Lee');
        $subscriber->method('getCustomerId')->willReturn(null);
        $subscriber->method('isStatusChanged')->willReturn(true);

        return $subscriber;
    }

    private function makeMagentoObserver($subscriber): Observer
    {
        $observer = $this->createMock(Observer::class);
        $observer->method('getDataObject')->willReturn($subscriber);

        return $observer;
    }

    public function testSubscribeSendsCustomPropertiesForSubscriberStore()
    {
        $this->scopeSetting->method('isEnabled')->with(self::STORE_ID)->willReturn('1');
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('before_subscribe_email_to_klaviyo_list', $this->callback(function (array $data) {
                $data['properties']->setData('properties', ['Brand' => 'Budsies']);
                return $data['properties'] === $this->properties;
            }));
        $this->dataHelper->expects($this->once())
            ->method('subscribeEmailToKlaviyoList')
            ->with(SampleCustomer::CUSTOMER_EMAIL, 'Ann', 'Lee', self::STORE_ID, ['Brand' => 'Budsies']);
        $this->dataHelper->expects($this->never())->method('unsubscribeEmailFromKlaviyoList');

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_SUBSCRIBED);
        $this->makeObserver()->execute($this->makeMagentoObserver($subscriber));
    }

    public function testUnsubscribeUsesSubscriberStore()
    {
        $this->scopeSetting->method('isEnabled')->with(self::STORE_ID)->willReturn('1');
        $this->dataHelper->expects($this->never())->method('subscribeEmailToKlaviyoList');
        $this->dataHelper->expects($this->once())
            ->method('unsubscribeEmailFromKlaviyoList')
            ->with(SampleCustomer::CUSTOMER_EMAIL, self::STORE_ID);

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_UNSUBSCRIBED);
        $this->makeObserver()->execute($this->makeMagentoObserver($subscriber));
    }

    public function testKlaviyoDisabledForSubscriberStoreDoesNothing()
    {
        $this->scopeSetting->method('isEnabled')->with(self::STORE_ID)->willReturn('0');
        $this->eventManager->expects($this->never())->method('dispatch');
        $this->dataHelper->expects($this->never())->method('subscribeEmailToKlaviyoList');
        $this->dataHelper->expects($this->never())->method('unsubscribeEmailFromKlaviyoList');

        $subscriber = $this->makeSubscriber(Subscriber::STATUS_SUBSCRIBED);
        $this->makeObserver()->execute($this->makeMagentoObserver($subscriber));
    }

    public function testMissingSubscriberDoesNothing()
    {
        $this->scopeSetting->expects($this->never())->method('isEnabled');
        $this->dataHelper->expects($this->never())->method('subscribeEmailToKlaviyoList');

        $this->makeObserver()->execute($this->makeMagentoObserver(null));
    }
}
