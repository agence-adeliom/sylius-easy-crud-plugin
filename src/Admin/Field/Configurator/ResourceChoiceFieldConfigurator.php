<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ResourceChoiceField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ResourceChoiceFieldConfigurator implements FieldConfiguratorInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return ResourceChoiceField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null): void
    {
        // Get the class directly from resource declaration if not set
        if (empty($field->getFormTypeOption('class')) && $field->getFormTypeOption('resource')) {
            /** @var array<string, array{
             *  driver: string,
             *  classes: array{
             *     model: class-string,
             *     controller: class-string,
             *     repository: class-string,
             *     form: class-string,
             *     factory: class-string,
             *  },
             *  translation: array{
             *      classes: array{
             *          model: class-string,
             *          controller: class-string,
             *          repository: class-string,
             *          form: class-string,
             *          factory: class-string
             *      }
             *  }
             * }> $resources */
            $resources = $this->parameterBag->get('sylius.resources');
            if (isset($resources[$field->getFormTypeOption('resource')]['classes']['model'])) {
                $field->setFormTypeOption(
                    'class',
                    $resources[$field->getFormTypeOption('resource')]['classes']['model'],
                );
            }
        }
    }

    public function formatValue(FieldDto $field, mixed $value): mixed
    {
        return $value;
    }
}
