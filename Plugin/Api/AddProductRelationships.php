<?php

namespace Klaviyo\Reclaim\Plugin\Api;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class AddProductRelationships
{
    protected $configurableProductType;

    /**
     * @var ScopeSetting
     */
    protected $klaviyoScopeSetting;

    public function __construct(
        Configurable $configurableProductType,
        ScopeSetting $klaviyoScopeSetting
    ) {
        $this->configurableProductType = $configurableProductType;
        $this->klaviyoScopeSetting = $klaviyoScopeSetting;
    }

    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $entity
    ) {
        if (!$this->klaviyoScopeSetting->isEnabled($entity->getStoreId())) {
            return $entity;
        }

        $this->addParentIds($entity);

        return $entity;
    }

    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ) {
        $enabledByStore = [];
        $products = [];
        $isChanged = false;
        foreach ($searchResults->getItems() as $key => $entity) {
            $storeId = $entity->getStoreId();
            if (!array_key_exists($storeId, $enabledByStore)) {
                $enabledByStore[$storeId] = (bool) $this->klaviyoScopeSetting->isEnabled($storeId);
            }

            if ($enabledByStore[$storeId]) {
                $this->addParentIds($entity);
                $isChanged = true;
            }

            $products[$key] = $entity;
        }

        if ($isChanged) {
            $searchResults->setItems(array_values($products));
        }

        return $searchResults;
    }

    private function addParentIds(ProductInterface $entity)
    {
        $extensionAttributes = $entity->getExtensionAttributes();
        $parentIds = $this->configurableProductType->getParentIdsByChild($entity->getId());
        $extensionAttributes->setData('kl_parent_ids', $parentIds);
        $entity->setExtensionAttributes($extensionAttributes);
    }
}
