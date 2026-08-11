<?php
/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Crevellari\FeaturedProduct\Controller\Stock;

use Crevellari\FeaturedProduct\Api\StockInformationInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * JSON endpoint polled by the frontend stock component.
 *
 * All business logic lives in the StockInformationInterface service;
 * this action only maps HTTP to the service call and formats the response.
 */
class Get implements HttpGetActionInterface
{
    /**
     * @var StockInformationInterface
     */
    private $stockInformation;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param StockInformationInterface $stockInformation
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        StockInformationInterface $stockInformation,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        $this->stockInformation = $stockInformation;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Return the salable quantity of the configured featured product as JSON.
     *
     * The SKU comes from the store configuration; the endpoint takes no input.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);

        try {
            $stock = $this->stockInformation->getForConfiguredProduct();

            return $result->setData([
                'success' => true,
                'qty' => $stock->getQty(),
                'is_salable' => $stock->getIsSalable(),
                'updated_at' => $stock->getUpdatedAt(),
            ]);
        } catch (NoSuchEntityException $exception) {
            return $result->setHttpResponseCode(404)->setData([
                'success' => false,
                'message' => (string)__('Featured product is not available.'),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error(
                '[Crevellari_FeaturedProduct] Failed to read stock information: ' . $exception->getMessage(),
                ['exception' => $exception]
            );

            return $result->setHttpResponseCode(500)->setData([
                'success' => false,
                'message' => (string)__('Unable to load stock information right now.'),
            ]);
        }
    }
}
