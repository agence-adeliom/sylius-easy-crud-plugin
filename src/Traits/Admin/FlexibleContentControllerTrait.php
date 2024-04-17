<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Traits\Admin;

use Symfony\Component\HttpFoundation\Request;

trait FlexibleContentControllerTrait
{
    /** @param class-string $adminType */
    public function overrideRequestConfiguration(Request $request, string $adminType): void
    {
        if ($request->query->has('context') && str_starts_with($request->query->get('context'), 'flexible_content:')) {
            $locale = array_filter(explode(':', $request->query->get('context')));
            if ([] !== $locale && count($locale) === 2) {
                $request->attributes->set('_locale', $locale[1]);
                $_sylius = $request->attributes->get('_sylius');
                $_sylius['template'] = '@SyliusEasyCrudPlugin\crud\update.html.twig';
                $_sylius['vars']['templates']['form'] = '@SyliusEasyCrudPlugin\crud\form\_form.html.twig';
                $_sylius['form'] = [
                    'type' => $adminType,
                    'options' => [
                        'context' => $request->query->get('context'),
                    ],
                ];
                // needed so the PUT AND PATCH form action also build the $adminType
                $_sylius['vars']['route']['parameters'] = [
                    'id' => $request->attributes->get('id'),
                    'context' => $request->query->get('context'),
                ];
                $request->attributes->set('_sylius', $_sylius);
            }
        }
    }
}
