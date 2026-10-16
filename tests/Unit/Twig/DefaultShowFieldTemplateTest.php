<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Twig;

use Adeliom\SyliusEasyCrudPlugin\Twig\CrudRenderExtension;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Grid\Parser\OptionsParserInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * The default show template of a field (templates/field/default/show.html.twig).
 */
final class DefaultShowFieldTemplateTest extends TestCase
{
    #[Test]
    public function a_collection_of_entities_is_displayed_item_by_item_instead_of_being_serialized(): void
    {
        $handle = new ShowFieldEntity('Handle');
        $panel = new ShowFieldEntity('Panel');
        // circular references, like a self-referencing ManyToMany: the serializer fails on them
        $handle->related = $panel;
        $panel->related = $handle;

        self::assertSame('Handle, Panel', $this->render(new ArrayCollection([$handle, $panel])));
    }

    #[Test]
    public function a_list_of_scalars_is_joined(): void
    {
        self::assertSame('a, 2, true', $this->render(['a', 2, true]));
    }

    #[Test]
    public function nested_arrays_are_still_serialized(): void
    {
        self::assertSame('serialized', $this->render([['key' => 'value']]));
    }

    #[Test]
    public function a_scalar_is_displayed_as_is(): void
    {
        self::assertSame('Handle', $this->render('Handle'));
    }

    private function render(mixed $data): string
    {
        $twig = new Environment(new FilesystemLoader());
        $twig->getLoader()->addPath(\dirname(__DIR__, 3) . '/templates', 'SyliusEasyCrudPlugin');
        $twig->addExtension(new CrudRenderExtension(
            PropertyAccess::createPropertyAccessor(),
            new ParameterBag(),
            $twig,
            $this->createMock(OptionsParserInterface::class),
        ));
        $twig->addFilter(new TwigFilter('trans', static fn (string $label): string => $label));
        $twig->addFilter(new TwigFilter('serialize', static fn (): string => 'serialized'));

        $html = $twig->render('@SyliusEasyCrudPlugin/field/default/show.html.twig', [
            'field' => ['label' => 'Handles', 'property' => 'handles'],
            'data' => $data,
        ]);

        return trim(strip_tags(preg_replace('/^.*<div class="col-12 col-md-8">/s', '', $html) ?? ''));
    }
}

final class ShowFieldEntity implements \Stringable
{
    public ?self $related = null;

    public function __construct(private readonly string $name)
    {
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
