<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;

interface UpdatePageInterface extends SymfonyPageInterface
{
    public function saveChanges(): void;

    public function getPostName(): string;

    public function waitForNotification(string $message): void;
}
