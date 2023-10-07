<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Service;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

class GraphqlService
{
    /**
     * GraphqlService constructor.
     * @param Client $client
     * @param ConfigProviderInterface $configProvider
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly Client                  $client,
        private readonly ConfigProviderInterface $configProvider,
        private readonly SerializerInterface     $serializer
    )
    {
    }

    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     */
    public function getProducts(array $data): array
    {
        $body = [
            'query' => 'query ($first: Int, $after: Int) {
                            getMagentoIntegrationProductListing(first: $first, after: $after) {
                                edges {
                                    node {
                                        status
                                        attribute_set_id: attributeSetId
                                        name
                                        sku
                                        price
                                    }
                                }
                            }
                        }',
            'variables' => $data
        ];

        $response = $this->makeRequest($body);
        return $this->processResponse($response, 'getMagentoIntegrationProductListing');
    }

    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     */
    public function getCategories(array $data): array
    {
        $body = [
            'query' => 'query ($first: Int, $after: Int) {
                            getMagentoIntegrationCategoryListing(first: $first, after: $after) {
                                edges {
                                    node {
                                        is_active: isActive
                                        include_in_menu: includeInMenu
                                        name
                                        parent_id: parentCategoryId
                                    }
                                }
                            }
                        }',
            'variables' => $data
        ];

        $response = $this->makeRequest($body);
        return $this->processResponse($response, 'getMagentoIntegrationCategoryListing');
    }

    /**
     * @param ResponseInterface $response
     * @param string $queryType
     * @return array
     */
    private function processResponse(ResponseInterface $response, string $queryType): array
    {
        $contents = $this->serializer->unserialize($response->getBody()->getContents());
        return $contents['data'][$queryType]['edges'];
    }

    /**
     * @param array $body
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function makeRequest(array $body): ResponseInterface
    {
        $pimcoreUrl = $this->configProvider->getPimcoreUrl();
        $pimcoreApiKey = $this->configProvider->getPimcoreApiKey();

        return $this->client->request(Request::METHOD_POST, $pimcoreUrl . 'pimcore-graphql-webservices/greenrivers', [
            'headers' => [
                'apikey' => $pimcoreApiKey,
                'Content-Type' => 'application/json'
            ],
            'body' => $this->serializer->serialize($body)
        ]);
    }
}
