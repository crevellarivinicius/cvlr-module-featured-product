<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed access to the module configuration (Stores > Configuration > Catalog > Featured Product).
 */
class Config
{
    private const XML_PATH_ENABLED = 'featured_product/general/enabled';
    private const XML_PATH_SKU = 'featured_product/general/sku';
    private const XML_PATH_REFRESH_INTERVAL = 'featured_product/display/refresh_interval';
    private const XML_PATH_LOW_STOCK_THRESHOLD = 'featured_product/display/low_stock_threshold';

    private const MIN_REFRESH_INTERVAL = 5;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Whether the featured product box is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * SKU of the product configured to be featured.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getSku($storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_SKU,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Polling interval, in seconds, used by the frontend stock component.
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getRefreshInterval($storeId = null): int
    {
        $interval = (int)$this->scopeConfig->getValue(
            self::XML_PATH_REFRESH_INTERVAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return max($interval, self::MIN_REFRESH_INTERVAL);
    }

    /**
     * Quantity at (or below) which the frontend highlights the stock as "last units".
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getLowStockThreshold($storeId = null): int
    {
        return max(0, (int)$this->scopeConfig->getValue(
            self::XML_PATH_LOW_STOCK_THRESHOLD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }
}
