<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Model;

use Greenrivers\PimcoreIntegration\Api\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    public const XML_PATH_ENABLED = 'greenrivers_pimcoreintegration/general/enabled';
    public const XML_PATH_PIMCORE_URL = 'greenrivers_pimcoreintegration/general/pimcore_url';
    public const XML_PATH_PIMCORE_API_KEY = 'greenrivers_pimcoreintegration/general/pimcore_api_key';

    public const XML_PATH_OVERRIDE_PRODUCT = 'greenrivers_pimcoreintegration/magento/override_product';
    public const XML_PATH_OVERRIDE_CATEGORY = 'greenrivers_pimcoreintegration/magento/override_category';

    /**
     * ConfigProvider constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * @inheritDoc
     */
    public function getEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritDoc
     */
    public function getPimcoreUrl(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_PIMCORE_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritDoc
     */
    public function getPimcoreApiKey(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_PIMCORE_API_KEY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritDoc
     */
    public function getOverrideProduct(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_OVERRIDE_PRODUCT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @inheritDoc
     */
    public function getOverrideCategory(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_OVERRIDE_CATEGORY, ScopeInterface::SCOPE_STORE);
    }
}
