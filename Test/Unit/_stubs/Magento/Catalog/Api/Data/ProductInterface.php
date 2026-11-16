<?php

namespace Magento\Catalog\Api\Data;

if (!interface_exists(\Magento\Catalog\Api\Data\ProductInterface::class)) {
    interface ProductInterface
    {
        public function getId();

        public function getExtensionAttributes();

        public function setExtensionAttributes(\Magento\Catalog\Api\Data\ProductExtensionInterface $extensionAttributes);
    }
}
