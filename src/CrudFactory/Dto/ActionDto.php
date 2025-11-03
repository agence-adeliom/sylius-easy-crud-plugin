<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Sylius\Bundle\GridBundle\Builder\Action\Action as SyliusAction;
use Sylius\Bundle\GridBundle\Builder\Action\ActionInterface;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
class ActionDto
{
    private ?string $type = null;

    private ?string $name = null;

    private string|null $label = null;

    private ?string $icon = null;

    private string $cssClass = '';

    private ?string $htmlElement = null;

    /** @var string[] */
    private array $htmlAttributes = [];

    private ?string $linkUrl = null;

    private ?string $templatePath = null;

    private ?string $controllerMethodName = null;

    /** @var array<string, mixed>|null */
    private ?array $controllerMethodContext = null;

    private ?string $routeName = null;

    /** @var array<string>|callable */
    private $routeParameters = [];

    private mixed $url = null;

    /** @var array<string, mixed> */
    private array $translationParameters = [];

    /** @var callable|null */
    private $displayCallable;

    /** @var array<Action> */
    private array $subActions = [];

    private ?ActionInterface $syliusAction = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function hasType(string $type): bool
    {
        return $this->type === $type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isItemAction(): bool
    {
        return Action::TYPE_ITEM === $this->type;
    }

    public function isGlobalAction(): bool
    {
        return Action::TYPE_GLOBAL === $this->type;
    }

    public function isBatchAction(): bool
    {
        return Action::TYPE_BATCH === $this->type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): string
    {
        if (is_string($this->label)) {
            return $this->label;
        }

        return '';
    }

    public function setLabel(string|null $label): void
    {
        $this->label = $label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = $cssClass;
    }

    public function getHtmlElement(): ?string
    {
        return $this->htmlElement;
    }

    public function setHtmlElement(string $htmlElement): void
    {
        $this->htmlElement = $htmlElement;
    }

    /**
     * @return string[]
     */
    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    /**
     * @param string[] $htmlAttributes
     */
    public function addHtmlAttributes(array $htmlAttributes): void
    {
        $this->htmlAttributes = array_merge($this->htmlAttributes, $htmlAttributes);
    }

    /**
     * @param string[] $htmlAttributes
     */
    public function setHtmlAttributes(array $htmlAttributes): void
    {
        $this->htmlAttributes = $htmlAttributes;
    }

    public function setHtmlAttribute(string $attributeName, string $attributeValue): void
    {
        $this->htmlAttributes[$attributeName] = $attributeValue;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function setTemplatePath(string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(string $linkUrl): void
    {
        $this->linkUrl = $linkUrl;
    }

    public function getControllerMethodName(): ?string
    {
        return $this->controllerMethodName;
    }

    public function setControllerMethodName(string $controllerMethodName): void
    {
        $this->controllerMethodName = $controllerMethodName;
    }

    /**
     * @return array<string, mixed>
     */
    public function getControllerMethodContext(): array
    {
        return $this->controllerMethodContext ?? [];
    }

    /**
     * @param array<string, mixed>|null $controllerMethodContext
     */
    public function setControllerMethodContext(?array $controllerMethodContext = null): void
    {
        $this->controllerMethodContext = $controllerMethodContext;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): void
    {
        $this->routeName = $routeName;
    }

    /**
     * @return array<string>|callable
     */
    public function getRouteParameters(): array|callable
    {
        return $this->routeParameters;
    }

    /**
     * @param array<string>|callable $routeParameters
     */
    public function setRouteParameters(array|callable $routeParameters): void
    {
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return string|callable|null
     */
    public function getUrl(): mixed
    {
        return $this->url;
    }

    public function setUrl(string|callable $url): void
    {
        $this->url = $url;
    }

    /**
     * @return array<string, mixed> $translationParameters
     */
    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    /**
     * @param array<string, mixed> $translationParameters
     */
    public function setTranslationParameters(array $translationParameters): void
    {
        $this->translationParameters = $translationParameters;
    }

    public function shouldBeDisplayedFor(EntityDto $entityDto): bool
    {
        if (null === $entityDto->getInstance()) {
            return false;
        }

        return null === $this->displayCallable || (bool) \call_user_func($this->displayCallable, $entityDto->getInstance());
    }

    public function setDisplayCallable(callable $displayCallable): void
    {
        $this->displayCallable = $displayCallable;
    }

    /**
     * @internal
     */
    public function getAsConfigObject(): Action
    {
        $action = Action::new($this->name, $this->label, $this->icon);
        $this->setCssClass($this->cssClass);
        $this->setHtmlAttributes($this->htmlAttributes);
        $this->setTranslationParameters($this->translationParameters);

        if (null !== $this->templatePath) {
            $this->setTemplatePath($this->templatePath);
        }

        if ($this->isGlobalAction()) {
            $action->createAsGlobalAction();
        } elseif ($this->isBatchAction()) {
            $action->createAsBatchAction();
        }

        if ('a' === $this->htmlElement) {
            $action->displayAsLink();
        } else {
            $action->displayAsButton();
        }

        if (null !== $this->controllerMethodName) {
            $this->setControllerMethodName($this->controllerMethodName);
        }

        if (null !== $this->controllerMethodContext && [] !== $this->controllerMethodContext) {
            $this->setControllerMethodContext($this->getControllerMethodContext());
        }

        if (null !== $this->routeName) {
            $action->linkToRoute($this->routeName, $this->routeParameters);
        }

        if (null !== $this->displayCallable) {
            $action->displayIf($this->displayCallable);
        }

        return $action;
    }

    /**
     * @return array<Action>
     */
    public function getSubActions(): array
    {
        return $this->subActions;
    }

    /**
     * @param array<Action> $subActions
     */
    public function setSubActions(array $subActions): void
    {
        $this->subActions = $subActions;
    }

    public function addSubAction(Action $action): void
    {
        $this->subActions[] = $action;
    }

    public function setSyliusAction(ActionInterface $action): void
    {
        $this->syliusAction = $action;
    }

    public function getSyliusAction(): ?ActionInterface
    {
        return $this->syliusAction;
    }

    public function convertToGridAction(): ?ActionInterface
    {
        $actionRoute = function (self $actionDto): array {
            if (null !== $actionDto->getUrl() && '' !== $actionDto->getUrl()) {
                $route = [
                    'url' => $actionDto->getUrl(),
                ];
            } elseif (null !== $actionDto->getControllerMethodName()) {
                $route = [
                    'route' => $actionDto->getRouteName(),
                    'parameters' => array_merge([
                        'context' => 'ca:' . $actionDto->getControllerMethodName(),
                    ], $actionDto->getControllerMethodContext()),
                ];
            } else {
                $route = [
                    'route' => $actionDto->getRouteName(),
                    'parameters' => $actionDto->getRouteParameters(),
                ];
            }

            return $route;
        };

        $actionOptions = [];
        if (null !== $this->getSyliusAction()) {
            return $this->getSyliusAction();
        }
        if ([] !== $this->getSubActions()) {
            $subItemWrapper = SyliusAction::create(
                $this->getName(),
                'easy_crud_' . $this->getType() . '_sub_action',
            )->setLabel($this->getLabel());

            $subItems = [];
            foreach ($this->getSubActions() as $subAction) {
                $subActionDto = $subAction->getAsDto();
                $route = $actionRoute($subActionDto);
                $subItems[] = array_merge(
                    [
                        'label' => $subActionDto->getLabel(),
                        'icon' => $subActionDto->getIcon(),
                        'htmlAttributes' => $subActionDto->getHtmlAttributes(),
                    ],
                    $route,
                );
            }

            $subItemWrapper->setOptions(
                [
                    'icon' => $this->getIcon(),
                    'links' => [
                        ...$subItems,
                    ],
                ],
            );

            return $subItemWrapper;
        }
        $route = $actionRoute($this);

        if ($this->getCssClass()) {
            $actionOptions['class'] = $this->getCssClass();
        }
        if ($this->getHtmlAttributes()) {
            $actionOptions['attr'] = $this->getHtmlAttributes();
        }

        $actionOptions = array_merge(
            ['link' => $route],
            $actionOptions,
        );

        return SyliusAction::create($this->getName(), 'easy_crud_' . $this->getType() . '_action')
            ->setLabel($this->getLabel())
            ->setIcon($this->getIcon())
            ->setEnabled(true)
            ->setOptions($actionOptions);
    }
}
