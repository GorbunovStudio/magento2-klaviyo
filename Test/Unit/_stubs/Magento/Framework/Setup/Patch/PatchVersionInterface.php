<?php

namespace Magento\Framework\Setup\Patch;

if (!interface_exists(\Magento\Framework\Setup\Patch\PatchVersionInterface::class)) {
    interface PatchVersionInterface
    {
        public static function getVersion();
    }
}
