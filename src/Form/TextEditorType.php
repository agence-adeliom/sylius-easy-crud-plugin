<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\Asset\AssetEasyCrudPackage;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('trixEditorConfig', null);
        $resolver->setAllowedTypes('trixEditorConfig', ['array', 'null']);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['trixEditorConfig'] = $options['trixEditorConfig'] ?? null;
    }

    /**
     * @return array<string, array<int,string|Asset>>
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
