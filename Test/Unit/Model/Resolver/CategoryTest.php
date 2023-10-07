<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Unit\Model\Resolver;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Greenrivers\PimcoreIntegration\Model\Resolver\Category;
use Greenrivers\PimcoreIntegration\Service\AuthService;
use Greenrivers\PimcoreIntegration\Service\CategoryService;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Category $category;

    private ConfigProviderInterface|MockObject $configProviderMock;

    private AuthService|MockObject $authServiceMock;

    private CategoryService|MockObject $categoryServiceMock;

    protected function setUp(): void
    {
        $this->configProviderMock = $this->getMockBuilder(ConfigProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authServiceMock = $this->getMockBuilder(AuthService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryServiceMock = $this->getMockBuilder(CategoryService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->category = new Category($this->configProviderMock, $this->authServiceMock, $this->categoryServiceMock);
    }

    /**
     * @covers Category::resolve
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
            ->method('getOverrideCategory')
            ->willReturn(true);
        $contextMock->expects(self::once())
            ->method('getUserType')
            ->willReturn(UserContextInterface::USER_TYPE_ADMIN);
        $this->categoryServiceMock->expects(self::once())
            ->method('createUrlKey')
            ->with('Test category', 2)
            ->willReturn('test-category');
        $this->categoryServiceMock->expects(self::once())
            ->method('getCategory')
            ->with('Test category', 2)
            ->willReturn(null);
        $this->authServiceMock->expects(self::once())
            ->method('authenticate')
            ->with(UserContextInterface::USER_TYPE_ADMIN)
            ->willReturn(true);
        $this->categoryServiceMock->expects(self::once())
            ->method('saveCategory')
            ->with(
                [
                    'is_active' => 1,
                    'include_in_menu' => 0,
                    'name' => 'Test category',
                    'parent_id' => 2,
                    'url_key' => 'test-category'
                ],
                null
            );

        $result = $this->category->resolve(
            $fieldMock,
            $contextMock,
            $resolveInfoMock,
            [],
            [
                'input' => [
                    'is_active' => 1,
                    'include_in_menu' => 0,
                    'name' => 'Test category',
                    'parent_id' => 2
                ]
            ]
        );

        $this->assertArrayHasKey('category', $result);
        $this->assertCount(5, $result['category']);
        $this->assertEquals(
            [
                'category' => [
                    'is_active' => 1,
                    'include_in_menu' => 0,
                    'name' => 'Test category',
                    'parent_id' => 2,
                    'url_key' => 'test-category'
                ]
            ],
            $result
        );
    }
}
