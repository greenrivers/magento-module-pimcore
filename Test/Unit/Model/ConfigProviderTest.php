<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Unit\Model;

use Greenrivers\PimcoreIntegration\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    private ScopeConfigInterface|MockObject $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = new ConfigProvider($this->scopeConfigMock);
    }

    /**
     * @covers ConfigProvider::getEnabled
     */
    public function testGetEnabled(): void
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('greenrivers_pimcoreintegration/general/enabled', 'store')
            ->willReturn(true);

        $this->assertTrue($this->configProvider->getEnabled());
    }

    /**
     * @covers ConfigProvider::getPimcoreUrl
     */
    public function testGetPimcoreUrl(): void
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('greenrivers_pimcoreintegration/general/pimcore_url', 'store')
            ->willReturn('https://app.pimcore.test/');

        $this->assertEquals('https://app.pimcore.test/', $this->configProvider->getPimcoreUrl());
    }

    /**
     * @covers ConfigProvider::getPimcoreApiKey
     */
    public function testGetPimcoreApiKey(): void
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('greenrivers_pimcoreintegration/general/pimcore_api_key', 'store')
            ->willReturn('apikey123');

        $this->assertEquals('apikey123', $this->configProvider->getPimcoreApiKey());
    }

    /**
     * @covers ConfigProvider::getOverrideProduct
     */
    public function testGetOverrideProduct(): void
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('greenrivers_pimcoreintegration/magento/override_product', 'store')
            ->willReturn(true);

        $this->assertTrue($this->configProvider->getOverrideProduct());
    }

    /**
     * @covers ConfigProvider::getOverrideCategory
     */
    public function testGetOverrideCategory(): void
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('greenrivers_pimcoreintegration/magento/override_category', 'store')
            ->willReturn(true);

        $this->assertTrue($this->configProvider->getOverrideCategory());
    }
}
