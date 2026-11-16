<?php

namespace Magento\ConfigurableProduct\Model\ResourceModel\Product\Type;

if (!class_exists(\Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable::class, false)) {
    class Configurable
    {
        public function getParentIdsByChild($childId)
        {
            return [];
        }
    }
}
