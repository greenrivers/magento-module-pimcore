<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Unit\Service;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Greenrivers\PimcoreIntegration\Service\GraphqlService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Stream\Stream;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GraphqlServiceTest extends TestCase
{
    private GraphqlService $graphqlService;

    private MockHandler $mockHandler;

    private Client $client;

    private ConfigProviderInterface|MockObject $configProviderMock;

    private SerializerInterface|MockObject $serializerMock;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler([]);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->client = new Client(['handler' => $handlerStack]);
        $this->configProviderMock = $this->getMockBuilder(ConfigProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->graphqlService = new GraphqlService($this->client, $this->configProviderMock, $this->serializerMock);
    }

    /**
     * @covers GraphqlService::getProducts
     */
    public function testGetProducts(): void
    {
        $this->configProviderMock->expects(self::once())
            ->method('getPimcoreUrl')
            ->willReturn('https://app.pimcore.test/');
        $this->configProviderMock->expects(self::once())
            ->method('getPimcoreApiKey')
            ->willReturn('apikey123');
        $this->mockHandler->append(
            new Response(
                200,
                [],
                Stream::factory(
                    serialize([
                        'data' => [
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
                        ]
                    ])
                )
            )
        );
        $this->serializerMock->expects(self::once())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return unserialize($value);
                }
            );

        $result = $this->graphqlService->getProducts(['first' => 100, 'after' => 0]);

        $this->assertCount(2, $result);
        $this->assertEquals(
            [
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
            ],
            $result
        );
    }

    /**
     * @covers GraphqlService::getCategories
     */
    public function testGetCategories(): void
    {
        $this->configProviderMock->expects(self::once())
            ->method('getPimcoreUrl')
            ->willReturn('https://app.pimcore.test/');
        $this->configProviderMock->expects(self::once())
            ->method('getPimcoreApiKey')
            ->willReturn('apikey123');
        $this->mockHandler->append(
            new Response(
                200,
                [],
                Stream::factory(
                    serialize([
                        'data' => [
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
                        ]
                    ])
                )
            )
        );
        $this->serializerMock->expects(self::once())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return unserialize($value);
                }
            );

        $result = $this->graphqlService->getCategories(['first' => 100, 'after' => 0]);

        $this->assertCount(3, $result);
        $this->assertEquals(
            [
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
            ],
            $result
        );
    }
}
