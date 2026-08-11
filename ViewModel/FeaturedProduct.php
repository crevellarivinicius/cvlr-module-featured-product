<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\ViewModel;

use Crevellari\FeaturedProduct\Api\Data\StockInterface;
use Crevellari\FeaturedProduct\Api\StockInformationInterface;
use Crevellari\FeaturedProduct\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Image as ProductImage;
use Magento\Catalog\Block\Product\ImageFactory as ProductImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

/**
 * Supplies the featured product data to the frontend template.
 * Injected through the view_model layout argument.
 */
class FeaturedProduct implements ArgumentInterface
{
    /**
     * Image id defined by the Luma/blank theme view.xml for the base image role.
     */
    private const IMAGE_ID = 'product_base_image';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockInformationInterface
     */
    private $stockInformation;

    /**
     * @var ProductImageFactory
     */
    private $productImageFactory;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductInterface|Product|null|false
     */
    private $product = false;

    /**
     * @param Config $config
     * @param ProductRepositoryInterface $productRepository
     * @param StockInformationInterface $stockInformation
     * @param ProductImageFactory $productImageFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        ProductRepositoryInterface $productRepository,
        StockInformationInterface $stockInformation,
        ProductImageFactory $productImageFactory,
        PriceCurrencyInterface $priceCurrency,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->productRepository = $productRepository;
        $this->stockInformation = $stockInformation;
        $this->productImageFactory = $productImageFactory;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
    }

    /**
     * Whether the box should be rendered at all.
     *
     * @return bool
     */
    public function shouldDisplay(): bool
    {
        return $this->config->isEnabled() && $this->getProduct() !== null;
    }

    /**
     * The featured product, or null when not configured / not found / disabled.
     *
     * @return ProductInterface|Product|null
     */
    public function getProduct(): ?ProductInterface
    {
        if ($this->product === false) {
            $this->product = $this->loadProduct();
        }

        return $this->product;
    }

    /**
     * Frontend URL of the featured product page.
     *
     * @return string
     */
    public function getProductUrl(): string
    {
        $product = $this->getProduct();

        return $product instanceof Product ? (string)$product->getProductUrl() : '';
    }

    /**
     * Base image of the product, rendered through the catalog image pipeline.
     *
     * @return ProductImage|null
     */
    public function getImage(): ?ProductImage
    {
        $product = $this->getProduct();

        if (!$product instanceof Product) {
            return null;
        }

        return $this->productImageFactory->create($product, self::IMAGE_ID);
    }

    /**
     * Initial stock information for the server-side render.
     *
     * The Knockout component keeps it fresh afterwards.
     *
     * @return StockInterface|null
     */
    public function getInitialStock(): ?StockInterface
    {
        $product = $this->getProduct();

        if ($product === null) {
            return null;
        }

        try {
            return $this->stockInformation->getBySku((string)$product->getSku());
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }

    /**
     * Fallback price formatting used when the price render block is unavailable.
     *
     * @return string
     */
    public function getFormattedFinalPrice(): string
    {
        $product = $this->getProduct();

        if (!$product instanceof Product) {
            return '';
        }

        return (string)$this->priceCurrency->convertAndFormat(
            (float)$product->getFinalPrice(),
            false
        );
    }

    /**
     * Quantity at (or below) which the "last units" highlight is shown.
     *
     * @return int
     */
    public function getLowStockThreshold(): int
    {
        return $this->config->getLowStockThreshold();
    }

    /**
     * Polling interval in seconds.
     *
     * @return int
     */
    public function getRefreshInterval(): int
    {
        return $this->config->getRefreshInterval();
    }

    /**
     * Load the configured product; returns null on any misconfiguration.
     *
     * @return ProductInterface|null
     */
    private function loadProduct(): ?ProductInterface
    {
        $sku = $this->config->getSku();

        if ($sku === '') {
            return null;
        }

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $exception) {
            $this->logger->warning(
                sprintf('[Crevellari_FeaturedProduct] Configured SKU "%s" was not found.', $sku)
            );

            return null;
        }

        if ((int)$product->getStatus() !== Status::STATUS_ENABLED) {
            return null;
        }

        return $product;
    }
}
