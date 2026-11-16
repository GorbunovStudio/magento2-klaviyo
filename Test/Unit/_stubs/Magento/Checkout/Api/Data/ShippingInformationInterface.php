<?php

namespace Magento\Checkout\Api\Data;

if (!interface_exists(\Magento\Checkout\Api\Data\ShippingInformationInterface::class)) {
    interface ShippingInformationInterface
    {
        public function getExtensionAttributes();
    }
}
