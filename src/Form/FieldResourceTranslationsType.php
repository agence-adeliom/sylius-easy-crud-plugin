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

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Webmozart\Assert\Assert;

final class FieldResourceTranslationsType extends AbstractType implements AdminFormTypeInterface
{
    /** @var string[] */
    private array $definedLocalesCodes;

    private string $defaultLocaleCode;

    private PropertyAccessor $propertyAccessor;

    /**
     * Translation field can be submitted twice, so submitted data is merged by locale
     * and kept between submissions (the form type is a shared service).
     *
     * @var array<array-key, mixed>
     */
    private array $translationsByLocale = [];

    /** @var array<array-key, object> */
    private array $translationsObjectsByLocale = [];

    public function __construct(
        TranslationLocaleProviderInterface $localeProvider,
        PropertyAccessor $propertyAccessor,
    ) {
        $this->definedLocalesCodes = $localeProvider->getDefinedLocalesCodes();
        $this->defaultLocaleCode = $localeProvider->getDefaultLocaleCode();
        $this->propertyAccessor = $propertyAccessor;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            /** @var array<string, mixed> $translations */
            $translations = $event->getData();

            $parentForm = $event->getForm()->getParent();
            Assert::notNull($parentForm);

            if (!empty($translations)) {
                foreach ($translations as $localeCode => $translation) {
                    if (!isset($this->translationsByLocale[$localeCode])) {
                        $this->translationsByLocale[$localeCode] = $translation;
                    } elseif (is_array($this->translationsByLocale[$localeCode]) && is_array($translation)) {
                        $this->translationsByLocale[$localeCode] = array_merge(
                            $this->translationsByLocale[$localeCode],
                            $translation,
                        );
                    }

                    if (null === $translation) {
                        // Apply previous data in case of null
                        // This trick fix non persistence of translations in case of translations multiple form parts
                        if (isset($this->translationsByLocale[$localeCode])) {
                            $translations[$localeCode] = $this->translationsByLocale[$localeCode];
                        } else {
                            unset($translations[$localeCode]);
                        }
                    }
                }
            }

            $event->setData($translations);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $translations = $event->getData();
            if (!is_array($translations) && !$translations instanceof Collection) {
                return;
            }

            if ($translations instanceof PersistentCollection) {
                $translations->initialize();
            }

            $parentForm = $event->getForm()->getParent();
            Assert::notNull($parentForm);

            /** @var TranslatableInterface $translatable */
            $translatable = $parentForm->getData();

            $count = count($translations);

            if ($count) {
                foreach ($translations as $localeCode => $translation) {
                    if (null === $translation) {
                        if (isset($this->translationsObjectsByLocale[$localeCode])) {
                            $translations[$localeCode] = $this->translationsObjectsByLocale[$localeCode];

                            continue;
                        }
                        unset($translations[$localeCode]);

                        continue;
                    }

                    if (is_object($translation)) {
                        $className = get_class($translation);
                        $objectNormalizer = new ObjectNormalizer();
                        $data = $this->translationsByLocale[$localeCode] ?? [];
                        if (!is_array($data)) {
                            $data = [];
                        }

                        $reflectionExtractor = new ReflectionExtractor();
                        $reflectionExtractor->getProperties($className);
                        foreach ($data as $property => $value) {
                            if (!is_string($property)) {
                                continue;
                            }
                            $actualValue = $this->propertyAccessor->getValue($translation, $property);
                            $propertyType = $reflectionExtractor->getType($className, $property);
                            if ($propertyType instanceof NullableType) {
                                $propertyType = $propertyType->getWrappedType();
                            }
                            if (
                                !is_array($actualValue) &&
                                $reflectionExtractor->isWritable($className, $property) &&
                                $propertyType instanceof ObjectType
                            ) {
                                $objectClassName = $propertyType->getClassName();
                                if ((is_string($actualValue) || is_object($actualValue)) && method_exists($actualValue, 'normalizeFormData')) {
                                    $value = $actualValue::normalizeFormData($value);
                                }

                                try {
                                    $objectValue = $objectNormalizer->denormalize($value, $objectClassName, null, [
                                        AbstractNormalizer::OBJECT_TO_POPULATE => $actualValue,
                                        AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
                                    ]);
                                    $this->propertyAccessor->setValue($translation, $property, $objectValue);
                                } catch (\Symfony\Component\Serializer\Exception\NotNormalizableValueException $e) {
                                    // some data cannot be denormalized, skip it
                                }
                            } elseif (
                                $reflectionExtractor->isWritable($className, $property)
                            ) {
                                $this->propertyAccessor->setValue($translation, $property, $value);
                            }
                        }

                        if ($translation instanceof TranslationInterface) {
                            $translation->setLocale($localeCode);
                            $translation->setTranslatable($translatable);
                        }
                        if (!isset($this->translationsObjectsByLocale[$localeCode])) {
                            $this->translationsObjectsByLocale[$localeCode] = $translation;
                        }
                    }
                }
            }

            $event->setData($translations);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
               'mapped' => true,
               'field' => null,
               'restrictToLocales' => [],
               'entries' => $this->definedLocalesCodes,
               'entry_name' => function (string $localeCode): string {
                   return $localeCode;
               },
               'entry_options' => function (string $localeCode): array {
                   return [
                       'required' => $localeCode === $this->defaultLocaleCode,
                   ];
               },
           ]);
    }

    public function getParent(): string
    {
        return FieldCollectionType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_translations';
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
        return ['@SyliusEasyCrudPlugin/field/translation/form.html.twig'];
    }
}
