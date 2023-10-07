<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Test\Unit\Service;

use Greenrivers\PimcoreIntegration\Service\AuthService;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->authService = new AuthService();
    }

    /**
     * @covers AuthService::authenticate
     */
    public function testAuthenticate(): void
    {
        $this->assertTrue($this->authService->authenticate(1));
    }
}
