<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;

final class TranslationConfigurator implements FieldConfiguratorInterface
{
    public function __construct(
        protected TranslationLocaleProviderInterface $localeProvider,
    ) {
    }

    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return TranslationField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null): void
    {
        $field->setCustomOption('definedLocalesCodes', $this->localeProvider->getDefinedLocalesCodes());
        $field->setCustomOption('defaultLocaleCode', $this->localeProvider->getDefaultLocaleCode());
    }

    public function formatValue(FieldDto $field, mixed $value): mixed
    {
        return $value;
    }
}
