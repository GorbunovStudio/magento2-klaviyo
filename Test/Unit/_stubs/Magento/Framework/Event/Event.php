<?php

namespace Magento\Framework\Event;

if (!class_exists(\Magento\Framework\Event\Event::class)) {
    class Event
    {
        public function getOrder()
        {
            return null;
        }
        public function getQuote()
        {
            return null;
        }
    }
}
