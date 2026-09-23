<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;

return (new Config())
    ->withProfile(
        (new Profile('default'))
        ->withSuite(
            (new Suite('admin_post', ['javascript' => false]))
            ->withPaths('%paths.base%/features')
            ->withContexts(
                'sylius.behat.context.hook.doctrine_orm',
                'sylius.behat.context.setup.admin_security',
                'sylius.behat.context.ui.admin.impersonating_customers',
                'sylius.behat.context.ui.admin.managing_customers',
                'tests.adeliom.sylius_easy_crud_plugin.behat.context.setup.post',
                'tests.adeliom.sylius_easy_crud_plugin.behat.context.ui.admin.managing_posts',
                'sylius.behat.context.ui.admin.notification',
            )
            ->withFilter(new TagFilter('@managing_posts&&@ui')),
        ),
    )
;
