<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Re-indexes the submitted data of an array collection as a list.
 *
 * Collection entries are submitted with their form names as keys: after a deletion (`[0, 2]`) or an addition with a
 * non-sequential index, the array would be stored as a JSON object (`{"0": …, "2": …}`) instead of an array. Runs
 * after Symfony's ResizeFormListener (priority 50), so removed entries are already gone and new ones are appended in
 * submitted order. Collection objects (Doctrine collections) are left untouched: their keys are not persisted.
 *
 * Unlike CollectionType's `keep_as_list`, the child forms are not rebuilt.
 */
final class ReindexCollectionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => ['onSubmit', 0],
        ];
    }

    public function onSubmit(FormEvent $event): void
    {
        $data = $event->getData();

        if (!\is_array($data) || array_is_list($data)) {
            return;
        }

        $event->setData(array_values($data));
    }
}
