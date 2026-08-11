<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Model\Resolver;

use Crevellari\FeaturedProduct\Api\StockInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for the featuredProductStock query.
 *
 * Delegates to the same service contract used by the polling controller
 * and the REST endpoint.
 */
class FeaturedProductStock implements ResolverInterface
{
    /**
     * @var StockInformationInterface
     */
    private $stockInformation;

    /**
     * @param StockInformationInterface $stockInformation
     */
    public function __construct(StockInformationInterface $stockInformation)
    {
        $this->stockInformation = $stockInformation;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        try {
            $stock = $this->stockInformation->getForConfiguredProduct();
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()), $exception);
        }

        return [
            'sku' => $stock->getSku(),
            'qty' => $stock->getQty(),
            'is_salable' => $stock->getIsSalable(),
            'updated_at' => $stock->getUpdatedAt(),
        ];
    }
}
