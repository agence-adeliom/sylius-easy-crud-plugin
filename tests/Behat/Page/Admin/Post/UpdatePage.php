<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post;

use Behat\Mink\Exception\ElementNotFoundException;
use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;

class UpdatePage extends SymfonyPage implements UpdatePageInterface
{
    public function getRouteName(): string
    {
        return 'admin_tests_adeliom_sylius_easy_crud_plugin_entity_post_update';
    }

    public function saveChanges(): void
    {
        $this->getDocument()->pressButton('Save changes');
    }

    public function getPostName(): string
    {
        $nameField = $this->getDocument()->find('css', 'input[name*="[translations]"][name*="[name]"]');

        if (null === $nameField) {
            throw new ElementNotFoundException($this->getDriver(), 'name field');
        }

        return $nameField->getValue();
    }

    public function waitForNotification(string $message): void
    {
        $this->getDocument()->waitFor(5, function () use ($message) {
            return null !== $this->getDocument()->find('css', sprintf('.notification:contains("%s"), .alert:contains("%s")', $message, $message));
        });
    }
}
