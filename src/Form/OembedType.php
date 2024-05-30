<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormTypeInterface;

class OembedType extends AbstractType implements AdminFormTypeInterface
{
    /**
     * @phpstan-return class-string<FormTypeInterface>
     */
    public function getParent(): ?string
    {
        return UrlType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'oembed';
    }

    /**
     * @return array<string, string[]>
     */
    public static function configureAdminAssets(): array
    {
        return [];
    }

    public static function configureAdminFormThemes(): array
    {
        return ['@SyliusEasyCrudPlugin/field/oembed/widget.html.twig'];
    }
}
