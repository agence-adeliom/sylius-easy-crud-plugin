<div align="center">

# Sylius Easy CRUD Plugin

**Rapidly build admin CRUD interfaces in Sylius with ease**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![Sylius Version](https://img.shields.io/badge/sylius-%5E2.0-blue)](https://sylius.com)
[![Latest Version](https://img.shields.io/packagist/v/adeliom/sylius-easy-crud-plugin)](https://packagist.org/packages/adeliom/sylius-easy-crud-plugin)

[Features](#features) • [Installation](#installation) • [Quick Start](#quick-start) • [Documentation](#documentation)

</div>

---

## Overview

#### **Sylius Easy CRUD Plugin** is an abstraction layer that helps building CRUD interfaces in Sylius e-commerce.

🧩 Define your entire CRUD interface (list, create, edit, show, delete) in a single `configureFields()` method on a `App\Admin\ResourceAdmin` class based on Sylius resources — similar to EasyAdminBundle, but integrated with Sylius!

🚀 With this `Admin class` this plugin generates automatically all CRUD interfaces for you: `Grid`, `Creation/Edition forms`, `Show page`, and preconfigured CRUD `Actions`. It also provides a rich set of `form field types` that helps you build forms with minimal code.

✨ Transform Symfony entities / Sylius resources into fully functional admin interfaces in minutes!

```php
<?php

namespace App\Admin;

class PostAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('general', 'General Information')
            ->setHorizontal();

        yield Field::new('name');
        
        yield SlugField::new('slug');
        
        yield TextEditorField::new('description')
            ->onlyOnForms();
            
        ...
    }
}
```

### What Makes It Easy?

- **🚀 Rapid Development**: Generate complete CRUD interfaces in minutes with Symfony Maker commands
- **🎨 Rich Field Types**: 18+ specialized fields (Image, WYSIWYG, CodeEditor, Slug, Sortable Collections, and more)
- **🌍 i18n Ready**: Built-in translatable content mapped to Sylius Translation system
- **🧩 Highly Extensible**: Custom field configurators, actions, and form types
- **😎 Centralized configuration**: CRUD configuration in a single Admin class per resource

### Why we built this plugin?

From our previous Symfony projects, in [Adeliom](https://www.adeliom.com/), we were familiar with EasyAdminBundle. When migrating to Sylius, we looked for a fast and efficient way to reuse and build on our previous solutions ([several CMS bundles based on EasyAdmin](https://github.com/search?q=org%3Aagence-adeliom+easy-&type=repositories)), so we tried to integrate EasyAdmin's abstraction system directly into Sylius.

The result works fine so we decided to put this work public.

We started this project in 2022 before learning about the Sylius Stack approach. As a result, it does not fully comply with Sylius guidelines, so please use this plugin with caution. We will continue improving it to ensure compatibility with future Sylius versions.

### Easy CRUD and Happy CMS

Finally, alongside this plugin, we migrated our other EasyAdmin-based bundles — which provided content management (CMS) features — into a separate plugin called [Happy CMS](https://github.com/agence-adeliom/sylius-happy-cms-plugin). A possible alternative to build CMS features in Sylius.

---

## Installation

### Requirements

- PHP 8.2 or higher
- Sylius 2.0 or higher
- Symfony 6.4 or 7.1+

### 1. Install via Composer

```bash
composer require adeliom/sylius-easy-crud-plugin
composer require --dev symfony/maker-bundle
```

### 2. Enable the Bundle

Add the plugin to `config/bundles.php`:

```php
<?php

return [
    // ...
    Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
];
```

### 3. Import Configuration

In `config/packages/_sylius.yaml`:

```yaml
imports:
    - { resource: "@SyliusEasyCrudPlugin/config/config.yaml" }
```

### 4. Import Routes

In `config/routes.yaml`:

```yaml
sylius_easy_crud:
    resource: "@SyliusEasyCrudPlugin/config/routes.yaml"
```

### 5. Install Assets (if needed)

```bash
php bin/console assets:install
```

---

## Quick Start

### Step 1: Generate an Entity

Create a new entity with translation support:

```bash
php bin/console make:easy-crud:create-entity Post
```

This generates:
- `src/Entity/Post.php`
- `src/Entity/PostTranslation.php`
- `src/Repository/PostRepository.php`

### Step 2: Generate the CRUD

Create a complete CRUD interface for your entity:

```bash
php bin/console make:easy-crud:create-crud Post
```

This generates:
- `src/Admin/PostAdmin.php` - CRUD configuration class
- Updates `config/routes.yaml` - Adds admin routes
- Updates `config/packages/sylius_resources.yaml` - Registers Sylius resource
- Updates `src/Menu/AdminMenuListener.php` - Adds menu entry

### Step 3: Configure Your Fields

Edit `src/Admin/PostAdmin.php`:
[Discover available fields](./discover_fields.md)

```php
<?php

namespace App\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateTimeField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ImageField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SlugField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TextEditorField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\Enums\ColumnSizeEnum;

class PostAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        // Main Information Tab
        yield TabField::new('main', 'Main Information');

        yield ColumnField::new('_col1')
            ->setSize(ColumnSizeEnum::WIDE_8_OF_16)
            ->setLabel('Content');

        yield Field::new('title')
            ->setRequired(true)
            ->setHelp('The post title (max 60 characters)');

        yield SlugField::new('slug')
            ->onlyOnForms()
            ->setTargetFieldName('title')
            ->setHelp('URL-friendly version of the title');

        yield TextEditorField::new('content')
            ->onlyOnForms()
            ->setHelp('Post main content');

        yield ColumnField::new('_col2')
            ->setSize(ColumnSizeEnum::REGULAR_4_OF_16)
            ->setLabel('Sidebar');

        yield ImageField::new('featuredImage')
            ->onlyOnForms()
            ->setHelp('Post featured image');

        yield CheckboxField::new('enabled')
            ->setLabel('Published');

        yield DateTimeField::new('publishedAt')
            ->setHelp('Publication date and time');

        // Translations Tab
        yield TabField::new('translations', 'Translations');

        yield TranslationField::new('translations')
            ->addField(
                Field::new('translatedTitle')->setRequired(true)
            )
            ->setRequired(false);
    }
}
```

### Step 4: Access Your CRUD

Navigate to your Sylius admin panel and find your new "Posts" menu entry. You now have a fully functional CRUD interface!

---

## Documentation

### 📚 User Guides

- **[Getting Started Guide](./docs/README.md)**

---

## Roadmap

### Planned Features

- [ ] Improved maker commands with interactive mode and based on new Sylius Stack recommendations
- [ ] Tests coverage
- [ ] More field types
- [ ] Integrate an default Export functionality (CSV, Excel, PDF)
- [ ] Integrate a default Clone functionality

---

## License

This plugin is licensed under the **MIT License**. See [LICENSE](LICENSE) for details.

---

## Credits

[Adeliom](https://www.adeliom.com/)

**EasyAdminBundle** Core Team

---

## Support

- **Issues**: [GitHub Issues](https://github.com/agence-adeliom/sylius-easy-crud-plugin/issues)
- **Documentation**: [docs/](./docs/)
- **Discussions**: [GitHub Discussions](https://github.com/agence-adeliom/sylius-easy-crud-plugin/discussions)

---

<div align="center">

**If this plugin helped you, please consider giving it a ⭐ on GitHub!**

Made with ❤️ by [Adeliom](https://www.adeliom.com/)

</div>
