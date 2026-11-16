<?php

namespace Magento\Framework\Option;

if (!interface_exists(\Magento\Framework\Option\ArrayInterface::class)) {
    interface ArrayInterface
    {
        public function toOptionArray();
    }
}
