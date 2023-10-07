<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Model\Resolver;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Greenrivers\PimcoreIntegration\Service\AuthService;
use Greenrivers\PimcoreIntegration\Service\ProductService;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Product implements ResolverInterface
{
    /**
     * Product constructor.
     * @param ConfigProviderInterface $configProvider
     * @param AuthService $authService
     * @param ProductService $productService
     */
    public function __construct(
        private readonly ConfigProviderInterface $configProvider,
        private readonly AuthService             $authService,
        private readonly ProductService          $productService
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $enabled = $this->configProvider->getEnabled();
        $overrideProduct = $this->configProvider->getOverrideProduct();
        $userType = $context->getUserType();

        $data = $args['input'];
        ['name' => $name, 'sku' => $sku] = $data;
        $data['url_key'] = $this->productService->createUrlKey($name, $sku);

        $product = $this->productService->getProduct($sku);

        if (!$this->authService->authenticate($userType)) {
            throw new GraphQlAuthorizationException(__("The user isn't authorized."));
        }

        if (!$enabled) {
            throw new LocalizedException(__("Integration isn't enabled in admin config."));
        }

        if (!$overrideProduct && $product) {
            throw new AlreadyExistsException(__("Product with SKU $sku already exists."));
        }

        $this->productService->saveProduct($data, $product);

        return ['product' => $data];
    }
}
