<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Test\Unit\Model;

use Crevellari\FeaturedProduct\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the module configuration reader.
 */
class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfigMock);
    }

    public function testIsEnabledReadsStoreScopedFlag(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('featured_product/general/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function testGetSkuTrimsWhitespace(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with('featured_product/general/sku', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('  24-MB01  ');

        $this->assertSame('24-MB01', $this->config->getSku());
    }

    public function testGetSkuReturnsEmptyStringWhenNotConfigured(): void
    {
        $this->scopeConfigMock->method('getValue')->willReturn(null);

        $this->assertSame('', $this->config->getSku());
    }

    public function testRefreshIntervalEnforcesMinimumOfFiveSeconds(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with('featured_product/display/refresh_interval', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('1');

        $this->assertSame(5, $this->config->getRefreshInterval());
    }

    public function testRefreshIntervalReturnsConfiguredValue(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn('30');

        $this->assertSame(30, $this->config->getRefreshInterval());
    }

    public function testLowStockThresholdNeverNegative(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn('-3');

        $this->assertSame(0, $this->config->getLowStockThreshold());
    }
}
