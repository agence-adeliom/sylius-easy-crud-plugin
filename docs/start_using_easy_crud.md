## Create a custom CRUD

#### 1. Change Sylius default `doctrine.yaml` mapping type to `attribute`
```yaml
mappings:
    App:
        type: attribute
```

#### 2. Create a new Symfony entity

- You can use `php bin/console make:entity`
And follow Sylius documentation to declare entity as a   [Sylius resource](https://docs.sylius.com/en/latest/cookbook/entities/custom-model.html#register-your-entity-as-a-sylius-resource).
- Or, we provide the bundle command `php bin/console make:easy-crud:create-entity Post`

This command will create 3 files :
```bash
$ src/Entity/Post.php
$ src/Entity/PostTranslation.php
$ src/Repository/PostRepository.php
```

#### 3. Generate an CRUD based on your entity

- Execute : `php bin/console make:easy-crud:generate Post`

This command will create or modify files :
```bash
# This file allow you to configure your crud
$ creation : src/Admin/PostAdmin.php
# This optional file allow you to create custom action
$ creation : src/Controller/PostController.php
# A Sylius route based on your entity is added automatically
$ modification : config/routes.yaml
# A Sylius resource based on your entity is added automatically
$ modification : config/packages/sylius_resources.yaml
# A Sylius resource based on your entity is added automatically
$ creation and configuration of a Sylius menu Listener : src/Menu/MenuListener.php
```

You can execute without entity name `php bin/console make:easy-crud:generate` and choose an existing entity.

- Then, `php bin/console cache:clear`
- Then, `php bin/console doctrine:migrations:diff`
- Then, `php bin/console doctrine:migrations:migrate`

4. Change Sylius Menu Listener to add your new custom crud

Follow documentation [here](https://docs.sylius.com/en/latest/customization/menu.html).

- Do not forget to add new entity into [menu](https://docs.sylius.com/en/latest/customization/menu.html).
- Navigate to https://sylius-site.ddev.site/admin/posts/

5. Now you can learn more about Admin class configuration

- Visit [here](./discover_fields) to see all default form fields available.
