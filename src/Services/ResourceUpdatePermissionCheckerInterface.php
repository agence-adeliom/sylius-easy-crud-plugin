<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Sylius\Resource\Metadata\MetadataInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The permission to update a resource, checked outside of its update route.
 */
interface ResourceUpdatePermissionCheckerInterface
{
    /**
     * @throws AccessDeniedHttpException when the permission is refused
     */
    public function denyUnlessGranted(MetadataInterface $metadata, Request $request): void;
}
