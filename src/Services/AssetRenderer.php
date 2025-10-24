<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\WebpackEncoreBundle\Event\RenderAssetTagEvent;

class AssetRenderer
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private TagRenderer $tagRenderer,
        private Packages $packages,
    ) {
    }

    /**
     * @param array{js: array<string|Asset>|null, css: array<string|Asset>|null, webpack: array<string|Asset>|null} $assets
     */
    public function renderAssets(array $assets, ?string $nonce = null): string
    {
        $html = '';

        if (!empty($assets['css'])) {
            foreach ($assets['css'] as $asset) {
                if (null === $asset) {
                    continue;
                }
                $attributes = [
                    'nonce' => $nonce,
                ];
                $attributes['rel'] = 'stylesheet';
                if (is_string($asset)) {
                    $attributes['href'] = $asset;
                } elseif ($asset instanceof Asset) {
                    $attributes['href'] = $asset->getAsDto()->getValue();

                    if ($asset->getAsDto()->getPackageName()) {
                        $attributes['href'] = $this->packages->getPackage($asset->getAsDto()->getPackageName())->getUrl($attributes['href']);
                    }
                }

                if (isset($attributes['href'])) {
                    $event = new RenderAssetTagEvent(
                        RenderAssetTagEvent::TYPE_LINK,
                        $attributes['href'],
                        $attributes,
                    );
                    if (null !== $this->eventDispatcher) {
                        $event = $this->eventDispatcher->dispatch($event);
                    }
                    $attributes = $event->getAttributes();

                    $html .= sprintf(
                        '<link %s>',
                        $this->convertArrayToAttributes($attributes),
                    );
                }
            }
        }

        if (!empty($assets['js'])) {
            foreach ($assets['js'] as $asset) {
                if (null === $asset) {
                    continue;
                }
                $attributes = [
                    'nonce' => $nonce,
                ];
                if (is_string($asset)) {
                    $attributes['src'] = $asset;
                } elseif ($asset instanceof Asset) {
                    $attributes['src'] = $asset->getAsDto()->getValue();
                }

                if ($asset->getAsDto()->getPackageName()) {
                    $attributes['src'] = $this->packages->getPackage($asset->getAsDto()->getPackageName())->getUrl($attributes['src']);
                }

                $event = new RenderAssetTagEvent(
                    RenderAssetTagEvent::TYPE_SCRIPT,
                    $attributes['src'],
                    $attributes,
                );
                if (null !== $this->eventDispatcher) {
                    $event = $this->eventDispatcher->dispatch($event);
                }
                $attributes = $event->getAttributes();

                $html .= sprintf(
                    '<script %s></script>',
                    $this->convertArrayToAttributes($attributes),
                );
            }
        }

        if (!empty($assets['webpack'])) {
            foreach ($assets['webpack'] as $webpackAsset) {
                if (null === $webpackAsset) {
                    continue;
                }

                try {
                    if ($webpackAsset instanceof Asset) {
                        $html .= $this->tagRenderer
                            ->renderWebpackScriptTags(
                                $webpackAsset->getAsDto()->getValue(),
                                $webpackAsset->getAsDto()->getPackageName(),
                                $webpackAsset->getAsDto()->getWebpackEntrypointName(),
                                [
                                    'nonce' => $nonce,
                                ],
                                true,
                            );
                        $html .= $this->tagRenderer
                            ->renderWebpackLinkTags(
                                $webpackAsset->getAsDto()->getValue(),
                                $webpackAsset->getAsDto()->getPackageName(),
                                $webpackAsset->getAsDto()->getWebpackEntrypointName(),
                                [
                                    'nonce' => $nonce,
                                ],
                                true,
                            );

                        continue;
                    }
                    if (is_string($webpackAsset)) {
                        $html .= $this->tagRenderer
                            ->renderWebpackScriptTags(
                                $webpackAsset,
                                null,
                                null,
                                [
                                    'nonce' => $nonce,
                                ],
                                true,
                            );
                        $html .= $this->tagRenderer
                            ->renderWebpackLinkTags(
                                $webpackAsset,
                                null,
                                null,
                                [
                                    'nonce' => $nonce,
                                ],
                                true,
                            );
                    }
                } catch (\Exception $exception) {
                    $html .= '';
                }
            }
        }

        return $html;
    }

    private function convertArrayToAttributes(array $attributesMap): string
    {
        // remove attributes set specifically to false
        $attributesMap = array_filter($attributesMap, static function ($value) {
            return false !== $value;
        });

        return implode(' ', array_map(
            static function ($key, $value) {
                // allows for things like defer: true to only render "defer"
                if (true === $value || null === $value) {
                    return $key;
                }

                return sprintf('%s="%s"', $key, htmlentities($value));
            },
            array_keys($attributesMap),
            $attributesMap,
        ));
    }
}
