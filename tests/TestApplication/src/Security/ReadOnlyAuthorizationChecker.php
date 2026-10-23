<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Security;

use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Authorization checker of the resource controllers in the `test_position_sort` environment: every permission is
 * refused to the users with the `ROLE_READ_ONLY` role, granted to the others.
 */
final readonly class ReadOnlyAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function isGranted(RequestConfiguration $configuration, string $permission): bool
    {
        return !$this->security->isGranted('ROLE_READ_ONLY');
    }
}
