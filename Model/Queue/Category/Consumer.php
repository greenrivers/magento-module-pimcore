<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Model\Queue\Category;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Greenrivers\PimcoreIntegration\Service\CategoryService;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\SerializerInterface;

class Consumer
{
    /**
     * Consumer constructor.
     * @param ConfigProviderInterface $configProvider
     * @param CategoryService $categoryService
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly ConfigProviderInterface $configProvider,
        private readonly CategoryService         $categoryService,
        private readonly SerializerInterface     $serializer
    )
    {
    }

    /**
     * @param string $categoryDataEncoded
     * @return void
     * @throws AlreadyExistsException
     * @throws CouldNotSaveException
     */
    public function process(string $categoryDataEncoded): void
    {
        $categoryData = $this->serializer->unserialize($categoryDataEncoded);

        $overrideCategory = $this->configProvider->getOverrideCategory();

        ['name' => $name, 'parent_id' => $parentId] = $categoryData;
        $categoryData['url_key'] = $this->categoryService->createUrlKey($name, (int)$parentId);
        $category = $this->categoryService->getCategory($name, (int)$parentId);

        if (!$overrideCategory && $category) {
            throw new AlreadyExistsException(__("Category with name $name with parentId $parentId already exists."));
        }

        $this->categoryService->saveCategory($categoryData, $category);
    }
}
