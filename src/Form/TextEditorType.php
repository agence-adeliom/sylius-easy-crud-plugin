<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\Asset\AssetEasyCrudPackage;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextEditorType extends AbstractType implements AdminFormTypeInterface
{
    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'text_editor';
    }

    /**
     * @return array<string, array<int,mixed>>
     */
    public static function configureAdminAssets(): array
    {
        return [
            'js' => [
                (Asset::new('text-editor.js'))->package(AssetEasyCrudPackage::PACKAGE_NAME),
            ],
            'css' => [
                (Asset::new('text-editor.css'))->package(AssetEasyCrudPackage::PACKAGE_NAME),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return ['@SyliusEasyCrudPlugin/field/texteditor/form.html.twig'];
    }
}
