# Installation Guide

This guide provides detailed installation instructions for the Sylius Easy CRUD Plugin.

## Table of Contents

- [Installation Steps](#installation-steps)
- [Configuration](#configuration)
- [Verification](#verification)
- [Troubleshooting](#troubleshooting)

---

## Installation Steps

### Step 1: Install via Composer

```bash
composer require adeliom/sylius-easy-crud-plugin
```

To use the `make:easy-crud:*` commands, install Symfony Maker Bundle:

```bash
composer require --dev symfony/maker-bundle
```

### Step 2: Enable the Bundle

If you're using **Symfony Flex**, the bundle should be automatically registered. Otherwise, manually add it to `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
];
```

### Step 3: Import Plugin Configuration

Create or update `config/packages/_sylius.yaml` to import the plugin's configuration:

```yaml
imports:
    - { resource: "@SyliusEasyCrudPlugin/config/config.yaml" }
```

**Note**: The underscore prefix in `_sylius.yaml` ensures it loads after other Sylius configuration files.

### Step 4: Import Plugin Routes

Add the plugin routes to `config/routes.yaml`:

```yaml
sylius_easy_crud:
    resource: "@SyliusEasyCrudPlugin/config/routes.yaml"
```

### Step 5: Install Assets

If your project uses frontend assets, install them:

```bash
php bin/console assets:install
```

### Step 6: Clear Cache

Clear your Symfony cache:

```bash
php bin/console cache:clear
```

---

## Next Steps

After successful installation:

1. **Read the [Getting Started Guide](./start_using_easy_crud.md)** to create your first CRUD
2. **Explore [Field Types](./discover_fields.md)** to understand available fields
3. **Review [Entity Traits](./entity_traits.md)** for common entity features
