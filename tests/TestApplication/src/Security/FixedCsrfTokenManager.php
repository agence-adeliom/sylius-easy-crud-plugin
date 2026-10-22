<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Security;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * CSRF token manager of the `test_position_sort` environment, without session: the only valid token is `valid`.
 */
final class FixedCsrfTokenManager implements CsrfTokenManagerInterface
{
    public const VALID_TOKEN = 'valid';

    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, self::VALID_TOKEN);
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        return $this->getToken($tokenId);
    }

    public function removeToken(string $tokenId): ?string
    {
        return null;
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        return self::VALID_TOKEN === $token->getValue();
    }
}
