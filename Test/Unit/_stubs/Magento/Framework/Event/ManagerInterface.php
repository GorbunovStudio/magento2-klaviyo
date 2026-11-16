<?php

namespace Magento\Framework\Event;

if (!interface_exists(\Magento\Framework\Event\ManagerInterface::class)) {
    interface ManagerInterface
    {
        public function dispatch($eventName, array $data = []);
    }
}
