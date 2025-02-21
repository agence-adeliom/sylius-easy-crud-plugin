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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Webmozart\Assert\Assert;

final class FieldResourceTranslationsType extends AbstractType implements AdminFormTypeInterface
{
    /** @var string[] */
    private array $definedLocalesCodes;

    private string $defaultLocaleCode;

    private PropertyAccessor $propertyAccessor;

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
        // Translation field can be submitted twice
        // So we need do merge all parts on translations submission
        // To do that we store data by local into global var $translationsByLocale and persist data for next loop
        global $translationsByLocale;
        if (!isset($translationsByLocale)) {
            $translationsByLocale = [];
        }

        global $translationsFormTypes;
        if (!isset($translationsFormTypes)) {
            $translationsFormTypes = [];
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            /** @var array<string, mixed> $translations */
            $translations = $event->getData();

            $parentForm = $event->getForm()->getParent();
            Assert::notNull($parentForm);

            global $translationsByLocale;

            if (!empty($translations)) {
                foreach ($translations as $localeCode => $translation) {
                    if (!isset($translationsByLocale[$localeCode])) {
                        $translationsByLocale[$localeCode] = $translation;
                    } elseif (is_array($translationsByLocale[$localeCode]) && is_array($translation)) {
                        $translationsByLocale[$localeCode] = array_merge(
                            $translationsByLocale[$localeCode],
                            $translation,
                        );
                    }

                    if (null === $translation) {
                        // Apply previous data in case of null
                        // This trick fix non persistence of translations in case of translations multiple form parts
                        if (isset($translationsByLocale[$localeCode])) {
                            $translations[$localeCode] = $translationsByLocale[$localeCode];
                        } else {
                            unset($translations[$localeCode]);
                        }
                    }
                }
            }

            $event->setData($translations);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var PersistentCollection<string, mixed>|ArrayCollection<string, mixed> $translations */
            $translations = $event->getData();

            if ($translations instanceof PersistentCollection) {
                $translations->initialize();
            }

            global $translationsByLocale;

            $parentForm = $event->getForm()->getParent();
            Assert::notNull($parentForm);

            global $translationsObjectsByLocale;
            if (!isset($translationsObjectsByLocale)) {
                $translationsObjectsByLocale = [];
            }

            /** @var TranslatableInterface $translatable */
            $translatable = $parentForm->getData();

            if ($translations->count()) {
                foreach ($translations as $localeCode => $translation) {
                    if (null === $translation) {
                        if (isset($translationsObjectsByLocale[$localeCode])) {
                            $translations[$localeCode] = $translationsObjectsByLocale[$localeCode];

                            continue;
                        }
                        unset($translations[$localeCode]);

                        continue;
                    }

                    if (is_object($translation)) {
                        $className = get_class($translation);
                        $objectNormalizer = new ObjectNormalizer();
                        $data = $translationsByLocale[$localeCode] ?? [];

                        $reflectionExtractor = new ReflectionExtractor();
                        $reflectionExtractor->getProperties($className);
                        foreach ($data as $property => $value) {
                            $actualValue = $this->propertyAccessor->getValue($translation, $property);
                            if (
                                !is_array($actualValue) &&
                                $reflectionExtractor->isWritable($className, $property) &&
                                ($types = $reflectionExtractor->getTypes($className, $property)) &&
                                $types[0]->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT
                            ) {
                                $objectClassName = $reflectionExtractor->getTypes($className, $property)[0]->getClassName();
                                if (method_exists($actualValue, 'normalizeFormData')) {
                                    $value = $actualValue::normalizeFormData($value);
                                }
                                $objectValue = $objectNormalizer->denormalize($value, $objectClassName, null, [
                                    AbstractNormalizer::OBJECT_TO_POPULATE => $actualValue,
                                    AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE,
                                ]);
                                $this->propertyAccessor->setValue($translation, $property, $objectValue);
                            } elseif (
                                $reflectionExtractor->isWritable($className, $property)
                            ) {
                                $this->propertyAccessor->setValue($translation, $property, $value);
                            }
                        }

                        $translation->setLocale($localeCode);
                        $translation->setTranslatable($translatable);
                        if (!isset($translationsObjectsByLocale[$localeCode])) {
                            $translationsObjectsByLocale[$localeCode] = $translation;
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
               'columns' => 'col-12',
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
     * @return array<string, array<int,string|Asset>>
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
