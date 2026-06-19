<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\CrudFactory\Config;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use PHPUnit\Framework\TestCase;

final class ActionsTest extends TestCase
{
    public function testItCreatesIndexActionFromRoutePrefixWhenRequestConfigurationIsMissing(): void
    {
        $actions = Actions::new();
        $actions->setRoutePrefix('app_admin_post');
        $actions->addGlobalAction(Crud::PAGE_DETAIL, Action::INDEX);

        $gridActions = $actions
            ->getAsDto(Crud::PAGE_DETAIL)
            ->getGridActionsByType(Action::TYPE_GLOBAL);

        self::assertCount(1, $gridActions);
        self::assertSame(Action::INDEX, $gridActions[0]->getName());
        self::assertSame(
            [
                'type' => 'easy_crud_main_action',
                'label' => 'app.resource.admin.action.index',
                'icon' => 'tabler:list',
                'options' => [
                    'link' => [
                        'route' => 'app_admin_post_index',
                    ],
                ],
            ],
            $gridActions[0]->toArray(),
        );
    }
}
