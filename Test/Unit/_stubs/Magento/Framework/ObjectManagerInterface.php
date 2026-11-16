<?php

namespace Magento\Framework;

if (!interface_exists(\Magento\Framework\ObjectManagerInterface::class)) {
    interface ObjectManagerInterface
    {
        public function create($type, array $arguments = []);

        public function get($type);

        public function configure(array $configuration);
    }
}
