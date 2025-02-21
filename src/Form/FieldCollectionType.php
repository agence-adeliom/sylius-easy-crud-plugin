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

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

final class FieldCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        Assert::isIterable($options['entries']);

        foreach ($options['entries'] as $entry) {
            Assert::isCallable($options['entry_type']);
            Assert::isCallable($options['entry_name']);
            Assert::isCallable($options['entry_options']);

            $entryName = $options['entry_name']($entry);
            $entryOptions = $options['entry_options']($entry);
            $entryType = $options['entry_type']($entry);

            if ($entryType !== FormType::class) {
                Assert::isCallable($options['entry_type']);

                $builder->add($entryName, $entryType, array_replace([
                    'property_path' => '[' . $entryName . ']',
                    'block_name' => 'entry',
                ], $entryOptions));
            } else {
                $formField = $builder
                    ->getFormFactory()
                    ->createNamedBuilder(
                        $entryName,
                        FormType::class,
                        null,
                        array_replace([
                              'property_path' => '[' . $entryName . ']',
                              'block_name' => 'entry',
                              'data_class' => $options['data_translation_class'],
                        ], $entryOptions),
                    );

                foreach ($options['fields'] as $field) {
                    $formField->add(
                        $field['name'],
                        $field['type'],
                        $field['type'] !== CodeEditorType::class ? $field['options'] : array_merge($field['options'], $field['customOptions'] ?? []),
                    );
                }

                $builder->add($formField);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('data_translation_class');
        $resolver->setAllowedTypes('data_translation_class', ['string']);

        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', ['array']);

        $resolver->setRequired('entries');
        $resolver->setAllowedTypes('entries', ['array', \Traversable::class]);

        $resolver->setRequired('entry_type');
        $resolver->setAllowedTypes('entry_type', ['string', 'callable']);
        $resolver->setNormalizer('entry_type', $this->optionalCallableNormalizer());

        $resolver->setRequired('entry_name');
        $resolver->setAllowedTypes('entry_name', ['callable']);

        $resolver->setDefault('entry_options', function () {
            return [];
        });
        $resolver->setAllowedTypes('entry_options', ['array', 'callable']);
        $resolver->setNormalizer('entry_options', $this->optionalCallableNormalizer());
        $resolver->setDefault('columns', 'col-12');
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_fixed_collection';
    }

    private function optionalCallableNormalizer(): \Closure
    {
        return
            /**
             * @param mixed $value
             *
             * @return mixed
             */
            function (Options $options, $value) {
                if (is_callable($value)) {
                    return $value;
                }

                return /** @return mixed */ function () use ($value) {
                    return $value;
                };
            }
        ;
    }
}
