<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Unit\Model\Resolver;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Greenrivers\PimcoreIntegration\Model\Resolver\Product;
use Greenrivers\PimcoreIntegration\Service\AuthService;
use Greenrivers\PimcoreIntegration\Service\ProductService;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    private Product $product;

    private ConfigProviderInterface|MockObject $configProviderMock;

    private AuthService|MockObject $authServiceMock;

    private ProductService|MockObject $productServiceMock;

    protected function setUp(): void
    {
        $this->configProviderMock = $this->getMockBuilder(ConfigProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authServiceMock = $this->getMockBuilder(AuthService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productServiceMock = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->product = new Product($this->configProviderMock, $this->authServiceMock, $this->productServiceMock);
    }

    /**
     * @covers Product::resolve
     */
    public function testResolve(): void
    {
        $fieldMock = $this->createMock(Field::class);
        $contextMock = $this->createMock(Context::class);
        $resolveInfoMock = $this->createMock(ResolveInfo::class);

        $this->configProviderMock->expects(self::once())
            ->method('getEnabled')
            ->willReturn(true);
        $this->configProviderMock->expects(self::once())
            ->method('getOverrideProduct')
            ->willReturn(true);
        $contextMock->expects(self::once())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $this->productServiceMock->expects(self::once())
            ->method('createUrlKey')
            ->with('Test product', 'test')
            ->willReturn('test');
        $this->productServiceMock->expects(self::once())
            ->method('getProduct')
            ->with('test')
            ->willReturn(null);
        $this->authServiceMock->expects(self::once())
            ->method('authenticate')
            ->with(UserContextInterface::USER_TYPE_ADMIN)
            ->willReturn(true);
        $this->productServiceMock->expects(self::once())
            ->method('saveProduct')
            ->with(
                [
                    'status' => Status::STATUS_ENABLED,
                    'attribute_set_id' => 4,
                    'name' => 'Test product',
                    'sku' => 'test',
                    'price' => 29.99,
                    'url_key' => 'test'
                ],
                null
            );

        $result = $this->product->resolve(
            $fieldMock,
            $contextMock,
            $resolveInfoMock,
            [],
            [
                'input' => [
                    'status' => Status::STATUS_ENABLED,
                    'attribute_set_id' => 4,
                    'name' => 'Test product',
                    'sku' => 'test',
                    'price' => 29.99
                ]
            ]
        );

        $this->assertArrayHasKey('product', $result);
        $this->assertCount(6, $result['product']);
        $this->assertEquals(
            [
                'product' => [
                    'status' => Status::STATUS_ENABLED,
                    'attribute_set_id' => 4,
                    'name' => 'Test product',
                    'sku' => 'test',
                    'price' => 29.99,
                    'url_key' => 'test'
                ]
            ],
            $result
        );
    }
}
