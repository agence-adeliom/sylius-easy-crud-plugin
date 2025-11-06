<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post;

use Behat\Mink\Exception\ElementNotFoundException;
use FriendsOfBehat\PageObjectExtension\Page\SymfonyPage;

class CreatePage extends SymfonyPage implements CreatePageInterface
{
    public function getRouteName(): string
    {
        return 'tests_adeliom_sylius_easy_crud_plugin_admin_tests_adeliom_sylius_easy_crud_plugin_entity_post_create';
    }

    public function specifyName(string $name): void
    {
        $this->getDocument()->fillField('name', $name);
    }

    public function specifyTranslatedName(string $name, string $locale): void
    {
        $localeCode = $this->getLocaleCode($locale);
        $field = $this->getDocument()->find('css', sprintf('input[name*="[translations][%s][name]"]', $localeCode));

        if (null === $field) {
            throw new ElementNotFoundException($this->getDriver(), 'name field', 'css', $localeCode);
        }

        $field->setValue($name);
    }

    public function specifyTranslatedDescription(string $description, string $locale): void
    {
        $localeCode = $this->getLocaleCode($locale);
        $field = $this->getDocument()->find('css', sprintf('textarea[name*="[translations][%s][description]"]', $localeCode));

        if (null === $field) {
            throw new ElementNotFoundException($this->getDriver(), 'description field', 'css', $localeCode);
        }

        $field->setValue($description);
    }

    public function enable(): void
    {
        $checkbox = $this->getDocument()->find('css', 'input[name*="[enabled]"]');

        if (null === $checkbox) {
            throw new ElementNotFoundException($this->getDriver(), 'enabled checkbox');
        }

        $checkbox->check();
    }

    public function create(): void
    {
        $this->getDocument()->pressButton('Create');
    }

    public function switchTab(string $tabName): void
    {
        $tab = $this->getDocument()->find('css', sprintf('a.nav-link:contains("%s"), button.nav-link:contains("%s")', $tabName, $tabName));

        if (null === $tab) {
            throw new ElementNotFoundException($this->getDriver(), 'tab', 'css', $tabName);
        }

        $tab->click();
    }

    public function selectState(string $state): void
    {
        $radio = $this->getDocument()->find('css', sprintf('input[name*="[state]"][value="%s"]', strtolower($state)));

        if (null === $radio) {
            throw new ElementNotFoundException($this->getDriver(), 'state radio', 'css', $state);
        }

        $radio->click();
    }

    public function selectIcon(string $icon): void
    {
        $field = $this->getDocument()->find('css', 'input[name*="[icon]"]');

        if (null === $field) {
            throw new ElementNotFoundException($this->getDriver(), 'icon field');
        }

        $field->setValue($icon);
    }

    public function fillCodeEditor(string $code): void
    {
        // For code editor, we might need to use JavaScript
        $textarea = $this->getDocument()->find('css', 'textarea[name*="[codeEditor]"]');

        if (null === $textarea) {
            throw new ElementNotFoundException($this->getDriver(), 'code editor');
        }

        $this->getDriver()->executeScript(sprintf('document.querySelector(\'textarea[name*="[codeEditor]"]\').value = %s', json_encode($code)));
    }

    public function attachImage(string $filename): void
    {
        $fileInput = $this->getDocument()->find('css', 'input[name*="[image]"][type="file"]');

        if (null === $fileInput) {
            throw new ElementNotFoundException($this->getDriver(), 'image upload field');
        }

        $fileInput->attachFile($filename);
    }

    public function specifyEmbedUrl(string $url): void
    {
        $field = $this->getDocument()->find('css', 'input[name*="[embed]"]');

        if (null === $field) {
            throw new ElementNotFoundException($this->getDriver(), 'embed field');
        }

        $field->setValue($url);
    }

    public function selectTaxon(string $taxonName): void
    {
        // This depends on how ResourceChoiceField is implemented (autocomplete, select, etc.)
        $select = $this->getDocument()->find('css', 'select[name*="[taxon]"]');

        if (null !== $select) {
            $select->selectOption($taxonName);
        } else {
            // Try autocomplete
            $input = $this->getDocument()->find('css', 'input[name*="[taxon]"]');
            if (null !== $input) {
                $input->setValue($taxonName);
                $this->getDocument()->waitFor(1, function () use ($taxonName) {
                    return null !== $this->getDocument()->find('css', sprintf('.autocomplete-results .option:contains("%s")', $taxonName));
                });
                $option = $this->getDocument()->find('css', sprintf('.autocomplete-results .option:contains("%s")', $taxonName));
                if (null !== $option) {
                    $option->click();
                }
            }
        }
    }

    public function selectProducts(array $productNames): void
    {
        // Similar to selectTaxon but for multiple selection
        foreach ($productNames as $productName) {
            if ($element = $this->getDocument()->find('css', sprintf('select[name*="[products][]"] option:contains("%s")', $productName))) {
                $element->setValue($productName);
                $this->getDocument()->waitFor(1, function () {
                    return true;
                });
            }
        }
    }

    public function addCollectionItem(): void
    {
        $addButton = $this->getDocument()->find('css', 'button[data-collection-add], a[data-collection-add]');

        if (null === $addButton) {
            throw new ElementNotFoundException($this->getDriver(), 'add collection button');
        }

        $addButton->click();
    }

    public function selectChoiceMaskOption(string $option): void
    {
        $radio = $this->getDocument()->find('css', sprintf('input[name*="[choice_mask]"][value="%s"]', $option));

        if (null === $radio) {
            throw new ElementNotFoundException($this->getDriver(), 'choice mask option', 'css', $option);
        }

        $radio->click();
    }

    public function hasField(string $fieldName): bool
    {
        return null !== $this->getDocument()->find('css', sprintf('[name*="[%s]"]', $fieldName));
    }

    public function hasTab(string $tabName): bool
    {
        return null !== $this->getDocument()->find('css', sprintf('a.nav-link:contains("%s"), button.nav-link:contains("%s")', $tabName, $tabName));
    }

    private function getLocaleCode(string $locale): string
    {
        $localeMap = [
            'English (United States)' => 'en_US',
            'French (France)' => 'fr_FR',
        ];

        return $localeMap[$locale] ?? 'en_US';
    }
}
