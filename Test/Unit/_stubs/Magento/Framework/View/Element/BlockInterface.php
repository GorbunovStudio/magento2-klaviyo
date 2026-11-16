<?php

namespace Magento\Framework\View\Element;

if (!interface_exists(\Magento\Framework\View\Element\BlockInterface::class)) {
    interface BlockInterface
    {
        public function toHtml();
    }
}
