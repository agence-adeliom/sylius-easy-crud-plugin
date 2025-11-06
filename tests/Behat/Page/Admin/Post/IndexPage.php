<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post;

use Behat\Mink\Exception\ElementNotFoundException;
use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;

class IndexPage extends SymfonyPage implements IndexPageInterface
{
    public function getRouteName(): string
    {
        return 'admin_tests_adeliom_sylius_easy_crud_plugin_entity_post_index';
    }

    public function countItems(): int
    {
        $rows = $this->getDocument()->findAll('css', 'table tbody tr');

        return count($rows);
    }

    public function hasPostWithName(string $name): bool
    {
        return null !== $this->getDocument()->find('css', sprintf('table tbody tr:contains("%s")', $name));
    }

    public function isPostEnabled(string $name): bool
    {
        $row = $this->getDocument()->find('css', sprintf('table tbody tr:contains("%s")', $name));

        if (null === $row) {
            return false;
        }

        $enabledCell = $row->find('css', 'td[data-field="enabled"]');

        return null !== $enabledCell && str_contains($enabledCell->getText(), 'Yes');
    }

    public function deletePost(string $name): void
    {
        $row = $this->getDocument()->find('css', sprintf('table tbody tr:contains("%s")', $name));

        if (null === $row) {
            throw new ElementNotFoundException($this->getDriver(), 'post row', 'css', $name);
        }

        $deleteButton = $row->find('css', 'button.sylius-delete-resource-button, a[data-action="delete"]');

        if (null === $deleteButton) {
            throw new ElementNotFoundException($this->getDriver(), 'delete button');
        }

        $deleteButton->click();
        $this->getDocument()->waitFor(1, function () {
            return $this->hasElement('.confirmation-modal, .swal2-popup');
        });

        $confirmButton = $this->getDocument()->find('css', '.confirmation-modal .confirm-button, .swal2-confirm');
        if (null !== $confirmButton) {
            $confirmButton->click();
        }
    }

    public function filterByEnabled(string $status): void
    {
        $filter = $this->getDocument()->find('css', 'select[name="criteria[enabled]"]');

        if (null === $filter) {
            throw new ElementNotFoundException($this->getDriver(), 'enabled filter');
        }

        $filter->selectOption($status === 'Yes' ? '1' : '0');

        $this->getDocument()->pressButton('Filter');
    }

    public function hasColumn(string $columnName): bool
    {
        return null !== $this->getDocument()->find('css', sprintf('table thead th[data-field="%s"]', $columnName));
    }

    public function waitForNotification(string $message): void
    {
        $this->getDocument()->waitFor(5, function () use ($message) {
            return $this->hasElement(sprintf('.notification:contains("%s"), .alert:contains("%s")', $message, $message));
        });
    }

    protected function hasElement(string $name, ?array $parameters = []): bool
    {
        return null !== $this->getDocument()->find('css', $name);
    }
}
