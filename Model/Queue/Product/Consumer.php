<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Model\Queue\Product;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Greenrivers\PimcoreIntegration\Service\ProductService;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Serialize\SerializerInterface;

class Consumer
{
    /**
     * Consumer constructor.
     * @param ConfigProviderInterface $configProvider
     * @param ProductService $productService
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly ConfigProviderInterface $configProvider,
        private readonly ProductService          $productService,
        private readonly SerializerInterface     $serializer
    )
    {
    }

    /**
     * @param string $productDataEncoded
     * @return void
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function process(string $productDataEncoded): void
    {
        $productData = $this->serializer->unserialize($productDataEncoded);

        $overrideProduct = $this->configProvider->getOverrideProduct();

        ['name' => $name, 'sku' => $sku] = $productData;
        $productData['url_key'] = $this->productService->createUrlKey($name, $sku);
        $product = $this->productService->getProduct($sku);

        if (!$overrideProduct && $product) {
            throw new AlreadyExistsException(__("Product with SKU $sku already exists."));
        }

        $this->productService->saveProduct($productData, $product);
    }
}
