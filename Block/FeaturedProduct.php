<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Block;

use Crevellari\FeaturedProduct\ViewModel\FeaturedProduct as FeaturedProductViewModel;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;

/**
 * Featured product box.
 *
 * Merges runtime configuration into the jsLayout declared in layout XML and
 * exposes the product cache identities, so the homepage FPC entry is
 * invalidated when the featured product changes.
 */
class FeaturedProduct extends Template implements IdentityInterface
{
    private const STOCK_COMPONENT = 'featured-product-stock';

    /**
     * @inheritdoc
     */
    public function getJsLayout()
    {
        $viewModel = $this->getViewModel();

        if ($viewModel !== null && isset($this->jsLayout['components'][self::STOCK_COMPONENT])) {
            $initialStock = $viewModel->getInitialStock();

            $runtimeConfig = [
                'stockUrl' => $this->getUrl('featuredproduct/stock/get'),
                'refreshInterval' => $viewModel->getRefreshInterval(),
                'lowStockThreshold' => $viewModel->getLowStockThreshold(),
                'initialQty' => $initialStock !== null ? $initialStock->getQty() : null,
                'initialIsSalable' => $initialStock !== null && $initialStock->getIsSalable(),
                'initialUpdatedAt' => $initialStock !== null ? $initialStock->getUpdatedAt() : '',
            ];

            $existingConfig = $this->jsLayout['components'][self::STOCK_COMPONENT]['config'] ?? [];
            $this->jsLayout['components'][self::STOCK_COMPONENT]['config'] =
                array_merge($existingConfig, $runtimeConfig);
        }

        return parent::getJsLayout();
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        $viewModel = $this->getViewModel();
        $product = $viewModel !== null ? $viewModel->getProduct() : null;

        if ($product instanceof Product) {
            return $product->getIdentities();
        }

        return [];
    }

    /**
     * Do not output anything when the box must not be displayed.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $viewModel = $this->getViewModel();

        if ($viewModel === null || !$viewModel->shouldDisplay()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * View model injected through the layout XML "view_model" argument.
     *
     * @return FeaturedProductViewModel|null
     */
    private function getViewModel(): ?FeaturedProductViewModel
    {
        $viewModel = $this->getData('view_model');

        return $viewModel instanceof FeaturedProductViewModel ? $viewModel : null;
    }
}
