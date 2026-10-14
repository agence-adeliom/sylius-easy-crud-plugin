<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Form;

use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

/**
 * A sortable collection edits a list: after a deletion or an addition with a non-sequential index (the admin script
 * numbers new entries with a counter), the submitted value must still be a list, stored as a JSON array and not as
 * an object keyed by the form names.
 */
final class SortableCollectionTypeTest extends TestCase
{
    private FormFactoryInterface $factory;

    protected function setUp(): void
    {
        $this->factory = Forms::createFormFactoryBuilder()->getFormFactory();
    }

    public function testDeletingAnEntryKeepsAList(): void
    {
        $form = $this->collection(['A', 'B', 'C']);

        $form->submit(['0' => 'A', '2' => 'C']);

        self::assertSame(['A', 'C'], $form->getData());
        self::assertSame('["A","C"]', json_encode($form->getData()));
    }

    public function testAddingAnEntryWithANonSequentialIndexKeepsAList(): void
    {
        $form = $this->collection(['A', 'B']);

        $form->submit(['0' => 'A', '1' => 'B', '7' => 'New']);

        self::assertSame(['A', 'B', 'New'], $form->getData());
    }

    public function testDeletingAndAddingKeepsTheSubmittedOrder(): void
    {
        $form = $this->collection(['A', 'B', 'C']);

        $form->submit(['1' => 'B', '2' => 'C', '3' => 'D']);

        self::assertSame(['B', 'C', 'D'], $form->getData());
    }

    public function testReorderedEntriesRenamedByTheAdminScriptKeepTheirNewOrder(): void
    {
        $form = $this->collection(['A', 'B', 'C']);

        // after a drag and drop, the script renames the inputs after their new index
        $form->submit(['0' => 'C', '1' => 'A', '2' => 'B']);

        self::assertSame(['C', 'A', 'B'], $form->getData());
    }

    public function testEntriesOfACompoundTypeAreReindexedToo(): void
    {
        $form = $this->factory->create(SortableCollectionType::class, [['title' => 'A'], ['title' => 'B'], ['title' => 'C']], [
            'entry_type' => TitleType::class,
            'allow_add' => true,
            'allow_delete' => true,
        ]);

        $form->submit(['0' => ['title' => 'A'], '2' => ['title' => 'C']]);

        self::assertSame([['title' => 'A'], ['title' => 'C']], $form->getData());
    }

    public function testKeysCanBeKeptWithKeepAsListDisabled(): void
    {
        $form = $this->collection(['A', 'B', 'C'], ['keep_as_list' => false]);

        $form->submit(['0' => 'A', '2' => 'C']);

        self::assertSame([0 => 'A', 2 => 'C'], $form->getData());
    }

    public function testAnObjectCollectionIsLeftAsIs(): void
    {
        $data = new ArrayCollection(['A', 'B', 'C']);
        $form = $this->collection($data);

        $form->submit(['0' => 'A', '2' => 'C']);

        self::assertSame($data, $form->getData());
        self::assertSame([0 => 'A', 2 => 'C'], $data->toArray());
    }

    public function testANestedSortableCollectionIsReindexed(): void
    {
        $form = $this->factory->createBuilder(FormType::class, ['items' => ['A', 'B', 'C']])
            ->add('items', SortableCollectionType::class, ['allow_add' => true, 'allow_delete' => true])
            ->getForm();

        $form->submit(['items' => ['1' => 'B', '2' => 'C']]);

        self::assertSame(['items' => ['B', 'C']], $form->getData());
    }

    /**
     * @param array<array-key, mixed>|ArrayCollection<array-key, mixed> $data
     * @param array<string, mixed> $options
     */
    private function collection(array|ArrayCollection $data, array $options = []): FormInterface
    {
        return $this->factory->create(SortableCollectionType::class, $data, array_merge([
            'allow_add' => true,
            'allow_delete' => true,
            'allow_drag' => true,
        ], $options));
    }
}
