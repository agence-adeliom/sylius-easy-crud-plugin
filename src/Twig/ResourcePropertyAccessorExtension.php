<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Twig;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ResourcePropertyAccessorExtension extends AbstractExtension
{
    public function __construct(
        protected PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'get_resource_property_value',
                [$this, 'propertyValue'],
            ),
        ];
    }

    public function propertyValue(object $resource, string $propertyPath): mixed
    {
        if ($this->propertyAccessor->isReadable($resource, $propertyPath)) {
            return $this->propertyAccessor->getValue($resource, $propertyPath);
        }

        return null;
    }
}
