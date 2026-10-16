<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\TranslatableAdmin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Sylius\Resource\Factory\Factory;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

/**
 * A translatable model declared without translation sub-resource nor translatable factory.
 */
#[AsAdmin(
    resourceClass: Post::class,
    alias: 'app.post_without_translation',
    grid: 'app_admin_post_without_translation',
    resource: ['classes' => ['factory' => Factory::class], 'translation' => null],
)]
final class PostWithoutTranslationAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
