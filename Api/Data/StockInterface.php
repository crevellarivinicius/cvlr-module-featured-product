<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Api\Data;

/**
 * DTO with the salable stock information of the featured product.
 *
 * @api
 */
interface StockInterface
{
    public const KEY_SKU = 'sku';
    public const KEY_QTY = 'qty';
    public const KEY_IS_SALABLE = 'is_salable';
    public const KEY_UPDATED_AT = 'updated_at';

    /**
     * Product SKU the stock information belongs to.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set the product SKU.
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Quantity available for sale (MSI salable quantity).
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Set the quantity available for sale.
     *
     * @param float $qty
     * @return $this
     */
    public function setQty(float $qty): self;

    /**
     * Whether the product is salable at all.
     *
     * @return bool
     */
    public function getIsSalable(): bool;

    /**
     * Set whether the product is salable.
     *
     * @param bool $isSalable
     * @return $this
     */
    public function setIsSalable(bool $isSalable): self;

    /**
     * Timestamp (ISO 8601) of when the information was read.
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set the read timestamp.
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;
}
