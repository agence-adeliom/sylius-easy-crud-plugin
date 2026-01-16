<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use function PHPUnit\Framework\assertTrue;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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

        // If this form type is used directly on a entity field set false
        // If this form type is used in sub form type collection and saved as array in database, set true
        if ($options['persist_into_an_array']) {
            if (!$options['multiple']) {
                $builder->addModelTransformer(
                    new CallbackTransformer(
                        function (string|int|null $tag) use ($options): ?ResourceInterface {
                            if (null !== $tag) {
                                return $options['repository']->findOneBy([
                                                                             $options['choice_value'] => $tag,
                                                                         ]);
                            }

                            return null;
                        },
                        function (?ResourceInterface $tagsAsResource) use ($options): string|int {
                            if (null !== $tagsAsResource) {
                                $choiceValue = $options['choice_value'];
                                if (is_string($choiceValue) && method_exists($tagsAsResource, 'get' . ucfirst($choiceValue))) {
                                    /** @phpstan-ignore-next-line */
                                    $return = call_user_func([$tagsAsResource, 'get' . ucfirst($choiceValue)]);
                                    assertTrue(is_string($return) || is_int($return));

                                    return $return;
                                }
                            }

                            return '';
                        },
                    ),
                );
            } elseif ($options['multiple']) {
                $builder
                    ->addModelTransformer(
                        new CallbackTransformer(
                            function (array|string|null $tagsAsArray) use ($options): Collection {
                                $valueAsCollection = new ArrayCollection();
                                if (is_string($tagsAsArray)) {
                                    // If you switch from non multiple value to multiple value, the value will be a string
                                    // If the tag have some separators, we need to split it
                                    if (str_contains($tagsAsArray, ',')) {
                                        $tagsAsArray = explode(',', $tagsAsArray);
                                    } else {
                                        $valueAsCollection->add($options['repository']->findOneBy([
                                          $options['choice_value'] => $tagsAsArray,
                                        ]));
                                    }
                                }
                                if (is_array($tagsAsArray)) {
                                    foreach ($tagsAsArray as $key => $tag) {
                                        if (is_string($tag) or is_int($tag)) {
                                            // If the tag is a string, we can assume it's an ID
                                            $valueAsCollection->add($options['repository']->findOneBy([
                                                                                                          $options['choice_value'] => $tag,
                                                                                                      ]));
                                        }
                                    }
                                }

                                return $valueAsCollection;
                            },
                            function (Collection $tagsAsCollection) use ($options): string {
                                $valuesAsString = '';
                                foreach ($tagsAsCollection as $key => $tag) {
                                    $choiceValue = $options['choice_value'];
                                    if (is_string($choiceValue) && is_object($tag) && method_exists($tag, 'get' . ucfirst($choiceValue))) {
                                        /** @phpstan-ignore-next-line */
                                        $return = call_user_func([$tag, 'get' . ucfirst($choiceValue)]);
                                        assertTrue(is_string($return) || is_int($return));
                                        $valuesAsString .= $return;
                                    }
                                    $valuesAsString .= ',';
                                }

                                return substr($valuesAsString, 0, -1); // Remove the last comma
                            },
                        ),
                    );
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired([
                              'class',
                              'resource',
                              'choice_name',
                          ])
            ->setDefaults([
                              'class' => null,
                              'resource' => null,
                              'autocomplete' => true,
                              'persist_into_an_array' => false,
                              'multiple' => false,
                              'error_bubbling' => false,
                              'by_reference' => function (Options $options) {
                                  // When multiple is true, set by_reference to false
                                  // to ensure addXxx/removeXxx methods are called on the entity
                                  return !$options['multiple'];
                              },
                              'placeholder' => '',
                              'choice_value' => 'id',
                              'choice_label' => 'name',
                              'choice_name' => 'name',
                              'choices' => function (Options $options) {
                                  Assert::string($options['resource']);
                                  $repository = $this->resourceRepositoryRegistry->get($options['resource']);

                                  if (isset($options['repositoryMethod']) && null !== $options['repositoryMethod'] && null !== $options['repositoryArguments']) {
                                      Assert::isArray($options['repositoryArguments']);

                                      /** @phpstan-ignore-next-line */
                                      return call_user_func([$repository, $options['repositoryMethod']], ...$options['repositoryArguments']);
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
            ->setAllowedTypes('multiple', ['bool'])
            ->setAllowedTypes('placeholder', ['string'])

            ->addAllowedTypes('resource', ['string', 'null'])
            ->addAllowedTypes('repositoryMethod', ['string', 'null'])
            ->addAllowedTypes('repositoryArguments', ['array', 'null'])
            ->addAllowedTypes('persist_into_an_array', ['bool'])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
    }

    /**
     * @return array<string, array<int, Asset|string>>
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
        return EntityType::class;
    }
}
