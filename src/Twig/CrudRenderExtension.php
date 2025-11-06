<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Twig;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Grid\Parser\OptionsParserInterface;
use Sylius\Component\Grid\Definition\Action;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CrudRenderExtension extends AbstractExtension
{
    public function __construct(
        protected PropertyAccessor $propertyAccessor,
        protected ParameterBagInterface $parameterBag,
        protected Environment $twig,
        protected OptionsParserInterface $optionsParser,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('crud_render_action', [$this, 'renderAction'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('display_flatten_array', [$this, 'flattenArray']),
            new TwigFilter('display_filesize', [$this, 'fileSize']),
            new TwigFilter('display_as_string', [$this, 'representAsString']),
            new TwigFilter('path_info', [$this, 'pathInfo'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderAction(Action $action, RequestConfiguration $requestConfiguration, ?array $data = null): string
    {
        $type = $action->getType();
        $actionTemplates =
            $this->parameterBag->get('sylius.grid.templates.action');

        if (!is_array($actionTemplates) || !isset($actionTemplates[$type])) {
            throw new \InvalidArgumentException(sprintf('Missing template for action type "%s".', $type));
        }

        $options = $this->optionsParser->parseOptions(
            $action->getOptions(),
            $requestConfiguration->getRequest(),
            $data,
        );

        if (
            is_array($requestConfiguration->getRequest()->get('_route_params')) &&
            $requestConfiguration->getRequest()->get('_route_params')['id'] &&
            'index' !== $action->getName() &&
            'new' !== $action->getName()
        ) {
            $data['id'] = $requestConfiguration->getRequest()->get('_route_params')['id'];
        }

        return $this->twig->render($actionTemplates[$type], [
            'action' => $action,
            'data' => $data,
            'options' => $options,
            'grid' => [
                'requestConfiguration' => $requestConfiguration,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function flattenArray(array $data, ?string $parentKey = null): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $key = null !== $parentKey ? sprintf('%s[%s]', $parentKey, $key) : $key;
            if (\is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $key));
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function fileSize(int $bytes): string
    {
        $size = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        $factor = (int) floor(log($bytes) / log(1024));

        return (int) ($bytes / (1024 ** $factor)) . @$size[$factor];
    }

    /**
     * @param string|int|bool|array<mixed, mixed>|object|null $value
     */
    public function representAsString(null|string|int|bool|array|object $value): string
    {
        if (null === $value) {
            return '';
        }

        if (\is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_array($value)) {
            return sprintf('Array (%d items)', \count($value));
        }

        if (\is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            if (method_exists($value, 'getId')) {
                return sprintf('%s #%s', $value::class, $value->getId());
            }

            return sprintf('%s #%s', $value::class, substr(md5(spl_object_hash($value)), 0, 7));
        }
    }

    /**
     * @return array<string, string>|string
     */
    public function pathInfo(string $path): array|string
    {
        return pathinfo($path);
    }
}
