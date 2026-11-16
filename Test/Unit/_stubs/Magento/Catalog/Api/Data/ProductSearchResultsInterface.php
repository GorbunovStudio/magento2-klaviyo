<?php

namespace Magento\Catalog\Api\Data;

if (!interface_exists(\Magento\Catalog\Api\Data\ProductSearchResultsInterface::class, false)) {
    interface ProductSearchResultsInterface
    {
        public function getItems();

        public function setItems(array $items);
    }
}
