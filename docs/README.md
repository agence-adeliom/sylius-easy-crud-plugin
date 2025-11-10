# Documentation

Welcome to the complete documentation for Sylius Easy CRUD Plugin. This guide will help you quickly build beautiful admin CRUD interfaces in Sylius.

## Getting Started

1. **[Installation Guide](./installation.md)** - Step-by-step installation instructions with troubleshooting


2. **[Create a CRUD](./start_using_easy_crud.md)** - Create your first CRUD based on a new or existing entity
    - Generate a new entity with translation support
    - Generate a complete CRUD interface
    - Configure menus and routes


3. **[Discover available fields](./discover_fields.md)** - Complete guide to all 18+ available field types
    - Basic fields (text, checkbox, date)
    - Advanced fields (image, WYSIWYG, code editor)
    - Relationship fields (resource choice, translations)
    - Layout fields (tabs, columns)
    - Collections (sortable, standard)


[//]: # (4. **[How to create action for your CRUD]&#40;./actions.md&#41;** - Customize buttons, links, and workflows)

[//]: # (    - Built-in actions)

[//]: # (    - Custom actions)

[//]: # (    - Batch actions)

[//]: # (    - Action permissions)

[//]: # ()
[//]: # ()
[//]: # (5. **[Create a custom field]&#40;./create_your_own_fields.md&#41;** - For specific needs you can create your own field types)

[//]: # (## Optional)

[//]: # ()
[//]: # (1. **[Discover available Traits]&#40;./entity_traits.md&#41;** - Ready-to-use traits for common entity features)

[//]: # (    - ID and naming traits)

[//]: # (    - Status and publishable traits)

[//]: # (    - Timestamp and soft delete traits)

[//]: # (    - Sortable and translation traits)

## Contributing

### How to Contribute

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Install the development environment:

```bash
# Install environment
make init
make database-init
make load-fixtures
make frontend-clear

# Run tests
make phpunit

# Code quality
make phpstan
make ecs

# More help
make help

# Build bundles assets (use node 20+)
yarn install
make plugin-asset-watch
#or
make plugin-asset-build
```

---
