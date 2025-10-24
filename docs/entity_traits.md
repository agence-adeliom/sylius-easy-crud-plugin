# Entity Traits Reference

The Sylius Easy CRUD Plugin provides a comprehensive collection of reusable entity traits that implement common patterns used in e-commerce and content management applications. These traits follow Symfony and Doctrine best practices and can be easily combined to create feature-rich entities.

## Available Traits

### 1. EntityIdTrait

Provides an auto-incrementing primary key.

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait`

**Properties**:
- `id` - Integer, auto-incremented primary key

**Methods**:
- `getId(): ?int` - Returns the entity ID

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityIdTrait;

class Product
{
    use EntityIdTrait;

    // Your other properties and methods
}
```

---

### 2. EntityNameTrait

Adds a `name` property to your entity.

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntityNameTrait`

**Properties**:
- `name` - String, the entity name

**Methods**:
- `getName(): ?string` - Returns the entity name
- `setName(?string $name): self` - Sets the entity name

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityNameTrait;

class Category
{
    use EntityIdTrait;
    use EntityNameTrait;
}

$category = new Category();
$category->setName('Electronics');
```

---

### 3. EntityNameSlugTrait

Adds both `name` and `slug` properties for SEO-friendly URLs.

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntityNameSlugTrait`

**Properties**:
- `name` - String, the entity name
- `slug` - String, URL-friendly slug

**Methods**:
- `getName(): ?string`
- `setName(?string $name): self`
- `getSlug(): ?string`
- `setSlug(?string $slug): self`

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityNameSlugTrait;

class BlogPost
{
    use EntityIdTrait;
    use EntityNameSlugTrait;
}

$post = new BlogPost();
$post->setName('My First Post');
$post->setSlug('my-first-post');
```

**In Admin Class**:
```php
yield Field::new('name')->setRequired(true);
yield SlugField::new('slug')->setTargetFieldName('name');
```

---

### 4. EntityStatusTrait

Provides a simple enabled/disabled status flag.

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntityStatusTrait`

**Properties**:
- `enabled` - Boolean, whether the entity is enabled

**Methods**:
- `isEnabled(): bool` - Returns true if enabled
- `setEnabled(bool $enabled): self` - Sets the enabled status

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityStatusTrait;

class Product
{
    use EntityIdTrait;
    use EntityStatusTrait;
}

$product = new Product();
$product->setEnabled(true);

if ($product->isEnabled()) {
    // Display product
}
```

**In Admin Class**:
```php
yield CheckboxField::new('enabled')->setLabel('Published');
```

---

### 5. EntityThreeStateStatusTrait

Provides a three-state status: enabled, disabled, or archived.

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntityThreeStateStatusTrait`

**Properties**:
- `status` - Enum of type `ThreeStateStatusEnum`

**Methods**:
- `getStatus(): ?ThreeStateStatusEnum` - Returns the current status
- `setStatus(?ThreeStateStatusEnum $status): self` - Sets the status
- `isEnabled(): bool` - Returns true if status is ENABLED
- `isDisabled(): bool` - Returns true if status is DISABLED
- `isArchived(): bool` - Returns true if status is ARCHIVED

**Available States**:
- `ThreeStateStatusEnum::ENABLED`
- `ThreeStateStatusEnum::DISABLED`
- `ThreeStateStatusEnum::ARCHIVED`

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityThreeStateStatusTrait;
use Adeliom\SyliusEasyCrudPlugin\Enums\ThreeStateStatusEnum;

class Product
{
    use EntityIdTrait;
    use EntityThreeStateStatusTrait;
}

$product = new Product();
$product->setStatus(ThreeStateStatusEnum::ENABLED);

if ($product->isArchived()) {
    // Handle archived product
}
```

**In Admin Class**:
```php
yield EnumField::new('status')
    ->setLabel('Status')
    ->setRequired(true);
```

---

### 6. EntityPublishableTrait

Provides publication status with optional publication dates.

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntityPublishableTrait`

**Properties**:
- `published` - Boolean, whether the entity is published
- `publishedAt` - DateTime, when the entity was/will be published
- `unpublishedAt` - DateTime, when the entity should be unpublished

**Methods**:
- `isPublished(): bool`
- `setPublished(bool $published): self`
- `getPublishedAt(): ?\DateTimeInterface`
- `setPublishedAt(?\DateTimeInterface $publishedAt): self`
- `getUnpublishedAt(): ?\DateTimeInterface`
- `setUnpublishedAt(?\DateTimeInterface $unpublishedAt): self`

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityPublishableTrait;

class Article
{
    use EntityIdTrait;
    use EntityPublishableTrait;
}

$article = new Article();
$article->setPublished(true);
$article->setPublishedAt(new \DateTime('2024-01-01 10:00:00'));
```

**In Admin Class**:
```php
yield CheckboxField::new('published');
yield DateTimeField::new('publishedAt')->setHelp('Schedule publication');
yield DateTimeField::new('unpublishedAt')->setHelp('Auto-unpublish date');
```

---

### 7. EntityTimestampableTrait

Automatically tracks creation and update timestamps.

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait`

**Properties**:
- `createdAt` - DateTime, when the entity was created
- `updatedAt` - DateTime, when the entity was last updated

**Methods**:
- `getCreatedAt(): ?\DateTimeInterface`
- `setCreatedAt(?\DateTimeInterface $createdAt): self`
- `getUpdatedAt(): ?\DateTimeInterface`
- `setUpdatedAt(?\DateTimeInterface $updatedAt): self`

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntityTimestampableTrait;

class Post
{
    use EntityIdTrait;
    use EntityTimestampableTrait;
}

// Timestamps are usually managed by Doctrine lifecycle callbacks
// or Gedmo Timestampable extension
```

**Note**: To auto-populate these fields, use Doctrine lifecycle callbacks or install `gedmo/doctrine-extensions`:

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
class Post
{
    use EntityTimestampableTrait;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
```

---

### 8. EntitySoftDeletableTrait

Implements soft delete functionality (entities are marked as deleted instead of being removed).

**Location**: `Adeliom\SyliusEasyCrudPlugin\Traits\EntitySoftDeletableTrait`

**Properties**:
- `deletedAt` - DateTime, when the entity was soft-deleted

**Methods**:
- `getDeletedAt(): ?\DateTimeInterface`
- `setDeletedAt(?\DateTimeInterface $deletedAt): self`
- `isDeleted(): bool` - Returns true if entity is soft-deleted
- `restore(): self` - Restores a soft-deleted entity

**Usage**:
```php
use Adeliom\SyliusEasyCrudPlugin\Traits\EntitySoftDeletableTrait;

class Product
{
    use EntityIdTrait;
    use EntitySoftDeletableTrait;
}

$product->setDeletedAt(new \DateTime()); // Soft delete
if ($product->isDeleted()) {
    // Handle deleted product
}

$product->restore(); // Restore
```

**Integration with Doctrine**: For automatic filtering of soft-deleted entities, install and configure `gedmo/doctrine-extensions` or use custom repository filters.

---
