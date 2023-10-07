<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Service;

use Magento\Authorization\Model\UserContextInterface;

class AuthService
{
    /**
     * @param int $userType
     * @return bool
     */
    public function authenticate(int $userType): bool
    {
        return in_array(
            $userType,
            [UserContextInterface::USER_TYPE_INTEGRATION, UserContextInterface::USER_TYPE_ADMIN],
            true
        );
    }
}
