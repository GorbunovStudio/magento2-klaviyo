<?php

namespace Magento\Catalog\Api\Data;

if (!interface_exists(\Magento\Catalog\Api\Data\ProductInterface::class, false)) {
    interface ProductInterface
    {
        public function getId();

        public function getStoreId();

        public function getExtensionAttributes();

        public function setExtensionAttributes($extensionAttributes);
    }
}
