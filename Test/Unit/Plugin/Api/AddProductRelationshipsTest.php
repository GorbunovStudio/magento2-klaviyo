<?php

namespace Klaviyo\Reclaim\Test\Unit\Plugin\Api;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Plugin\Api\AddProductRelationships;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class AddProductRelationshipsTest extends TestCase
{
    private const CURRENT_STORE_ID = 1;

    private function makeStoreManager()
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::CURRENT_STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return $storeManager;
    }

    private function makeScope($expectedStoreId, $enabled)
    {
        $scope = $this->getMockBuilder(ScopeSetting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scope->expects($this->once())
            ->method('isEnabled')
            ->with($expectedStoreId)
            ->willReturn($enabled ? '1' : '0');

        return $scope;
    }

    private function makeProduct(int $id, ProductExtensionInterface $extensionAttributes)
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($id);
        $product->method('getExtensionAttributes')->willReturn($extensionAttributes);

        return $product;
    }

    public function testGetListLeavesResultsUntouchedWhenKlaviyoIsDisabledForCurrentStore()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->never())->method('getParentIdsByChild');
        $extensionAttributes = $this->createMock(ProductExtensionInterface::class);
        $extensionAttributes->expects($this->never())->method('setKlParentIds');

        $searchResults = $this->createMock(ProductSearchResultsInterface::class);
        $searchResults->expects($this->never())->method('getItems');
        $searchResults->expects($this->never())->method('setItems');

        $plugin = new AddProductRelationships(
            $configurable,
            $this->makeScope(self::CURRENT_STORE_ID, false),
            $this->makeStoreManager()
        );
        $result = $plugin->afterGetList($this->createMock(ProductRepositoryInterface::class), $searchResults);

        $this->assertSame($searchResults, $result);
    }

    public function testGetListAddsParentIdsWhenKlaviyoIsEnabledForCurrentStore()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->method('getParentIdsByChild')->willReturnMap([[10, [5]], [11, [7, 8]]]);
        $firstAttributes = $this->createMock(ProductExtensionInterface::class);
        $firstAttributes->expects($this->once())->method('setKlParentIds')->with([5]);
        $secondAttributes = $this->createMock(ProductExtensionInterface::class);
        $secondAttributes->expects($this->once())->method('setKlParentIds')->with([7, 8]);
        $firstProduct = $this->makeProduct(10, $firstAttributes);
        $secondProduct = $this->makeProduct(11, $secondAttributes);

        $searchResults = $this->createMock(ProductSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([$firstProduct, $secondProduct]);
        $searchResults->expects($this->once())->method('setItems')->with([$firstProduct, $secondProduct]);

        $plugin = new AddProductRelationships(
            $configurable,
            $this->makeScope(self::CURRENT_STORE_ID, true),
            $this->makeStoreManager()
        );
        $plugin->afterGetList($this->createMock(ProductRepositoryInterface::class), $searchResults);
    }

    public function testGetDoesNotQueryParentsWhenKlaviyoIsDisabledForCurrentStore()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->never())->method('getParentIdsByChild');
        $extensionAttributes = $this->createMock(ProductExtensionInterface::class);
        $extensionAttributes->expects($this->never())->method('setKlParentIds');
        $product = $this->makeProduct(10, $extensionAttributes);

        $plugin = new AddProductRelationships(
            $configurable,
            $this->makeScope(self::CURRENT_STORE_ID, false),
            $this->makeStoreManager()
        );
        $result = $plugin->afterGet($this->createMock(ProductRepositoryInterface::class), $product, 'sku-10');

        $this->assertSame($product, $result);
    }

    public function testGetAddsParentIdsWhenKlaviyoIsEnabledForCurrentStore()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->once())->method('getParentIdsByChild')->with(10)->willReturn([5]);
        $extensionAttributes = $this->createMock(ProductExtensionInterface::class);
        $extensionAttributes->expects($this->once())->method('setKlParentIds')->with([5]);
        $product = $this->makeProduct(10, $extensionAttributes);
        $product->expects($this->once())->method('setExtensionAttributes')->with($extensionAttributes);

        $plugin = new AddProductRelationships(
            $configurable,
            $this->makeScope(self::CURRENT_STORE_ID, true),
            $this->makeStoreManager()
        );
        $plugin->afterGet($this->createMock(ProductRepositoryInterface::class), $product, 'sku-10');
    }

    public function testGetUsesExplicitStoreIdInsteadOfCurrentStore()
    {
        $configurable = $this->createMock(Configurable::class);
        $configurable->expects($this->never())->method('getParentIdsByChild');
        $product = $this->makeProduct(10, $this->createMock(ProductExtensionInterface::class));
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->expects($this->never())->method('getStore');

        $plugin = new AddProductRelationships($configurable, $this->makeScope(4, false), $storeManager);
        $plugin->afterGet($this->createMock(ProductRepositoryInterface::class), $product, 'sku-10', false, 4);
    }
}
