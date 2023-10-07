<?php

namespace Greenrivers\PimcoreIntegration\Api;

interface ConfigProviderInterface
{
    /**
     * @return bool
     */
    public function getEnabled(): bool;

    /**
     * @return string
     */
    public function getPimcoreUrl(): string;

    /**
     * @return string
     */
    public function getPimcoreApiKey(): string;

    /**
     * @return bool
     */
    public function getOverrideProduct(): bool;

    /**
     * @return bool
     */
    public function getOverrideCategory(): bool;
}
