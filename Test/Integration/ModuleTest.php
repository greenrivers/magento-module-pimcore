<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Integration;

use Magento\Framework\Module\ModuleList\Loader;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    private ?Loader $loader;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->loader = $objectManager->create(Loader::class);
    }

    public function testModuleSequence(): void
    {
        $moduleName = 'Greenrivers_PimcoreIntegration';
        $expectedDependencies = ['Magento_Catalog', 'Magento_GraphQl', 'Magento_MessageQueue'];

        $modulesList = $this->loader->load();

        $this->assertArrayHasKey($moduleName, $modulesList);
        $moduleConfig = $modulesList[$moduleName];
        $this->assertContains($expectedDependencies, $moduleConfig);
    }
}
