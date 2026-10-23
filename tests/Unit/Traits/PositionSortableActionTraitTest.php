<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Traits;

use Adeliom\SyliusEasyCrudPlugin\Traits\Admin\PositionSortableActionTrait;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

/**
 * Deprecated trait, kept so that the classes using it still load (its action could not work).
 */
#[IgnoreDeprecations]
final class PositionSortableActionTraitTest extends TestCase
{
    public function testAClassUsingTheTraitLoadsAndItsActionPointsToTheField(): void
    {
        $controller = new class() {
            use PositionSortableActionTrait;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('PositionSortableField::new()->asTree()');

        $controller->sortPositionAction();
    }
}
