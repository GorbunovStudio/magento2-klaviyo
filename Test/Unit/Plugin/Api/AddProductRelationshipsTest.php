<?php

namespace Klaviyo\Reclaim\Test\Unit\Plugin\Api;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Plugin\Api\AddProductRelationships;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class AddProductRelationshipsTest extends TestCase
{
    private function makeScope(array $enabledByStore)
    {
        $scope = $this->getMockBuilder(ScopeSetting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scope->method('isEnabled')->willReturnCallback(
            function ($storeId) use ($enabledByStore) {
                return $enabledByStore[$storeId] ?? null;
            }
        );

        return $scope;
    }

    private function makeProduct(int $id, int $storeId, DataObject $extensionAttributes)
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($id);
        $product->method('getStoreId')->willReturn($storeId);
        $product->method('getExtensionAttributes')->willReturn($extensionAttributes);

        return $product;
    }

    public function testGetDoesNotQueryParentsWhenKlaviyoIsDisabled()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->never())->method('getParentIdsByChild');
        $extensionAttributes = new DataObject();
        $product = $this->makeProduct(10, 1, $extensionAttributes);

        $plugin = new AddProductRelationships($configurable, $this->makeScope([1 => '0']));
        $result = $plugin->afterGet($this->createMock(ProductRepositoryInterface::class), $product);

        $this->assertSame($product, $result);
        $this->assertNull($extensionAttributes->getData('kl_parent_ids'));
    }

    public function testGetAddsParentIdsWhenKlaviyoIsEnabled()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->once())->method('getParentIdsByChild')->with(10)->willReturn([5]);
        $extensionAttributes = new DataObject();
        $product = $this->makeProduct(10, 1, $extensionAttributes);

        $plugin = new AddProductRelationships($configurable, $this->makeScope([1 => '1']));
        $plugin->afterGet($this->createMock(ProductRepositoryInterface::class), $product);

        $this->assertSame([5], $extensionAttributes->getData('kl_parent_ids'));
    }

    public function testGetListLeavesResultsUntouchedWhenKlaviyoIsDisabled()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->never())->method('getParentIdsByChild');
        $scope = $this->getMockBuilder(ScopeSetting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scope->expects($this->once())->method('isEnabled')->with(1)->willReturn('0');

        $searchResults = $this->createMock(ProductSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([
            $this->makeProduct(10, 1, new DataObject()),
            $this->makeProduct(11, 1, new DataObject()),
        ]);
        $searchResults->expects($this->never())->method('setItems');

        $plugin = new AddProductRelationships($configurable, $scope);
        $result = $plugin->afterGetList($this->createMock(ProductRepositoryInterface::class), $searchResults);

        $this->assertSame($searchResults, $result);
    }

    public function testGetListAddsParentIdsOnlyForEnabledStores()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->once())->method('getParentIdsByChild')->with(11)->willReturn([7]);
        $disabledAttributes = new DataObject();
        $enabledAttributes = new DataObject();
        $disabledProduct = $this->makeProduct(10, 1, $disabledAttributes);
        $enabledProduct = $this->makeProduct(11, 2, $enabledAttributes);

        $searchResults = $this->createMock(ProductSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([$disabledProduct, $enabledProduct]);
        $searchResults->expects($this->once())->method('setItems')->with([$disabledProduct, $enabledProduct]);

        $plugin = new AddProductRelationships($configurable, $this->makeScope([1 => '0', 2 => '1']));
        $plugin->afterGetList($this->createMock(ProductRepositoryInterface::class), $searchResults);

        $this->assertNull($disabledAttributes->getData('kl_parent_ids'));
        $this->assertSame([7], $enabledAttributes->getData('kl_parent_ids'));
    }
}
