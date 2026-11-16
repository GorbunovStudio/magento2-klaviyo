<?php

namespace Klaviyo\Reclaim\Model\ResourceModel\Syncs;

if (!class_exists(\Klaviyo\Reclaim\Model\ResourceModel\Syncs\CollectionFactory::class, false)) {
    class CollectionFactory
    {
        public function create(array $data = [])
        {
            return null;
        }
    }
}
