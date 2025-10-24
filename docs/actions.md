# Actions Reference

Actions define the operations users can perform on entities in your CRUD interface. The Sylius Easy CRUD Plugin provides a flexible action system inspired by EasyAdminBundle, allowing you to customize buttons, links, and workflows across different pages.

## Table of Contents

- [Action Types](#action-types)
- [Built-in Actions](#built-in-actions)
- [Configuring Actions](#configuring-actions)
- [Custom Actions](#custom-actions)
- [Action Permissions](#action-permissions)
- [Batch Actions](#batch-actions)
- [Examples](#examples)

---

## Action Types

Actions are categorized by where they appear and what they operate on:

### 1. Global Actions

Displayed at the top of pages, operate on the entire view or create new resources.

**Common uses**: Create new entity, Back to list, Export data

### 2. Item Actions

Displayed on each row in the index/list page, operate on individual entities.

**Common uses**: Edit, View details, Delete

### 3. Batch Actions

Operate on multiple selected entities in the list view.

**Common uses**: Bulk delete, Bulk publish, Bulk export

### 4. Page-Specific Actions

Different CRUD pages support different action types:

| Page | Supported Action Types |
|------|----------------------|
| `Crud::PAGE_INDEX` | Global actions, Item actions, Batch actions |
| `Crud::PAGE_DETAIL` | Global actions |
| `Crud::PAGE_EDIT` | Global actions |
| `Crud::PAGE_NEW` | Global actions |

---

## Built-in Actions

The plugin provides several built-in actions ready to use:

### Index Page Actions

**Global Actions**:
- `Action::NEW` - Create new entity button

**Item Actions**:
- `Action::EDIT` - Edit entity button
- `Action::DETAIL` - View entity details button
- `Action::DELETE` - Delete entity button

**Batch Actions**:
- `Action::BATCH_DELETE` - Delete multiple entities at once

### Detail Page Actions

**Global Actions**:
- `Action::EDIT` - Edit current entity
- `Action::DELETE` - Delete current entity
- `Action::INDEX` - Back to list

### Edit/New Page Actions

**Global Actions**:
- `Action::SAVE_AND_RETURN` - Save and return to list
- `Action::SAVE_AND_CONTINUE` - Save and continue editing
- `Action::INDEX` - Cancel and return to list

---

## Configuring Actions

### Basic Configuration

Configure actions in your Admin class by overriding `configureActions()`:

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Crud;

class PostAdmin extends AbstractAdmin
{
    public function configureActions(): Actions
    {
        return parent::configureActions()
            // Customize existing actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Create New Post')
                    ->setIcon('fa fa-plus');
            })

            // Add custom actions
            ->add(Crud::PAGE_INDEX, Action::new('publish', 'Publish')
                ->linkToCrudAction('publish')
                ->setIcon('fa fa-eye'))

            // Remove actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }
}
```

### Action Methods

Each action can be customized using these methods:

#### Display Methods

```php
Action::new('myAction', 'My Action')
    ->setLabel('Custom Label')           // Button text
    ->setIcon('fa fa-star')             // Icon class
    ->setHtmlAttribute('class', 'btn-primary') // HTML attributes
    ->setCssClass('my-custom-class')    // Additional CSS classes
```

#### Link Methods

```php
// Link to a CRUD action
->linkToCrudAction('customAction')

// Link to a route
->linkToRoute('app_custom_route', ['id' => 'entityId'])

// Link to URL
->linkToUrl('/custom/path')

// Link to external URL
->linkToUrl('https://example.com')
```

#### Behavior Methods

```php
// Display action in a dropdown
->displayAsButton()       // Display as button (default)
->displayAsLink()         // Display as text link

// Configure confirmation modal
->displayIf(callable $callable)  // Conditional display
```

---

## Custom Actions

### Step 1: Define the Action in Admin Class

```php
public function configureActions(): Actions
{
    $publishAction = Action::new('publish', 'Publish Post')
        ->linkToCrudAction('publish')
        ->setIcon('fa fa-eye')
        ->setCssClass('btn-success');

    return parent::configureActions()
        ->add(Crud::PAGE_INDEX, $publishAction)
        ->add(Crud::PAGE_DETAIL, $publishAction);
}
```

### Step 2: Create a Custom Controller

If you need complex logic, create a custom controller:

**src/Controller/PostController.php**:
```php
<?php

namespace App\Controller;

use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostController extends SyliusCrudResourceController
{
    public function publishAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create(
            $this->metadata,
            $request
        );

        $resource = $this->repository->find($request->get('id'));

        if (!$resource) {
            throw $this->createNotFoundException();
        }

        // Your custom logic
        $resource->setPublished(true);
        $resource->setPublishedAt(new \DateTime());

        $this->manager->flush();

        $this->addFlash('success', 'Post has been published successfully!');

        return $this->redirectToRoute('app_admin_post_index');
    }
}
```

### Step 3: Register Custom Controller

Update your Sylius resource configuration in `config/packages/sylius_resources.yaml`:

```yaml
sylius_resource:
    resources:
        app.post:
            driver: doctrine/orm
            classes:
                model: App\Entity\Post
                controller: App\Controller\PostController
```

### Step 4: Add Route (if needed)

If using a custom action outside the standard CRUD flow, add a route:

**config/routes.yaml**:
```yaml
app_admin_post_publish:
    path: /admin/posts/{id}/publish
    methods: [POST]
    defaults:
        _controller: app.controller.post:publishAction
```

---

## Action Permissions

### Conditional Display

Show actions only when certain conditions are met:

```php
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Crud;

public function configureActions(): Actions
{
    return parent::configureActions()
        ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
            return $action->displayIf(function ($entity) {
                // Only show delete for unpublished posts
                return !$entity->isPublished();
            });
        });
}
```

### Checking User Permissions

```php
public function configureActions(): Actions
{
    return parent::configureActions()
        ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
            return $action->displayIf(function ($entity, $adminContext) {
                // Check if current user has permission
                return $this->security->isGranted('ROLE_ADMIN');
            });
        });
}
```

---

## Batch Actions

Batch actions allow operating on multiple entities simultaneously.

### Creating Batch Actions

```php
public function configureActions(): Actions
{
    $batchPublish = Action::new('batchPublish', 'Publish Selected')
        ->linkToCrudAction('batchPublish')
        ->setIcon('fa fa-eye')
        ->addCssClass('btn-success');

    return parent::configureActions()
        ->addBatchAction(Crud::PAGE_INDEX, $batchPublish);
}
```

### Handling Batch Actions

**In your controller**:
```php
public function batchPublishAction(Request $request): Response
{
    $ids = $request->request->get('ids', []);

    $resources = $this->repository->findBy(['id' => $ids]);

    foreach ($resources as $resource) {
        $resource->setPublished(true);
        $resource->setPublishedAt(new \DateTime());
    }

    $this->manager->flush();

    $this->addFlash('success', sprintf(
        '%d posts have been published!',
        count($resources)
    ));

    return $this->redirectToRoute('app_admin_post_index');
}
```

---

## Examples

### Example 1: Custom Export Action

```php
public function configureActions(): Actions
{
    $exportAction = Action::new('export', 'Export to CSV')
        ->linkToCrudAction('export')
        ->setIcon('fa fa-download')
        ->createAsGlobalAction(); // Display at top of page

    return parent::configureActions()
        ->add(Crud::PAGE_INDEX, $exportAction);
}
```

**Controller method**:
```php
public function exportAction(Request $request): Response
{
    $posts = $this->repository->findAll();

    $csv = "Title,Author,Published At\n";
    foreach ($posts as $post) {
        $csv .= sprintf(
            "%s,%s,%s\n",
            $post->getTitle(),
            $post->getAuthor(),
            $post->getPublishedAt()?->format('Y-m-d')
        );
    }

    return new Response($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="posts.csv"',
    ]);
}
```

### Example 2: Duplicate Entity Action

```php
public function configureActions(): Actions
{
    $duplicateAction = Action::new('duplicate', 'Duplicate')
        ->linkToCrudAction('duplicate')
        ->setIcon('fa fa-copy')
        ->displayAsButton();

    return parent::configureActions()
        ->add(Crud::PAGE_DETAIL, $duplicateAction)
        ->add(Crud::PAGE_INDEX, $duplicateAction); // Also show in list
}
```

**Controller method**:
```php
public function duplicateAction(Request $request): Response
{
    $original = $this->repository->find($request->get('id'));

    $duplicate = clone $original;
    $duplicate->setTitle($original->getTitle() . ' (Copy)');
    $duplicate->setCreatedAt(new \DateTime());

    $this->manager->persist($duplicate);
    $this->manager->flush();

    $this->addFlash('success', 'Post duplicated successfully!');

    return $this->redirectToRoute('app_admin_post_edit', [
        'id' => $duplicate->getId()
    ]);
}
```

### Example 3: Conditional Actions Based on Status

```php
public function configureActions(): Actions
{
    $publishAction = Action::new('publish', 'Publish')
        ->linkToCrudAction('publish')
        ->setIcon('fa fa-eye')
        ->setCssClass('btn-success')
        ->displayIf(fn($entity) => !$entity->isPublished());

    $unpublishAction = Action::new('unpublish', 'Unpublish')
        ->linkToCrudAction('unpublish')
        ->setIcon('fa fa-eye-slash')
        ->setCssClass('btn-warning')
        ->displayIf(fn($entity) => $entity->isPublished());

    return parent::configureActions()
        ->add(Crud::PAGE_INDEX, $publishAction)
        ->add(Crud::PAGE_INDEX, $unpublishAction);
}
```

### Example 4: Action with Confirmation Modal

For actions requiring confirmation, use Sylius' built-in confirmation system:

```php
public function configureActions(): Actions
{
    $archiveAction = Action::new('archive', 'Archive')
        ->linkToCrudAction('archive')
        ->setIcon('fa fa-archive')
        ->setHtmlAttribute('data-confirm', 'Are you sure you want to archive this post?');

    return parent::configureActions()
        ->add(Crud::PAGE_DETAIL, $archiveAction);
}
```
