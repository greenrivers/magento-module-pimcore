<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Api\Graphql;

use Exception;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as HelperBootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class CreateCategoryTest extends GraphQlAbstract
{
    private ?ObjectManagerInterface $objectManager;

    private ?AdminTokenServiceInterface $adminTokenService;

    protected function setUp(): void
    {
        $this->objectManager = HelperBootstrap::getObjectManager();
        $this->adminTokenService = $this->objectManager->get(AdminTokenServiceInterface::class);
    }

    /**
     * @magentoConfigFixture default_store greenrivers_pimcoreintegration/general/enabled 1
     * @magentoConfigFixture default_store greenrivers_pimcoreintegration/magento/override_category 1
     */
    public function testMutation(): void
    {
        $mutation = $this->getMutation();
        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getAdminHeaderAuthentication(Bootstrap::ADMIN_NAME, Bootstrap::ADMIN_PASSWORD)
        );

        [
            'is_active' => $isActive,
            'include_in_menu' => $includeInMenu,
            'name' => $name,
            'parent_id' => $parentId
        ] = $response['createCategory']['category'];

        $category = $this->getCategory('Test category', 2);

        $this->assertEquals($isActive, $category->getIsActive());
        $this->assertEquals($includeInMenu, $category->getIncludeInMenu());
        $this->assertEquals($name, $category->getName());
        $this->assertEquals($parentId, $category->getParentId());
    }

    /**
     * @return string
     */
    private function getMutation(): string
    {
        return <<<MUTATION
                mutation {
                    createCategory(
                        input: {
                            is_active: true
                            include_in_menu: true
                            name: "Test category"
                            parent_id: 2
                        }
                    ) {
                        category {
                            is_active
                            include_in_menu
                            name
                            parent_id
                        }
                    }
                }
                MUTATION;
    }

    /**
     * @param string $userName
     * @param string $password
     * @return string[]
     * @throws AuthenticationException
     */
    private function getAdminHeaderAuthentication(string $userName, string $password): array
    {
        try {
            $adminAccessToken = $this->adminTokenService->createAdminAccessToken($userName, $password);
            return ['Authorization' => 'Bearer ' . $adminAccessToken];
        } catch (Exception $e) {
            throw new AuthenticationException(
                __(
                    'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.'
                )
            );
        }
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
}
