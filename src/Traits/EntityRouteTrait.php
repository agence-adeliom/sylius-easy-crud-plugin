<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Traits;

use Adeliom\SyliusHappyCMSPlugin\EventListener\EntityRouteIndexer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\TranslationInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route as OrmRoute;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Route;

trait EntityRouteTrait
{
    #[ORM\ManyToMany(targetEntity: OrmRoute::class, cascade: ['persist', 'remove'])]
    protected Collection $routes;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
    }

    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function getOnlineRoute(): ?RouteObjectInterface
    {
        return $this->getRoute(false);
    }

    public function getPreviewRoute(): ?RouteObjectInterface
    {
        return $this->getRoute(true);
    }

    private function getRoute(bool $preview = false): ?RouteObjectInterface
    {
        foreach ($this->routes as $route) {
            /**
             * @var \Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route $route
             */
            if ($preview === $route->getOption(EntityRouteIndexer::OPTION_PREVIEW)) {
                if ($route instanceof RouteObjectInterface) {
                    $route->setContent($this);

                    return $route;
                }
            }
        }

        return null;
    }

    public function setRoutes(Collection $routes): void
    {
        $this->routes = $routes;
    }

    public function addRoute(Route $route): void
    {
        if (!$this->routes->contains($route)) {
            $this->routes->add($route);
        }
    }

    public function removeRoute(Route $route): void
    {
        if ($this->routes->contains($route)) {
            $this->routes->removeElement($route);
        }
    }

    public function getRouteMethods(): array
    {
        return ['GET', 'POST'];
    }

    public function getRouteOptions(TranslationInterface $translation): array
    {
        return [
            'add_locale_pattern' => false,
        ];
    }

    public function getRouteRequirements(TranslationInterface $translation): array
    {
        return [
            '_locale' => $translation->getLocale(),
        ];
    }

    public function getRouteDefaults(TranslationInterface $translation): array
    {
        return [
            '_locale' => $translation->getLocale(),
        ];
    }

    public function getRouteSchemes(TranslationInterface $translation): array
    {
        return ['https'];
    }

    public function getRouteHost(TranslationInterface $translation): ?string
    {
        return null;
    }

    public function getRouteUnikName(): string
    {
        return 'document_' . $this->getId();
    }

    public function getRouteStaticPrefix(TranslationInterface $translation, bool $isPreview): string
    {
        $entity = $translation->getTranslatable();
        $accessor = new PropertyAccessor();
        if ($accessor->isReadable($entity, 'parent')) {
            if ($parent = $accessor->getValue($entity, 'parent')) {
                if ($accessor->isReadable($parent, 'translation')) {
                    // access parent->translation->slug if parent is Translatable
                    $parentTranslation = $parent->getTranslation($translation->getLocale());
                    $parentSlug = $accessor->getValue($parentTranslation, 'slug');
                } elseif ($accessor->isReadable($parent, 'slug')) {
                    // access parent->slug if parent is not Translatable
                    $parentSlug = $accessor->getValue($parent, 'slug');
                }
            }
        }

        $url = '/' . $translation->getLocale();
        if ($parentSlug ?? false) {
            $url .= '/' . $parentSlug;
        }
        $url .= '/' . $translation->getSlug();
        if ($isPreview) {
            $url .= '-preview';
        }

        return $url;
    }

    public function getVariablePattern(TranslationInterface $translation, bool $isPreview): string
    {
        return '';
    }

    public function getRouteController(): ?string
    {
        // To forward route response to a custom controller
        // return 'App\Controller\MyController::fancy';
        return null;
    }

    public function getRouteTemplate(): ?string
    {
        // render '@SyliusHappyCMSPlugin/front/document/default.html.twig' as default template
        // Feel free to change template path
        // return 'App/front/document/fancy.html.twig'
        return null;
    }
}
