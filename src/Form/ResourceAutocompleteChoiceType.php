<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Sylius\Bundle\ResourceBundle\Form\DataTransformer\CollectionToStringTransformer;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\RecursiveTransformer;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class ResourceAutocompleteChoiceType extends \Sylius\Bundle\ResourceBundle\Form\Type\ResourceAutocompleteChoiceType implements AdminFormTypeInterface
{
    public function __construct(
        protected ServiceRegistryInterface $resourceRepositoryRegistry,
        protected RouterInterface $router,
    ) {
        parent::__construct($resourceRepositoryRegistry);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        Assert::isInstanceOf($options['repository'], RepositoryInterface::class);
        Assert::nullOrString($options['choice_value']);

        if ($options['useResourceTransformers']) {
            if (!$options['multiple']) {
                $builder->addModelTransformer(
                    new ResourceToIdentifierTransformer(
                        $options['repository'],
                        $options['choice_value'],
                    ),
                );
            }

            if ($options['multiple']) {
                $builder
                    ->addModelTransformer(
                        new RecursiveTransformer(
                            new ResourceToIdentifierTransformer(
                                $options['repository'],
                                $options['choice_value'],
                            ),
                        ),
                    )
                    ->addViewTransformer(new CollectionToStringTransformer(','))
                ;
            }
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $parameters = [
            'resourceName' => $options['resource'],
        ];

        $view->vars['remote_url'] = $this->router->generate(
            $options['remote_route']['path'],
            array_merge(
                $options['remote_route']['parameters'],
                [
                    'repositoryMethod' => $options['repositoryMethod'],
                    'repositoryArguments' => json_encode(
                        $options['repositoryArguments'],
                    ),
                ],
                $parameters,
            ),
        );

        $view->vars['load_edit_url'] = $this->router->generate(
            $options['load_edit_route']['path'],
            array_merge(
                $options['load_edit_route']['parameters'],
                [
                    'repositoryMethod' => 'findBy',
                    'repositoryArguments' => json_encode([
                         [
                             $options['choice_value'] => "\${$options['choice_value']}",
                         ],
                    ]),
                ],
                $parameters,
            ),
        );

        $view->vars['remote_criteria_type'] = $options['remote_criteria_type'];
        $view->vars['remote_criteria_name'] = $options['remote_criteria_name'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('remote_route', ['path' => 'sylius_admin_ajax_resources', 'parameters' => []]);
        $resolver->setDefault('load_edit_route', ['path' => 'sylius_admin_ajax_resources', 'parameters' => []]);
        $resolver->setDefault('remote_criteria_type', 'contains');
        $resolver->setDefault('remote_criteria_name', 'phrase');
        $resolver->setDefault('repositoryMethod', 'findByPhrase');
        $resolver->setDefault('repositoryArguments', [
            'phrase' => '$phrase',
            'locale' => "expr:service('sylius.context.locale').getLocaleCode()",
            'limit' => 10,
        ]);
        $resolver->setDefault('choice_value', 'id');
        $resolver->setDefault('choice_name', 'name');
        $resolver->setDefault('resource', 'sylius.product');
        $resolver->setDefault('useResourceTransformers', true);

        $resolver->addAllowedTypes('remote_route', ['array']);
        $resolver->addAllowedTypes('load_edit_route', ['array']);
        $resolver->addAllowedTypes('remote_criteria_type', ['string']);
        $resolver->addAllowedTypes('remote_criteria_name', ['string']);
        $resolver->addAllowedTypes('repositoryMethod', ['string']);
        $resolver->addAllowedTypes('repositoryArguments', ['array']);
        $resolver->addAllowedTypes('useResourceTransformers', ['bool']);
    }

    /**
     * @return array<string, array<int,mixed>>
     */
    public static function configureAdminAssets(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return [];
    }
}
