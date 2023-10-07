<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Model\Resolver;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Greenrivers\PimcoreIntegration\Service\AuthService;
use Greenrivers\PimcoreIntegration\Service\CategoryService;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Category implements ResolverInterface
{
    /**
     * Category constructor.
     * @param ConfigProviderInterface $configProvider
     * @param AuthService $authService
     * @param CategoryService $categoryService
     */
    public function __construct(
        private readonly ConfigProviderInterface $configProvider,
        private readonly AuthService             $authService,
        private readonly CategoryService         $categoryService
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $enabled = $this->configProvider->getEnabled();
        $overrideCategory = $this->configProvider->getOverrideCategory();
        $userType = $context->getUserType();

        $data = $args['input'];
        ['name' => $name, 'parent_id' => $parentId] = $data;
        $data['url_key'] = $this->categoryService->createUrlKey($name, $parentId);

        $category = $this->categoryService->getCategory($name, $parentId);

        if (!$this->authService->authenticate($userType)) {
            throw new GraphQlAuthorizationException(__("The user isn't authorized."));
        }

        if (!$enabled) {
            throw new LocalizedException(__("Integration isn't enabled in admin config."));
        }

        if (!$overrideCategory && $category) {
            throw new AlreadyExistsException(__("Category with name $name with parentId $parentId already exists."));
        }

        $this->categoryService->saveCategory($data, $category);

        return ['category' => $data];
    }
}
