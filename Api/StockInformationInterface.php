<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Api;

use Crevellari\FeaturedProduct\Api\Data\StockInterface;

/**
 * Provides the salable quantity of the featured product.
 *
 * @api
 */
interface StockInformationInterface
{
    /**
     * Get salable stock information for a given SKU.
     *
     * @param string $sku
     * @return \Crevellari\FeaturedProduct\Api\Data\StockInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBySku(string $sku): StockInterface;

    /**
     * Get salable stock information for the product configured in the admin panel.
     *
     * @return \Crevellari\FeaturedProduct\Api\Data\StockInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getForConfiguredProduct(): StockInterface;
}
