<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Model\Data;

use Crevellari\FeaturedProduct\Api\Data\StockInterface;
use Magento\Framework\Api\AbstractSimpleObject;

/**
 * @inheritdoc
 */
class Stock extends AbstractSimpleObject implements StockInterface
{
    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return (string)$this->_get(self::KEY_SKU);
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku): StockInterface
    {
        return $this->setData(self::KEY_SKU, $sku);
    }

    /**
     * @inheritdoc
     */
    public function getQty(): float
    {
        return (float)$this->_get(self::KEY_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setQty(float $qty): StockInterface
    {
        return $this->setData(self::KEY_QTY, $qty);
    }

    /**
     * @inheritdoc
     */
    public function getIsSalable(): bool
    {
        return (bool)$this->_get(self::KEY_IS_SALABLE);
    }

    /**
     * @inheritdoc
     */
    public function setIsSalable(bool $isSalable): StockInterface
    {
        return $this->setData(self::KEY_IS_SALABLE, $isSalable);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): string
    {
        return (string)$this->_get(self::KEY_UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): StockInterface
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }
}
