<?php

namespace Magento\Catalog\Api\Data;

if (!interface_exists(\Magento\Catalog\Api\Data\ProductSearchResultsInterface::class)) {
    interface ProductSearchResultsInterface
    {
        public function getItems();

        public function setItems(array $items);
    }
}
