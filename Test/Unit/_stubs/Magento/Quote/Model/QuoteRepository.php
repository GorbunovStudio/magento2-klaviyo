<?php

namespace Magento\Quote\Model;

if (!class_exists(\Magento\Quote\Model\QuoteRepository::class)) {
    class QuoteRepository
    {
        public function getActive($cartId)
        {
            return null;
        }
    }
}
