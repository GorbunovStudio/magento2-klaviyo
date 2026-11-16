<?php

namespace Magento\Framework\App\Helper;

if (!class_exists(\Magento\Framework\App\Helper\AbstractHelper::class)) {
    abstract class AbstractHelper
    {
        public function __construct($context = null)
        {
        }
    }
}
