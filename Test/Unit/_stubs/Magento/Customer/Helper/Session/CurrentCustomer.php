<?php

namespace Magento\Customer\Helper\Session;

if (!class_exists(\Magento\Customer\Helper\Session\CurrentCustomer::class)) {
    class CurrentCustomer
    {
        public function getCustomer()
        {
            return null;
        }

        public function getCustomerId()
        {
            return null;
        }

        public function setCustomerId($customerId)
        {
            return $this;
        }
    }
}
