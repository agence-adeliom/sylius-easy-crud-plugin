<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IconType extends AbstractType implements AdminFormTypeInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        // this defines the available options and their default values when
        // they are not configured explicitly when using the form type
        $resolver->setDefaults([
            'json_url' => '/bundles/syliuseasycrudplugin/iconpicker/bootstrap-icons-1.11.3.json',
            'search_placeholder' => 'Search Icon',
            'select_button' => 'Select Icon',
            'show_all_button' => 'Show All',
            'cancel_button' => 'Cancel',
            'no_result_found' => 'No results found.',
            'delete_label' => 'Delete',
            'border_radius' => '5px',
            'fonts' => [
                'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            ],
        ]);

        // optionally you can also restrict the options type or types (to get
        // automatic type validation and useful error messages for end users)
        $resolver->setAllowedTypes('json_url', ['string']);
        $resolver->setAllowedTypes('select_button', ['string']);
        $resolver->setAllowedTypes('search_placeholder', ['string']);
        $resolver->setAllowedTypes('show_all_button', ['string']);
        $resolver->setAllowedTypes('cancel_button', ['string']);
        $resolver->setAllowedTypes('no_result_found', ['string']);
        $resolver->setAllowedTypes('border_radius', ['string']);
        $resolver->setAllowedTypes('fonts', ['null', 'string', 'array']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars = array_merge($view->vars, $options);

        if (!empty($options['fonts'])) {
            $view->vars['fonts'] = is_string($options['fonts']) ? [$options['fonts']] : $options['fonts'];
        }
    }

    /**
     * @phpstan-return class-string<FormTypeInterface>
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'icon';
    }

    /**
     * @return array<string, array<int, Asset|string>>
     */
    public static function configureAdminAssets(): array
    {
        return [
            'css' => [
                'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return ['@SyliusEasyCrudPlugin/field/icon/form.html.twig'];
    }
}
