<?php

namespace Adeliom\SyliusEasyCrudPlugin\Twig;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Grid\Parser\OptionsParserInterface;
use Sylius\Component\Grid\Definition\Action;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\RuntimeExtensionInterface;
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

    public function getFilters(): array
    {
        return [
            new TwigFilter('display_flatten_array', [$this, 'flattenArray']),
            new TwigFilter('display_filesize', [$this, 'fileSize']),
            new TwigFilter('as_apply_filter_if_exists', [$this, 'applyFilterIfExists'], ['needs_environment' => true]),
            new TwigFilter('display_as_string', [$this, 'representAsString']),
            new TwigFilter('path_info', [$this, 'pathInfo'], ['is_safe' => ['html']]),
        ];
    }


    public function renderAction(Action $action, RequestConfiguration $requestConfiguration, $data = null): mixed
    {
        $type = $action->getType();
        $actionTemplates =
            $this->parameterBag->get('sylius.grid.templates.action');
        if (!isset($actionTemplates[$type])) {
            throw new \InvalidArgumentException(sprintf('Missing template for action type "%s".', $type));
        }

        $options = $this->optionsParser->parseOptions(
            $action->getOptions(),
            $requestConfiguration->getRequest(),
            $data,
        );

        if (
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
                'requestConfiguration' => $requestConfiguration
            ]
        ]);
    }

    public function flattenArray($array, $parentKey = null): array
    {
        $flattenedArray = [];

        foreach ($array as $flattenedKey => $value) {
            $flattenedKey = null !== $parentKey ? sprintf('%s[%s]', $parentKey, $flattenedKey) : $flattenedKey;

            if (\is_array($value)) {
                $flattenedArray = array_merge($flattenedArray, $this->flattenArray($value, $flattenedKey));
            } else {
                $flattenedArray[$flattenedKey] = $value;
            }
        }

        return $flattenedArray;
    }

    public function fileSize(int $bytes): string
    {
        $size = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
        $factor = (int) floor(log($bytes) / log(1024));

        return (int) ($bytes / (1024 ** $factor)).@$size[$factor];
    }

    // Code adapted from https://stackoverflow.com/a/48606773/2804294 (License: CC BY-SA 3.0)
    public function applyFilterIfExists(Environment $environment, $value, string $filterName, ...$filterArguments)
    {
        if (null === $filter = $environment->getFilter($filterName)) {
            return $value;
        }

        [$class, $method] = $filter->getCallable();
        if ($class instanceof ExtensionInterface) {
            return $filter->getCallable()($value, ...$filterArguments);
        }

        $object = $environment->getRuntime($class);
        if ($object instanceof RuntimeExtensionInterface && method_exists($object, $method)) {
            return $object->$method($value, ...$filterArguments);
        }

        return null;
    }

    public function representAsString($value): string
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

        return '';
    }

    public function callFunctionIfExists(Environment $environment, string $functionName, ...$functionArguments)
    {
        if (null === $function = $environment->getFunction($functionName)) {
            return '';
        }

        return $function->getCallable()(...$functionArguments);
    }

    public function pathInfo(string $path): array
    {
        return pathinfo($path);
    }
}
