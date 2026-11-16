<?php

namespace Klaviyo\Reclaim\Api\Data;

if (!class_exists(\Klaviyo\Reclaim\Api\Data\PropertiesInterfaceFactory::class, false)) {
    class PropertiesInterfaceFactory
    {
        public function create(array $data = [])
        {
            return null;
        }
    }
}
