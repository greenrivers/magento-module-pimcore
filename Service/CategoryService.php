<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Service;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CategoryService
{
    /**
     * CategoryService constructor.
     * @param CategoryInterface $category
     * @param CategoryInterfaceFactory $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CategoryInterface           $category,
        private readonly CategoryInterfaceFactory    $categoryFactory,
        private readonly CategoryCollectionFactory   $categoryCollectionFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly StoreManagerInterface       $storeManager,
        private readonly ResourceConnection          $resourceConnection,
        private readonly LoggerInterface             $logger
    )
    {
    }

    /**
     * @param array $data
     * @param CategoryInterface|null $category
     * @return void
     * @throws CouldNotSaveException
     */
    public function saveCategory(array $data, ?CategoryInterface $category): void
    {
        if ($category) {
            $storesIds = array_keys($this->storeManager->getStores(true));

            foreach ($storesIds as $storeId) {
                $category->setStoreId($storeId);
                $category->addData($data);
                $category->save();
            }
        } else {
            $category = $this->categoryFactory->create(['data' => $data]);
            $this->categoryRepository->save($category);
        }
    }

    /**
     * @param string $name
     * @param int $parentId
     * @return CategoryInterface|null
     */
    public function getCategory(string $name, int $parentId): ?CategoryInterface
    {
        $category = null;

        try {
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->setStoreId(Store::DEFAULT_STORE_ID)
                ->addAttributeToFilter(CategoryInterface::KEY_NAME, ['eq' => $name])
                ->addAttributeToFilter(CategoryInterface::KEY_PARENT_ID, ['eq' => $parentId]);

            if ($categoryCollection->getSize()) {
                $category = $categoryCollection->getFirstItem();
            }
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
        }

        return $category;
    }

    /**
     * @param string $name
     * @param int $parentId
     * @return string
     */
    public function createUrlKey(string $name, int $parentId): string
    {
        $url = $this->category->formatUrlKey(str_replace(' ', '-', $name));
        $urlKey = strtolower($url);

        $isUnique = $this->checkUrlKeyDuplicates($parentId, $urlKey);

        if (!$isUnique) {
            $urlKey .= '-' . time();
        }

        return $urlKey;
    }

    /**
     * @param int $parentId
     * @param string $urlKey
     * @return bool
     */
    private function checkUrlKeyDuplicates(int $parentId, string $urlKey): bool
    {
        $connection = $this->resourceConnection->getConnection();

        $sql = $connection->select()->from(
            ['url_rewrite' => $connection->getTableName('url_rewrite')], ['request_path']
        )->joinLeft(
            ['cce' => $connection->getTableName('catalog_category_entity')], 'cce.entity_id = url_rewrite.entity_id'
        )->where('request_path IN (?)', [$urlKey, "$urlKey.html"])
            ->where('cce.parent_id IN (?)', $parentId);

        $urlKeyDuplicates = $connection->fetchAssoc($sql);

        return empty($urlKeyDuplicates);
    }
}
