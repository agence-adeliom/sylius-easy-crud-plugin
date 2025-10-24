<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CodeEditorField;
use Adeliom\SyliusEasyCrudPlugin\Asset\AssetEasyCrudPackage;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\KeyValueStore;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CodeEditorType extends AbstractType implements CodeEditorTypeInterface
{
    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'code_editor';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['field'] = $form->getConfig()->getAttributes()['field'] ?? [
            'customOptions' => KeyValueStore::new([
                CodeEditorField::OPTION_INDENT_WITH_TABS => $options[CodeEditorField::OPTION_INDENT_WITH_TABS],
                CodeEditorField::OPTION_LANGUAGE => $options[CodeEditorField::OPTION_LANGUAGE],
                CodeEditorField::OPTION_NUM_OF_ROWS => $options[CodeEditorField::OPTION_NUM_OF_ROWS],
                CodeEditorField::OPTION_TAB_SIZE => $options[CodeEditorField::OPTION_TAB_SIZE],
                CodeEditorField::OPTION_SHOW_LINE_NUMBERS => $options[CodeEditorField::OPTION_SHOW_LINE_NUMBERS],
            ]),
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            CodeEditorField::OPTION_INDENT_WITH_TABS => false,
            CodeEditorField::OPTION_LANGUAGE => 'markdown',
            CodeEditorField::OPTION_NUM_OF_ROWS => 6,
            CodeEditorField::OPTION_TAB_SIZE => 4,
            CodeEditorField::OPTION_SHOW_LINE_NUMBERS => true,
        ]);
    }

    /**
     * @return array<string, array<int,string|Asset>>
     */
    public static function configureAdminAssets(): array
    {
        return [
            'js' => [
                (Asset::new('field-code-editor.js'))->package(AssetEasyCrudPackage::PACKAGE_NAME),
            ],
            'css' => [
                (Asset::new('field-code-editor.css'))->package(AssetEasyCrudPackage::PACKAGE_NAME),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return ['@SyliusEasyCrudPlugin/field/codeeditor/form.html.twig'];
    }
}
