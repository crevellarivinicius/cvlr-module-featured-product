<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Model;

use Crevellari\FeaturedProduct\Api\Data\StockInterface;
use Crevellari\FeaturedProduct\Api\Data\StockInterfaceFactory;
use Crevellari\FeaturedProduct\Api\StockInformationInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reads the quantity available for sale of a product.
 *
 * Primary source is the MSI salable quantity (physical qty minus reservations).
 * Product types without source item management fall back to the legacy stock item.
 */
class StockInformation implements StockInformationInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var StockInterfaceFactory
     */
    private $stockFactory;

    /**
     * @param Config $config
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsProductSalableInterface $isProductSalable
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param TimezoneInterface $localeDate
     * @param StockInterfaceFactory $stockFactory
     */
    public function __construct(
        Config $config,
        GetProductSalableQtyInterface $getProductSalableQty,
        IsProductSalableInterface $isProductSalable,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry,
        TimezoneInterface $localeDate,
        StockInterfaceFactory $stockFactory
    ) {
        $this->config = $config;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->isProductSalable = $isProductSalable;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->localeDate = $localeDate;
        $this->stockFactory = $stockFactory;
    }

    /**
     * @inheritdoc
     */
    public function getBySku(string $sku): StockInterface
    {
        if ($sku === '') {
            throw new NoSuchEntityException(__('No featured product SKU is configured.'));
        }

        $stockId = $this->resolveStockId();

        try {
            $qty = $this->getProductSalableQty->execute($sku, $stockId);
            $isSalable = $this->isProductSalable->execute($sku, $stockId);
        } catch (LocalizedException $exception) {
            // Product type without MSI source item management (configurable, bundle...)
            [$qty, $isSalable] = $this->getLegacyStockData($sku);
        }

        /** @var StockInterface $stock */
        $stock = $this->stockFactory->create();
        $stock->setSku($sku);
        $stock->setQty((float)$qty);
        $stock->setIsSalable((bool)$isSalable);
        $stock->setUpdatedAt($this->localeDate->date()->format(DATE_ATOM));

        return $stock;
    }

    /**
     * @inheritdoc
     */
    public function getForConfiguredProduct(): StockInterface
    {
        if (!$this->config->isEnabled()) {
            throw new NoSuchEntityException(__('The featured product box is disabled.'));
        }

        return $this->getBySku($this->config->getSku());
    }

    /**
     * Resolve the MSI stock id assigned to the current website sales channel.
     *
     * @return int
     * @throws NoSuchEntityException
     */
    private function resolveStockId(): int
    {
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);

            return (int)$stock->getStockId();
        } catch (LocalizedException $exception) {
            throw new NoSuchEntityException(
                __('Unable to resolve the stock for the current website.'),
                $exception
            );
        }
    }

    /**
     * Legacy (CatalogInventory) fallback used when MSI cannot provide a salable quantity.
     *
     * @param string $sku
     * @return array{0: float, 1: bool}
     * @throws NoSuchEntityException
     */
    private function getLegacyStockData(string $sku): array
    {
        $product = $this->productRepository->get($sku);
        $stockItem = $this->stockRegistry->getStockItem((int)$product->getId());

        return [(float)$stockItem->getQty(), (bool)$stockItem->getIsInStock()];
    }
}
