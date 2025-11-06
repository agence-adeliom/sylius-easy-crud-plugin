<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;

interface CreatePageInterface extends SymfonyPageInterface
{
    public function specifyName(string $name): void;

    public function specifyTranslatedName(string $name, string $locale): void;

    public function specifyTranslatedDescription(string $description, string $locale): void;

    public function enable(): void;

    public function create(): void;

    public function switchTab(string $tabName): void;

    public function selectState(string $state): void;

    public function selectIcon(string $icon): void;

    public function fillCodeEditor(string $code): void;

    public function attachImage(string $filename): void;

    public function specifyEmbedUrl(string $url): void;

    public function selectTaxon(string $taxonName): void;

    public function selectProducts(array $productNames): void;

    public function addCollectionItem(): void;

    public function selectChoiceMaskOption(string $option): void;

    public function hasField(string $fieldName): bool;

    public function hasTab(string $tabName): bool;
}
