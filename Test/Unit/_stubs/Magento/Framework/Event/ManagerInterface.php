<?php

namespace Magento\Framework\Event;

if (!interface_exists(\Magento\Framework\Event\ManagerInterface::class, false)) {
    interface ManagerInterface
    {
        public function dispatch($eventName, array $data = []);
    }
}
