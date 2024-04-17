# Create a custom model & easy crud

- `ddev console make:entity` #Post id, name, description
- change `doctrine.yml` : type: attribute into App mappings
- `ddev console doctrine:migrations:diff`
- `ddev console doctrine:migrations:migrate`
- Add `ResourceInterface` to your model class :
  ```php  
    use Sylius\Component\Resource\Model\ResourceInterface;
    class Post implements ResourceInterface { 
  ```
- Change repository to extends `EntityRepository` :
```php  
    use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository
    class PostRepository implements EntityRepository { 
        # remove constructor
  ```
- Register your entity as described here :
  [Sylius resource](https://docs.sylius.com/en/latest/cookbook/entities/custom-model.html#register-your-entity-as-a-sylius-resource)
- `ddev console cache:clear`
- `ddev console make:easy-crud`
    - Or to generate a default Sylius grid follow [documentation](https://github.com/Sylius/SyliusGridBundle/blob/master/docs/your_first_grid.md) instead.
- Declare the admin form info `config/packages/sylius_resource.yaml`
```
sylius_resource:
    resources:
        app.post:
            ...
            classes:
                ...
                form: App\Admin\Post\PostAdmin
```
- Declare the admin route and configuration into : `config/routes.yaml`
```
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
- `ddev console cache:clear`
- Do not forget to add new entity into [menu](https://docs.sylius.com/en/latest/customization/menu.html).
- Navigate to https://sylius-site.ddev.site/admin/posts/
- More documentation [here](https://docs.sylius.com/en/latest/cookbook/entities/custom-model.html)
