<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\View;

use Knp\Menu\FactoryInterface;

final readonly class CrudViewBuilderFactory
{
    public function __construct(
        private FactoryInterface $menuFactory,
    ) {
    }

    public function create(): CrudViewBuilder
    {
        return new CrudViewBuilder($this->menuFactory);
    }
}
