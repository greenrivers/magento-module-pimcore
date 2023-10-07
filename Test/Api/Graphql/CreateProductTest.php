<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Api\Graphql;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\Helper\Bootstrap as HelperBootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class CreateProductTest extends GraphQlAbstract
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
     * @magentoConfigFixture default_store greenrivers_pimcoreintegration/magento/override_product 1
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
            'status' => $status,
            'attribute_set_id' => $attributeSetId,
            'name' => $name,
            'sku' => $sku,
            'price' => $price
        ] = $response['createProduct']['product'];

        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $product = $productRepository->get('test');

        $this->assertEquals($status, $product->getStatus());
        $this->assertEquals($attributeSetId, $product->getAttributeSetId());
        $this->assertEquals($name, $product->getName());
        $this->assertEquals($sku, $product->getSku());
        $this->assertEquals($price, $product->getPrice());
    }

    /**
     * @return string
     */
    private function getMutation(): string
    {
        return <<<MUTATION
                mutation {
                    createProduct(
                        input: {
                            status: true
                            attribute_set_id: 4
                            name: "Test product"
                            sku: "test"
                            price: 23.99
                        }
                    ) {
                        product {
                            status
                            attribute_set_id
                            name
                            sku
                            price
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
}
