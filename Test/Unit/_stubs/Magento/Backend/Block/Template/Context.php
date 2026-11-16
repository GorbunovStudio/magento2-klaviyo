<?php

namespace Magento\Backend\Block\Template;

require_once __DIR__ . '/../../../Framework/View/Element/Template/Context.php';

if (!class_exists(\Magento\Backend\Block\Template\Context::class)) {
    class Context extends \Magento\Framework\View\Element\Template\Context
    {
    }
}
