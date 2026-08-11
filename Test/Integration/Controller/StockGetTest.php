<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Test\Integration\Controller;

use Magento\TestFramework\TestCase\AbstractController;

/**
 * Integration coverage of the polling endpoint.
 */
class StockGetTest extends AbstractController
{
    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store featured_product/general/enabled 1
     * @magentoConfigFixture current_store featured_product/general/sku simple
     */
    public function testEndpointReturnsStockPayload(): void
    {
        $this->dispatch('featuredproduct/stock/get');

        $response = $this->getResponse();
        $this->assertSame(200, $response->getHttpResponseCode());
        $this->assertNotEmpty($response->getHeader('ETag'));

        $payload = json_decode($response->getBody(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame(100, (int)$payload['qty']);
        $this->assertTrue($payload['is_salable']);
        $this->assertArrayHasKey('updated_at', $payload);
    }

    /**
     * @magentoConfigFixture current_store featured_product/general/enabled 0
     */
    public function testEndpointReturns404WhenDisabled(): void
    {
        $this->dispatch('featuredproduct/stock/get');

        $response = $this->getResponse();
        $this->assertSame(404, $response->getHttpResponseCode());

        $payload = json_decode($response->getBody(), true);
        $this->assertFalse($payload['success']);
    }
}
