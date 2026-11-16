<?php

namespace Magento\Framework\App\Config;

if (!interface_exists(\Magento\Framework\App\Config\ScopeConfigInterface::class)) {
    interface ScopeConfigInterface
    {
        const SCOPE_TYPE_DEFAULT = 'default';

        public function getValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null);

        public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null);
    }
}
