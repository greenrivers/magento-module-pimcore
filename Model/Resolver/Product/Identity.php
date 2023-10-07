<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Model\Resolver\Product;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

class Identity implements IdentityInterface
{
    private string $cacheTag = 'greenrivers_pimcoreintegration_product';

    /**
     * @inheritDoc
     */
    public function getIdentities(array $resolvedData): array
    {
        return [$this->cacheTag];
    }
}
