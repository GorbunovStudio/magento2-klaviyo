<?php

namespace Klaviyo\Reclaim\Plugin\Api;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddProductRelationships
{
    protected $configurableProductType;

    /**
     * @var ScopeSetting
     */
    protected $klaviyoScopeSetting;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        Configurable $configurableProductType,
        ScopeSetting $klaviyoScopeSetting,
        StoreManagerInterface $storeManager
    ) {
        $this->configurableProductType = $configurableProductType;
        $this->klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->storeManager = $storeManager;
    }

    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $result,
        $sku,
        $editMode = false,
        $storeId = null,
        $forceReload = false
    ) {
        if (!$this->isEnabledForStore($storeId)) {
            return $result;
        }

        $this->addParentIds($result);

        return $result;
    }

    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ) {
        if (!$this->isEnabledForStore(null)) {
            return $searchResults;
        }

        $products = [];
        foreach ($searchResults->getItems() as $entity) {
            $this->addParentIds($entity);
            $products[] = $entity;
        }
        $searchResults->setItems($products);

        return $searchResults;
    }

    /**
     * Checks the Klaviyo enable flag for the given store, or for the store of the current request
     *
     * @param int|string|null $storeId
     * @return bool
     */
    private function isEnabledForStore($storeId)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return (bool) $this->klaviyoScopeSetting->isEnabled($storeId);
    }

    private function addParentIds(ProductInterface $entity)
    {
        $extensionAttributes = $entity->getExtensionAttributes();
        $parentIds = $this->configurableProductType->getParentIdsByChild($entity->getId());
        $extensionAttributes->setKlParentIds($parentIds);
        $entity->setExtensionAttributes($extensionAttributes);
    }
}
