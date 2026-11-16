<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Observer\SalesQuoteProductAddAfter;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class SalesQuoteProductAddAfterTest extends TestCase
{
    public function testKlaviyoDisabledSkipsAddedToCartPayloadPreparation()
    {
        $quote = new class {
            public function getStoreId()
            {
                return 3;
            }
        };
        $item = new class ($quote) {
            private $quote;

            public function __construct($quote)
            {
                $this->quote = $quote;
            }

            public function getQuote()
            {
                return $this->quote;
            }

            public function getProductType()
            {
                throw new \LogicException('Added to cart items must not be processed when Klaviyo is disabled');
            }
        };
        $scope = $this->getMockBuilder(ScopeSetting::class)->disableOriginalConstructor()->getMock();
        $scope->expects($this->once())->method('isEnabled')->with(3)->willReturn('0');

        $dataHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $dataHelper->expects($this->never())->method('setObserverAtcPayload');
        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $productRepository->expects($this->never())->method('get');
        $categoryFactory = $this->createMock(CategoryFactory::class);
        $categoryFactory->expects($this->never())->method('create');

        $observer = new SalesQuoteProductAddAfter(
            $dataHelper,
            $categoryFactory,
            $productRepository,
            $this->getMockBuilder(Image::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock(),
            $scope
        );

        $magentoObserver = new class ([$item]) extends Observer {
            private $items;

            public function __construct(array $items)
            {
                $this->items = $items;
            }

            public function getData($key = '', $index = null)
            {
                return $key === 'items' ? $this->items : null;
            }
        };

        $observer->execute($magentoObserver);
    }
}
