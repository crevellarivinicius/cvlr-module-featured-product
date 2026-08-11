<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Test\Integration\Model;

use Crevellari\FeaturedProduct\Api\StockInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration coverage of the stock information service against a real
 * product fixture and the MSI stock index.
 */
class StockInformationTest extends TestCase
{
    /**
     * @var StockInformationInterface
     */
    private $stockInformation;

    protected function setUp(): void
    {
        $this->stockInformation = Bootstrap::getObjectManager()->get(StockInformationInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testGetBySkuReturnsSalableQuantityForFixtureProduct(): void
    {
        $stock = $this->stockInformation->getBySku('simple');

        $this->assertSame('simple', $stock->getSku());
        $this->assertSame(100.0, $stock->getQty());
        $this->assertTrue($stock->getIsSalable());
        $this->assertNotEmpty($stock->getUpdatedAt());
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store featured_product/general/enabled 1
     * @magentoConfigFixture current_store featured_product/general/sku simple
     */
    public function testGetForConfiguredProductReadsSkuFromConfiguration(): void
    {
        $stock = $this->stockInformation->getForConfiguredProduct();

        $this->assertSame('simple', $stock->getSku());
        $this->assertSame(100.0, $stock->getQty());
    }

    /**
     * @magentoConfigFixture current_store featured_product/general/enabled 0
     */
    public function testGetForConfiguredProductThrowsWhenModuleIsDisabled(): void
    {
        $this->expectException(NoSuchEntityException::class);

        $this->stockInformation->getForConfiguredProduct();
    }
}
