<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Model\Config\Backend;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Validates the configured SKU on save, so a typo is caught in the admin
 * instead of silently hiding the box on the storefront.
 */
class Sku extends Value
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ProductRepositoryInterface $productRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ProductRepositoryInterface $productRepository,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Reject the save when the SKU does not match any product.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $sku = trim((string)$this->getValue());

        if ($sku !== '') {
            try {
                $this->productRepository->get($sku);
            } catch (NoSuchEntityException $exception) {
                throw new LocalizedException(
                    __('The SKU "%1" does not match any product. Please check the value and try again.', $sku)
                );
            }

            $this->setValue($sku);
        }

        return parent::beforeSave();
    }
}
