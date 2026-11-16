<?php

namespace Klaviyo\Reclaim\Test\Unit\Helper\Stubs;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Framework\App\Helper\Context;

/**
 * Testable subclass that replaces buildKlaviyoV3Api() with an injected mock.
 */
class TestableDataHelper extends Data
{
    private $apiMock;

    public function __construct(
        Context $context,
        Logger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting,
        KlaviyoV3Api $apiMock
    ) {
        parent::__construct($context, $klaviyoLogger, $klaviyoScopeSetting);
        $this->apiMock = $apiMock;
    }

    protected function buildKlaviyoV3Api($storeId = null): KlaviyoV3Api
    {
        return $this->apiMock;
    }
}
