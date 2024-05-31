<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Enum;

use Adeliom\SyliusEasyCrudPlugin\Helper\Enum;

/**
 * ThreeStateStatus enum.
 *
 * @method static ThreeStateStatusEnum UNPUBLISHED()
 * @method static ThreeStateStatusEnum PENDING()
 * @method static ThreeStateStatusEnum PUBLISHED()
 */
final class ThreeStateStatusEnum extends Enum
{
    /**
     * @var string
     */
    public const UNPUBLISHED = 'unpublished';

    /**
     * @var string
     */
    public const PENDING = 'pending';

    /**
     * @var string
     */
    public const PUBLISHED = 'published';
}
