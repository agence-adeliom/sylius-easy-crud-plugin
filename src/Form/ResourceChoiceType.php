<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Sylius\Bundle\ResourceBundle\Form\DataTransformer\CollectionToStringTransformer;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\RecursiveTransformer;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

class ResourceChoiceType extends AbstractType implements AdminFormTypeInterface
{
    public function __construct(
        protected ServiceRegistryInterface $resourceRepositoryRegistry,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        Assert::isInstanceOf($options['repository'], RepositoryInterface::class);
        Assert::nullOrString($options['choice_value']);

        if ($options['useResourceTransformers']) {
            if (!$options['multiple']) {
                $builder->addModelTransformer(
                    new ReversedTransformer(
                        new ResourceToIdentifierTransformer(
                            $options['repository'],
                            $options['choice_value'],
                        ),
                    ),
                );
            }

            if ($options['multiple']) {
                $builder
                    ->addModelTransformer(
                        new RecursiveTransformer(
                            new ReversedTransformer(
                                new ResourceToIdentifierTransformer(
                                    $options['repository'],
                                    $options['choice_value'],
                                ),
                            ),
                        ),
                    )
                    ->addViewTransformer(new CollectionToStringTransformer(','));
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired([
                'resource',
            ])
            ->setDefaults([
                'useResourceTransformers' => true,
                'multiple' => false,
                'error_bubbling' => false,
                'placeholder' => '',
                'choice_value' => 'id',
                'choice_label' => 'name',
                'choices' => function (Options $options) {
                    Assert::string($options['resource']);
                    $repository = $this->resourceRepositoryRegistry->get($options['resource']);

                    if (isset($options['repositoryMethod']) && null !== $options['repositoryMethod'] && null !== $options['repositoryArguments']) {
                        Assert::isArray($options['repositoryArguments']);

                        return $repository->$options['repositoryMethod'](...$options['repositoryArguments']);
                    }

                    return method_exists($repository, 'findAll') ? $repository->findAll() : [];
                },
                'repository' => function (Options $options) {
                    Assert::string($options['resource']);

                    return $this->resourceRepositoryRegistry->get($options['resource']);
                },
                'repositoryMethod' => null,
                'repositoryArguments' => null,
            ])
            ->setAllowedTypes('resource', ['string'])
            ->setAllowedTypes('multiple', ['bool'])
            ->setAllowedTypes('placeholder', ['string'])

            ->addAllowedTypes('repositoryMethod', ['string', 'null'])
            ->addAllowedTypes('repositoryArguments', ['array', 'null'])
            ->addAllowedTypes('useResourceTransformers', ['bool'])
        ;
    }

    /**
     * @return array<string, string|array<int,mixed>>
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

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
