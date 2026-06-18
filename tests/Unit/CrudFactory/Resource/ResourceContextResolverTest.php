<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\CrudFactory\Resource;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Resource\ResourceContextResolver;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Controller\ParametersParserInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ResourceContextResolverTest extends TestCase
{
    public function testItReturnsNullWithoutSyliusResourcesParameter(): void
    {
        $resolver = new ResourceContextResolver(
            new ParameterBag(),
            $this->requestStackWithCurrentRequest(),
            $this->requestConfigurationFactory(),
        );

        $this->assertNull($resolver->resolve(\stdClass::class));
    }

    public function testItReturnsNullWithoutCurrentRequest(): void
    {
        $resolver = new ResourceContextResolver(
            new ParameterBag([
                'sylius.resources' => [
                    'app.book' => [
                        'driver' => 'doctrine/orm',
                        'classes' => [
                            'model' => \stdClass::class,
                        ],
                    ],
                ],
            ]),
            new RequestStack(),
            $this->requestConfigurationFactory(),
        );

        $this->assertNull($resolver->resolve(\stdClass::class));
    }

    public function testItResolvesResourceContextForMatchingModel(): void
    {
        $resolver = new ResourceContextResolver(
            new ParameterBag([
                'sylius.resources' => [
                    'app.author' => [
                        'driver' => 'doctrine/orm',
                        'classes' => [
                            'model' => self::class,
                        ],
                    ],
                    'app.book' => [
                        'driver' => 'doctrine/orm',
                        'classes' => [
                            'model' => \stdClass::class,
                        ],
                    ],
                ],
            ]),
            $this->requestStackWithCurrentRequest(),
            $this->requestConfigurationFactory(),
        );

        $context = $resolver->resolve(\stdClass::class);

        self::assertNotNull($context);
        $this->assertSame('app.book', $context->getAlias());
        $this->assertSame('app.book', $context->getMetadata()->getAlias());
        $this->assertInstanceOf(RequestConfiguration::class, $context->getRequestConfiguration());
    }

    public function testItReturnsNullWhenNoModelMatches(): void
    {
        $resolver = new ResourceContextResolver(
            new ParameterBag([
                'sylius.resources' => [
                    'app.author' => [
                        'driver' => 'doctrine/orm',
                        'classes' => [
                            'model' => self::class,
                        ],
                    ],
                ],
            ]),
            $this->requestStackWithCurrentRequest(),
            $this->requestConfigurationFactory(),
        );

        $this->assertNull($resolver->resolve(\stdClass::class));
    }

    private function requestStackWithCurrentRequest(): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        return $requestStack;
    }

    private function requestConfigurationFactory(): RequestConfigurationFactory
    {
        $parametersParser = $this->createMock(ParametersParserInterface::class);
        $parametersParser
            ->method('parseRequestValues')
            ->willReturnArgument(0);

        return new RequestConfigurationFactory(
            $parametersParser,
            RequestConfiguration::class,
        );
    }
}
