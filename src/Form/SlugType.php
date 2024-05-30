<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\Asset\AssetEasyCrudPackage;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SlugType extends AbstractType implements AdminFormTypeInterface
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, array<int,mixed>>
     */
    public static function configureAdminAssets(): array
    {
        return [
            'js' => [
                (Asset::new('field-slug.js'))->package(AssetEasyCrudPackage::PACKAGE_NAME),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return ['@SyliusEasyCrudPlugin/field/slug/form.html.twig'];
    }
}
