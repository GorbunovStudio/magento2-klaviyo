<?php

namespace Magento\Framework\Event;

if (!class_exists(\Magento\Framework\Event\Observer::class)) {
    class Observer
    {
        public function getEvent()
        {
            return new Event();
        }

        public function getDataObject()
        {
            return null;
        }
    }
}
