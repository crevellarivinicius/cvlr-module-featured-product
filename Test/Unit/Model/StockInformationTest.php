<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Test\Unit\Model;

use Crevellari\FeaturedProduct\Api\Data\StockInterfaceFactory;
use Crevellari\FeaturedProduct\Model\Config;
use Crevellari\FeaturedProduct\Model\Data\Stock;
use Crevellari\FeaturedProduct\Model\StockInformation;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\InventoryApi\Api\Data\StockInterface as InventoryStockInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the stock information service.
 */
class StockInformationTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var GetProductSalableQtyInterface|MockObject
     */
    private $getProductSalableQtyMock;

    /**
     * @var IsProductSalableInterface|MockObject
     */
    private $isProductSalableMock;

    /**
     * @var StockResolverInterface|MockObject
     */
    private $stockResolverMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var StockRegistryInterface|MockObject
     */
    private $stockRegistryMock;

    /**
     * @var StockInformation
     */
    private $service;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->getProductSalableQtyMock = $this->createMock(GetProductSalableQtyInterface::class);
        $this->isProductSalableMock = $this->createMock(IsProductSalableInterface::class);
        $this->stockResolverMock = $this->createMock(StockResolverInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->stockRegistryMock = $this->createMock(StockRegistryInterface::class);

        $websiteMock = $this->createMock(WebsiteInterface::class);
        $websiteMock->method('getCode')->willReturn('base');
        $this->storeManagerMock->method('getWebsite')->willReturn($websiteMock);

        $salesStockMock = $this->createMock(InventoryStockInterface::class);
        $salesStockMock->method('getStockId')->willReturn(1);
        $this->stockResolverMock->method('execute')->willReturn($salesStockMock);

        $timezoneMock = $this->createMock(TimezoneInterface::class);
        $timezoneMock->method('date')->willReturn(new \DateTime('2026-07-03 10:00:00'));

        $stockFactoryMock = $this->createMock(StockInterfaceFactory::class);
        $stockFactoryMock->method('create')->willReturnCallback(
            static function () {
                return new Stock();
            }
        );

        $this->service = new StockInformation(
            $this->configMock,
            $this->getProductSalableQtyMock,
            $this->isProductSalableMock,
            $this->stockResolverMock,
            $this->storeManagerMock,
            $this->productRepositoryMock,
            $this->stockRegistryMock,
            $timezoneMock,
            $stockFactoryMock
        );
    }

    public function testGetBySkuReturnsMsiSalableQuantity(): void
    {
        $this->getProductSalableQtyMock->expects($this->once())
            ->method('execute')
            ->with('24-MB01', 1)
            ->willReturn(42.0);
        $this->isProductSalableMock->method('execute')->willReturn(true);

        $stock = $this->service->getBySku('24-MB01');

        $this->assertSame('24-MB01', $stock->getSku());
        $this->assertSame(42.0, $stock->getQty());
        $this->assertTrue($stock->getIsSalable());
        $this->assertNotEmpty($stock->getUpdatedAt());
    }

    public function testGetBySkuFallsBackToLegacyStockForUnsupportedProductTypes(): void
    {
        $this->getProductSalableQtyMock->method('execute')
            ->willThrowException(new InputException(__('Not applicable for this product type.')));

        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(10);
        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with('CONF-01')
            ->willReturn($productMock);

        $stockItemMock = $this->createMock(StockItemInterface::class);
        $stockItemMock->method('getQty')->willReturn(7.0);
        $stockItemMock->method('getIsInStock')->willReturn(true);
        $this->stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->with(10)
            ->willReturn($stockItemMock);

        $stock = $this->service->getBySku('CONF-01');

        $this->assertSame(7.0, $stock->getQty());
        $this->assertTrue($stock->getIsSalable());
    }

    public function testGetBySkuWithEmptySkuThrows(): void
    {
        $this->expectException(NoSuchEntityException::class);

        $this->service->getBySku('');
    }

    public function testGetForConfiguredProductThrowsWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        $this->expectException(NoSuchEntityException::class);

        $this->service->getForConfiguredProduct();
    }

    public function testGetForConfiguredProductUsesConfiguredSku(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getSku')->willReturn('24-MB01');
        $this->getProductSalableQtyMock->method('execute')->willReturn(3.0);
        $this->isProductSalableMock->method('execute')->willReturn(true);

        $stock = $this->service->getForConfiguredProduct();

        $this->assertSame('24-MB01', $stock->getSku());
        $this->assertSame(3.0, $stock->getQty());
    }
}
