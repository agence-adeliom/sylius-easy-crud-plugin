<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldConfiguratorCollection;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CrudAdminFactory
{
    public function __construct(
        protected DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser,
        protected FieldConfiguratorCollection $fieldConfiguratorCollection,
        protected PropertyAccessor $propertyAccessor,
        public RequestStack $requestStack,
    ) {
    }

    public function getFieldConfiguratorCollection(): FieldConfiguratorCollection
    {
        return $this->fieldConfiguratorCollection;
    }

    public function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor;
    }

    public function getDoctrineOrmTypeGuesser(): DoctrineOrmTypeGuesser
    {
        return $this->doctrineOrmTypeGuesser;
    }
}
