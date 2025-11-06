<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;

interface IndexPageInterface extends SymfonyPageInterface
{
    public function countItems(): int;

    public function hasPostWithName(string $name): bool;

    public function isPostEnabled(string $name): bool;

    public function deletePost(string $name): void;

    public function filterByEnabled(string $status): void;

    public function hasColumn(string $columnName): bool;

    public function waitForNotification(string $message): void;
}
