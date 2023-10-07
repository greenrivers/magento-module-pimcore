<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductService
{
    /**
     * ProductService constructor.
     * @param ProductInterface $product
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ProductInterface           $product,
        private readonly ProductInterfaceFactory    $productFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface      $storeManager,
        private readonly ResourceConnection         $resourceConnection,
        private readonly LoggerInterface            $logger
    )
    {
    }

    /**
     * @param array $data
     * @param ProductInterface|null $product
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    public function saveProduct(array $data, ?ProductInterface $product): void
    {
        if ($product) {
            $storesIds = array_keys($this->storeManager->getStores(true));

            foreach ($storesIds as $storeId) {
                $product->setStoreId($storeId);
                $product->addData($data);
            }
        } else {
            $product = $this->productFactory->create(['data' => $data]);
        }

        $this->productRepository->save($product);
    }

    /**
     * @param string $sku
     * @return ProductInterface|null
     */
    public function getProduct(string $sku): ?ProductInterface
    {
        $product = null;

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
        }

        return $product;
    }

    /**
     * @param string $name
     * @param string $sku
     * @return string
     */
    public function createUrlKey(string $name, string $sku): string
    {
        $url = $this->product->formatUrlKey(str_replace(' ', '-', $name));
        $urlKey = strtolower($url);

        $isUnique = $this->checkUrlKeyDuplicates($sku, $urlKey);

        if (!$isUnique) {
            $urlKey .= '-' . time();
        }

        return $urlKey;
    }

    /**
     * @param string $sku
     * @param string $urlKey
     * @return bool
     */
    private function checkUrlKeyDuplicates(string $sku, string $urlKey): bool
    {
        $connection = $this->resourceConnection->getConnection();

        $sql = $connection->select()->from(
            ['url_rewrite' => $connection->getTableName('url_rewrite')], ['request_path']
        )->joinLeft(
            ['cpe' => $connection->getTableName('catalog_product_entity')], 'cpe.entity_id = url_rewrite.entity_id'
        )->where('request_path IN (?)', [$urlKey, "$urlKey.html"])
            ->where('cpe.sku NOT IN (?)', $sku);

        $urlKeyDuplicates = $connection->fetchAssoc($sql);

        return empty($urlKeyDuplicates);
    }
}
