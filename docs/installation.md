# Installation Steps

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

This file imports the plugin admin routes (`config/routes/admin.yaml`) under the Sylius admin path,
`/%sylius_admin.path_name%` (`/admin` unless you changed `SYLIUS_ADMIN_ROUTING_PATH_NAME`).

If you need another prefix or a dedicated admin host, import the admin routes yourself; they carry no prefix:

```yaml
sylius_easy_crud_admin:
    resource: "@SyliusEasyCrudPlugin/config/routes/admin.yaml"
    prefix: '/%sylius_admin.path_name%'   # adapt, or remove when the admin has its own host
    # host: '%admin_host%'
```

### Step 5: Register the Stimulus controllers

The admin fields with a Stimulus controller (`PositionSortableField`) need the plugin's controllers in the admin
build. Add the plugin assets to the `package.json` of the application:

```json
"dependencies": {
    "@agence-adeliom/sylius-easy-crud-plugin": "file:vendor/agence-adeliom/sylius-easy-crud-plugin/assets"
}
```

then enable them in the `controllers.json` of the admin build (`assets/admin/controllers.json`):

```json
"@agence-adeliom/sylius-easy-crud-plugin": {
    "position-sortable": {
        "enabled": true,
        "fetch": "lazy"
    }
}
```

and reinstall the packages and rebuild the assets (`npm install && npm run build`).

### Step 6: Install Assets

If your project uses frontend assets, install them:

```bash
php bin/console assets:install
```

### Step 7: Clear Cache

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
