<?php

namespace Magento\Framework\Pricing\Price;

if (!interface_exists(\Magento\Framework\Pricing\Price\PriceInterface::class)) {
    interface PriceInterface
    {
        public function getValue();

        public function getAmount();
    }
}
