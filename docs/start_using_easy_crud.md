## Create a custom CRUD

1. Create a newSymfony entity (ex: Post)

- Create a new entity:
`php bin/console make:entity`
- Change `doctrine.yaml`: 
```yaml
mappings:
    App:
        type: attribute
```
- Execute:
`php bin/console doctrine:migrations:diff`
- Execute:
`php bin/console doctrine:migrations:migrate`

2. Transform your entity as a Sylius Resource :

- Add `ResourceInterface` to your entity model class :
  ```php  
    use Sylius\Component\Resource\Model\ResourceInterface;
    class Post implements ResourceInterface { 
  ```
- Extends the created Repo with `EntityRepository` :
```php  
    use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository
    class PostRepository implements EntityRepository { 
        # remove constructor
  ```
- Register your entity as described here :
  [Sylius resource](https://docs.sylius.com/en/latest/cookbook/entities/custom-model.html#register-your-entity-as-a-sylius-resource)
- `php bin/console cache:clear`

3. Generate your easy CRUD:

The following steps allows you to generate a Admin class file.
With this Admin class you will be able to configure easily and with a method fortly inspired form the EasyAdmin Symfony Bundle :
    - The Sylius Grid (list, filters, actions)
    - The Sylius default edit, create and show view
    - Create other custom view


- Execute: `php bin/console make:easy-crud` and choose the "Post" entity
- Declare the admin form info `config/packages/sylius_resource.yaml`
```yaml
sylius_resource:
    resources:
        app.post:
            ...
            classes:
                ...
                form: App\Admin\Post\PostAdmin
```
- Declare the admin route and configuration into : `config/routes.yaml`
```yaml
app_admin_post:
    resource: |
        alias: app.post
        section: admin
        templates: "@SyliusEasyCrudPlugin\\crud"
        #except: ['show']
        redirect: update
        grid: app_admin_post
        form: 
            type: App\Admin\Post\PostAdmin
            options:
                context: $context
        vars:
            all:
                subheader: app.ui.post # define a translation key for your entity subheader
                templates:
                    form: "@SyliusEasyCrudPlugin\\crud\\form\\_form.html.twig"
            index:
                icon: 'file image outline' # choose an icon that will be displayed next to the subheader
            update:
                redirect: 
                    route: update
                    parameters:
                        context: $context
                        id: $id
                route:
                    parameters:
                        context: $context
                        id: $id
    type: sylius.resource
    prefix: admin
```
- Execute: `php bin/console cache:clear`

4. Change Sylius Menu Listener to add your new custom crud

Follow documentation [here](https://docs.sylius.com/en/latest/customization/menu.html).

- Do not forget to add new entity into [menu](https://docs.sylius.com/en/latest/customization/menu.html).
- Navigate to https://sylius-site.ddev.site/admin/posts/

5. Now you can learn more about Admin class configuration

- Visit [here](./discover_fields) to see all default form fields available.
