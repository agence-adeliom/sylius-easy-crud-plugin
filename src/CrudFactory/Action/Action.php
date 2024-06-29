<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\ActionDto;
use Sylius\Bundle\GridBundle\Builder\Action\ActionInterface;
use function Symfony\Component\String\u;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
class Action
{
    public const BATCH_DELETE = 'batchDelete';

    public const DELETE = 'delete';

    public const DETAIL = 'detail';

    public const EDIT = 'edit';

    public const INDEX = 'index';

    public const NEW = 'new';

    // these are the actions applied to a specific entity instance
    public const TYPE_ITEM = 'item';

    public const TYPE_SUB_ITEM = 'subitem';

    // these are the actions that are not associated to an entity
    // (they are available only in the INDEX page)
    public const TYPE_GLOBAL = 'main';

    // these are actions that can be applied to one or more entities at the same time
    public const TYPE_BATCH = 'bulk';

    private ActionDto $dto;

    private function __construct(ActionDto $actionDto)
    {
        $this->dto = $actionDto;
    }

    public function __toString()
    {
        return $this->dto->getName();
    }

    public static function new(string $name, ?string $label = null, ?string $icon = null): self
    {
        $dto = new ActionDto();
        $dto->setType(self::TYPE_ITEM);
        $dto->setName($name);
        $dto->setLabel($label ?? self::humanizeString($name));
        $dto->setIcon($icon);
        $dto->setHtmlElement('a');
        $dto->setHtmlAttributes([]);
        $dto->setTranslationParameters([]);

        return new self($dto);
    }

    public function createAsGlobalAction(): self
    {
        $this->dto->setType(self::TYPE_GLOBAL);

        return $this;
    }

    public function createAsBatchAction(): self
    {
        $this->dto->setType(self::TYPE_BATCH);

        return $this;
    }

    public function setLabel(?string $label): self
    {
        $this->dto->setLabel($label ?? self::humanizeString($this->dto->getName()));

        return $this;
    }

    public function setIcon(?string $icon): self
    {
        $this->dto->setIcon($icon);

        return $this;
    }

    /**
     * If you set your own CSS classes, the default CSS classes are not applied.
     * You may want to also add the 'btn' (and 'btn-primary', etc.) classes to make
     * your action look like a button.
     */
    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($cssClass);

        return $this;
    }

    /**
     * If you add a custom CSS class, the default CSS classes are not applied.
     * You may want to also add the 'btn' (and 'btn-primary', etc.) classes to make
     * your action look like a button.
     */
    public function addCssClass(string $cssClass): self
    {
        $this->dto->setCssClass(trim($this->dto->getCssClass() . ' ' . $cssClass));

        return $this;
    }

    public function displayAsLink(): self
    {
        $this->dto->setHtmlElement('a');

        return $this;
    }

    public function displayAsButton(): self
    {
        $this->dto->setHtmlElement('button');

        return $this;
    }

    /**
     * @param string[] $attributes
     */
    public function setHtmlAttributes(array $attributes): self
    {
        $this->dto->setHtmlAttributes($attributes);

        return $this;
    }

    public function setTemplatePath(string $templatePath): self
    {
        $this->dto->setTemplatePath($templatePath);

        return $this;
    }

    /**
     * @param array<string, mixed>|null $methodContext
     */
    public function linkToControllerMethod(
        string $methodName,
        ?array $methodContext = null,
    ): self {
        $this->dto->setControllerMethodName($methodName);
        $this->dto->setControllerMethodContext($methodContext);

        return $this;
    }

    /**
     * @param array<string>|callable $routeParameters The callable has the signature: function ($entity): array
     *
     * Route parameters can be defined as a callable with the signature: function ($entityInstance): array
     * Example: ->linkToRoute('invoice_send', fn (Invoice $entity) => ['uuid' => $entity->getId()]);
     */
    public function linkToRoute(string $routeName, array|callable $routeParameters = []): self
    {
        $this->dto->setRouteName($routeName);
        $this->dto->setRouteParameters($routeParameters);

        return $this;
    }

    public function linkToUrl(string|callable $url): self
    {
        $this->dto->setUrl($url);

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setTranslationParameters(array $parameters): self
    {
        $this->dto->setTranslationParameters($parameters);

        return $this;
    }

    public function displayIf(callable $callable): self
    {
        $this->dto->setDisplayCallable($callable);

        return $this;
    }

    public function getAsDto(): ActionDto
    {
        return $this->dto;
    }

    private static function humanizeString(string $string): string
    {
        $uString = u($string);
        $upperString = $uString->upper()->toString();

        // this prevents humanizing all-uppercase labels (e.g. 'UUID' -> 'U u i d')
        // and other special labels which look better in uppercase
        if ($uString->toString() === $upperString) {
            return $upperString;
        }

        return $uString
            ->replaceMatches('/([A-Z])/', '_$1')
            ->replaceMatches('/[_\s]+/', ' ')
            ->trim()
            ->lower()
            ->title(true)
            ->toString();
    }

    public function addSubAction(self $action): static
    {
        $this->dto->addSubAction($action);

        return $this;
    }

    public function setSyliusAction(ActionInterface $action): self
    {
        $this->dto->setSyliusAction($action);

        return $this;
    }
}
