<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Integration\Console\Command;

use Greenrivers\PimcoreIntegration\Console\Command\SyncData;
use Greenrivers\PimcoreIntegration\Model\Queue\Product\Publisher as ProductPublisher;
use Greenrivers\PimcoreIntegration\Model\Queue\Category\Publisher as CategoryPublisher;
use Greenrivers\PimcoreIntegration\Service\GraphqlService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Stream\Stream;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

class SyncDataTest extends TestCase
{
    private ?ObjectManagerInterface $objectManager;

    private ?ConsumerFactory $consumerFactory;

    public static function setUpBeforeClass(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $clearQueueProcessor = $objectManager->get(ClearQueueProcessor::class);
        $clearQueueProcessor->execute(ProductPublisher::TOPIC_NAME);
        $clearQueueProcessor->execute(CategoryPublisher::TOPIC_NAME);
    }

    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->consumerFactory = $this->objectManager->get(ConsumerFactory::class);
    }

    /**
     * @magentoConfigFixture current_store greenrivers_pimcoreintegration/general/pimcore_url https://app.pimcore.test/
     * @magentoConfigFixture current_store greenrivers_pimcoreintegration/general/pimcore_api_key apikey123
     */
    public function testSyncProducts(): void
    {
        $input = new ArrayInput(['--products' => true]);
        $output = $this->getOutputStream();

        $data = [
            'getMagentoIntegrationProductListing' => [
                'edges' => [
                    [
                        'node' => [
                            'status' => Status::STATUS_ENABLED,
                            'attribute_set_id' => 4,
                            'name' => 'Test product',
                            'sku' => 'test',
                            'price' => 19.99
                        ]
                    ],
                    [
                        'node' => [
                            'status' => Status::STATUS_DISABLED,
                            'attribute_set_id' => 4,
                            'name' => 'Test product 2',
                            'sku' => 'test2',
                            'price' => 79.99
                        ]
                    ]
                ]
            ]
        ];
        $client = $this->getClient($data);
        $graphqlService = $this->getGraphqlService($client);
        $syncData = $this->getSyncData($graphqlService);

        $result = $syncData->run($input, $output);

        $this->processQueue(ProductPublisher::TOPIC_NAME, 2);

        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $product = $productRepository->get('test');
        $product2 = $productRepository->get('test2');

        $this->assertEquals(Command::SUCCESS, $result);

        $this->assertEquals(Status::STATUS_ENABLED, $product->getStatus());
        $this->assertEquals(4, $product->getAttributeSetId());
        $this->assertEquals('Test product', $product->getName());
        $this->assertEquals('test', $product->getSku());
        $this->assertEquals(19.99, $product->getPrice());

        $this->assertEquals(Status::STATUS_DISABLED, $product2->getStatus());
        $this->assertEquals(4, $product2->getAttributeSetId());
        $this->assertEquals('Test product 2', $product2->getName());
        $this->assertEquals('test2', $product2->getSku());
        $this->assertEquals(79.99, $product2->getPrice());
    }

    /**
     * @magentoConfigFixture current_store greenrivers_pimcoreintegration/general/pimcore_url https://app.pimcore.test/
     * @magentoConfigFixture current_store greenrivers_pimcoreintegration/general/pimcore_api_key apikey123
     */
    public function testSyncCategories(): void
    {
        $input = new ArrayInput(['--categories' => true]);
        $output = $this->getOutputStream();

        $data = [
            'getMagentoIntegrationCategoryListing' => [
                'edges' => [
                    [
                        'node' => [
                            'is_active' => 1,
                            'include_in_menu' => 1,
                            'name' => 'Test category',
                            'parent_id' => 2
                        ]
                    ],
                    [
                        'node' => [
                            'is_active' => 1,
                            'include_in_menu' => 0,
                            'name' => 'Test category 2',
                            'parent_id' => 2
                        ]
                    ],
                    [
                        'node' => [
                            'is_active' => 0,
                            'include_in_menu' => 1,
                            'name' => 'Test category 3',
                            'parent_id' => 5
                        ]
                    ]
                ]
            ]
        ];
        $client = $this->getClient($data);
        $graphqlService = $this->getGraphqlService($client);
        $syncData = $this->getSyncData($graphqlService);

        $result = $syncData->run($input, $output);

        $this->processQueue(CategoryPublisher::TOPIC_NAME, 3);

        $category = $this->getCategory('Test category', 2);
        $category2 = $this->getCategory('Test category 2', 2);
        $category3 = $this->getCategory('Test category 3', 5);

        $this->assertEquals(Command::SUCCESS, $result);

        $this->assertEquals(1, $category->getIsActive());
        $this->assertEquals(1, $category->getIncludeInMenu());
        $this->assertEquals('Test category', $category->getName());
        $this->assertEquals(2, $category->getParentId());

        $this->assertEquals(1, $category2->getIsActive());
        $this->assertEquals(0, $category2->getIncludeInMenu());
        $this->assertEquals('Test category 2', $category2->getName());
        $this->assertEquals(2, $category2->getParentId());

        $this->assertEquals(0, $category3->getIsActive());
        $this->assertEquals(1, $category3->getIncludeInMenu());
        $this->assertEquals('Test category 3', $category3->getName());
        $this->assertEquals(5, $category3->getParentId());
    }

    /**
     * @param GraphqlService $graphqlService
     * @return SyncData
     */
    private function getSyncData(GraphqlService $graphqlService): SyncData
    {
        return $this->objectManager->create(
            SyncData::class,
            [
                'graphqlService' => $graphqlService
            ]
        );
    }

    /**
     * @param Client $client
     * @return GraphqlService
     */
    private function getGraphqlService(Client $client): GraphqlService
    {
        return $this->objectManager->create(
            GraphqlService::class,
            [
                'client' => $client
            ]
        );
    }

    /**
     * @param array $data
     * @return Client
     */
    private function getClient(array $data): Client
    {
        $serializer = $this->getSerializer();

        $mockHandler = new MockHandler([
            new Response(
                200,
                [],
                Stream::factory($serializer->serialize(['data' => $data]))
            )
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        return new Client(['handler' => $handlerStack]);
    }

    /**
     * @return SerializerInterface
     */
    private function getSerializer(): SerializerInterface
    {
        return $this->objectManager->get(SerializerInterface::class);
    }

    /**
     * @param bool $decorated
     * @param int $verbosity
     * @return StreamOutput
     */
    private function getOutputStream(
        bool $decorated = true,
        int  $verbosity = StreamOutput::VERBOSITY_NORMAL
    ): StreamOutput
    {
        return new StreamOutput(fopen('php://memory', 'rb+'), $verbosity, $decorated);
    }

    /**
     * @param string $name
     * @param int $parentId
     * @return CategoryInterface|null
     * @throws LocalizedException
     */
    private function getCategory(string $name, int $parentId): ?CategoryInterface
    {
        /* @var Collection $collection */
        $collection = $this->objectManager->create(Collection::class);

        return $collection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter(CategoryInterface::KEY_NAME, $name)
            ->addAttributeToFilter(CategoryInterface::KEY_PARENT_ID, $parentId)
            ->getFirstItem();
    }

    /**
     * @param string $name
     * @param int $messages
     * @return void
     * @throws LocalizedException
     */
    private function processQueue(string $name, int $messages): void
    {
        $consumer = $this->consumerFactory->get($name);
        $consumer->process($messages);
    }
}
