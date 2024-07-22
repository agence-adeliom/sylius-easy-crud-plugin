<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class CrudMakerService
{
    public const YAML_ROUTES_FILE = 'config/routes.yaml';

    public const YAML_RESOURCE_FILE = 'config/packages/sylius_resource.yaml';

    public const YAML_SERVICES_FILE = 'config/services.yaml';

    /** @var array<string, mixed> */
    protected array $namespaces;

    /**
     * @param \ReflectionClass<ResourceInterface>|null $entity
     * @param \ReflectionClass<RepositoryInterface<ResourceInterface>>|null $repository
     * @param \ReflectionClass<ResourceInterface>|null $entityTranslation
     */
    public function __construct(
        protected string $projectDir,
        protected Generator $generator,
        protected string $namespace,
        protected ?\ReflectionClass $entity,
        protected ?\ReflectionClass $repository,
        protected ?\ReflectionClass $entityTranslation,
    ) {
        $this->namespaces = [
            'entity' => $entity,
        ];
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws \Exception
     */
    public function generateAdmin(
        ?string $suffix = null,
        ?string $className = null,
        ?string $templatePath = null,
        ?array $variables = [],
    ): ClassNameDetails {
        return $this->generateFileFromTpm('Admin', $suffix, $className, $templatePath, $variables);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws \Exception
     */
    public function generateController(
        ?string $suffix = null,
        ?string $className = null,
        ?string $templatePath = null,
        ?array $variables = [],
    ): ClassNameDetails {
        return $this->generateFileFromTpm('Controller', $suffix, $className, $templatePath, $variables);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws \Exception
     */
    public function generateEntity(string $className, ?string $template = null, ?array $variables = []): void
    {
        $this->generator->generateClass(
            $className,
            (is_string($template) && file_exists($template)) ? $template : __DIR__ . '/../Resources/skeleton/Entity.tpl.php',
            $variables,
        );
        $this->generator->writeChanges();
        $this->namespaces['entity'] = $className;
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws \Exception
     */
    public function generateEntityTranslation(string $className, ?string $template = null, ?array $variables = []): void
    {
        $this->generator->generateClass(
            $className . 'Translation',
            (is_string($template) && file_exists($template)) ? $template : __DIR__ . '/../Resources/skeleton/Translation.tpl.php',
            $variables,
        );
        $this->generator->writeChanges();
        $this->namespaces['entityTranslation'] = $className . 'Translation';
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws \Exception
     */
    public function generateRepository(string $className, ?string $template = null, ?array $variables = []): void
    {
        $this->generator->generateClass(
            str_replace('Entity', 'Repository', $className) . 'Repository',
            (is_string($template) && file_exists($template)) ? $template : __DIR__ . '/../Resources/skeleton/Repository.tpl.php',
            array_merge([
                            'entity_name' => Str::getShortClassName($className),
                        ], $variables),
        );
        $this->generator->writeChanges();
    }

    /**
     * @throws \Exception
     */
    public function generateMenuListener(string $className): void
    {
        if (!\class_exists('App\Menu\AdminMenuListener')) {
            $this->generator->generateClass(
                'App\Menu\AdminMenuListener',
                __DIR__ . '/../Resources/skeleton/AdminMenuListener.tpl.php',
                [
                    'route' => $this->namespace . '_admin_' . mb_strtolower(Str::asSnakeCase($className)),
                ],
            );
            $this->generator->writeChanges();

            $yaml = [];
            $yaml['app.listener.admin.menu_builder'] = [
                'class' => 'App\Menu\AdminMenuListener',
                'tags' => [
                    0 => [
                        'name' => 'kernel.event_listener',
                        'event' => 'sylius.menu.admin.main',
                        'method' => 'addAdminMenuItems',
                    ],
                ],
            ];
            $content = Yaml::dump($yaml, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            file_put_contents(
                self::YAML_SERVICES_FILE,
                '    ' . str_replace("\n", "\n    ", $content),
                \FILE_APPEND,
            );
        }
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws \Exception
     */
    protected function generateFileFromTpm(
        string $templateName,
        ?string $suffix = null,
        ?string $className = null,
        ?string $templatePath = null,
        ?array $variables = [],
    ): ClassNameDetails {
        if (null === $suffix) {
            $suffix = $templateName;
        }
        $file = $this->generator->createClassNameDetails(
            $className ?? $this->entity->getShortName(),
            $templateName,
            $suffix,
        );
        $this->generator->generateClass(
            $file->getFullName(),
            (is_string($templatePath) && file_exists($templatePath)) ? $templatePath : __DIR__ . '/../Resources/skeleton/' . $templateName . '.tpl.php',
            array_merge([
                            'entity' => $this->entity,
                            'repository' => $this->repository,
                        ], $variables),
        );
        $this->generator->writeChanges();
        $this->namespaces[strtolower($templateName)] = $file->getFullName();

        return $file;
    }

    public function generateRoute(): string
    {
        try {
            $yaml = [];
            $entityName = $this->entity->getName();
            $this->appendRoutingConfig($yaml, $entityName);
            file_put_contents(
                self::YAML_ROUTES_FILE,
                Yaml::dump($yaml, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK),
                \FILE_APPEND,
            );

            return self::YAML_ROUTES_FILE;
        } catch (\Exception $e) {
            return $e->getCode() . ' : ' . $e->getMessage();
        }
    }

    public function generateResource(): string
    {
        try {
            $filePath = $this->projectDir . '/' . self::YAML_RESOURCE_FILE;
            $filesystem = new Filesystem();
            if (!$filesystem->exists($filePath)) {
                $filesystem->touch($filePath);
                $filesystem->appendToFile($filePath, "sylius_resource:\n  resources:");
            }

            $entityName = $this->entity->getName();
            $yamlGenerator = new YamlSourceManipulator(file_get_contents($filePath) ?: '');
            $yaml = $yamlGenerator->getData();
            $this->appendResourceConfig($yaml, $entityName);
            $yamlGenerator->setData($yaml);
            file_put_contents($filePath, $yamlGenerator->getContents());

            return self::YAML_RESOURCE_FILE;
        } catch (\Exception $e) {
            return $e->getCode() . ' : ' . $e->getMessage();
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function appendRoutingConfig(array &$data, string $entityName, ?string $translationPrefix = null, ?string $icon = null): void
    {
        if (null === $translationPrefix) {
            $translationPrefix = 'sylius_easy_crud_plugin';
        }
        if (null === $icon) {
            $icon = 'file';
        }
        $resourceDataBlock =
            'alias: ' . mb_strtolower($this->namespace . '.' . Str::asSnakeCase($entityName)) . "\n"
            . "section: admin\n"
            . "templates: \"@SyliusEasyCrudPlugin\\\\crud\"\n"
            . "redirect: update\n"
            . 'grid: admin_' . mb_strtolower(Str::asSnakeCase($entityName)) . "\n"
            . "form:\n"
            . '    type: ' . ($this->namespaces['admin'] ?? 'NoAdmin') . "\n"
            . "    options:\n"
            . "        context: \$context\n"
            . "vars:\n"
            . "    all:\n"
            . "        icon: '" . $icon . "'\n"
            . '        subheader: ' . $translationPrefix . ".admin.ui.default.subheader\n"
            . '        breadcrumb: ' . $translationPrefix . ".admin.ui.default.index\n"
            . "        templates:\n"
            . "            form: \"@SyliusEasyCrudPlugin\\\\crud\\\\form\\\\_form.html.twig\"\n"
            . "    index:\n"
            . '        header: ' . $translationPrefix . ".admin.ui.default.index\n"
            . "    create:\n"
            . '        header: ' . $translationPrefix . ".admin.ui.default.create\n"
            . "    update:\n"
            . '        header: ' . $translationPrefix . ".admin.ui.default.update\n"
            . "        redirect:\n"
            . "            route: update\n"
            . "            parameters:\n"
            . "                context: \$context\n"
            . "                id: \$id\n"
            . "        route:\n"
            . "            parameters:\n"
            . "                context: \$context\n"
            . "                id: \$id\n";

        $data['admin_' . mb_strtolower(Str::asSnakeCase($entityName))] =
            [
                'resource' => $resourceDataBlock,
                'type' => 'sylius.resource',
                'prefix' => 'admin',
            ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function appendResourceConfig(array &$data, string $entityName): void
    {
        $alias = mb_strtolower($this->namespace . '.' . Str::asSnakeCase($entityName));
        if (!isset($data['sylius_resource'])) {
            $data['sylius_resource'] = [];
        }
        if (!isset($data['sylius_resource']['resources'])) {
            $data['sylius_resource']['resources'] = [];
        }
        $data['sylius_resource']['resources'][] = YamlSourceManipulator::EMPTY_LINE_PLACEHOLDER_VALUE;
        $data['sylius_resource']['resources'][$alias] =
            [
                'driver' => 'doctrine/orm',
                'classes' => [
                    'model' => $entityName,
                    'controller' => 'Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController',
                    'form' => $this->namespaces['admin'],
                ],
            ];
        if (isset($this->namespaces['admin'])) {
            $data['sylius_resource']['resources'][$alias]['classes']['form'] = $this->namespaces['admin'];
        }
        if (isset($this->namespaces['controller'])) {
            $data['sylius_resource']['resources'][$alias]['classes']['controller'] = $this->namespaces['controller'];
        }
        if (null !== $this->repository) {
            $data['sylius_resource']['resources'][$alias]['classes']['repository'] = $this->repository->getName();
        }
        if (null !== $this->entityTranslation) {
            $data['sylius_resource']['resources'][$alias]['translation'] = [
                'classes' => [
                    'model' => $this->entityTranslation->getName(),
                    'controller' => $this->namespaces['controller'] ?? 'Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController',
                    'form' => $this->namespaces['admin'],
                ],
            ];
        }
    }

    /**
     * @return array<int, mixed>
     */
    public static function getEntity(string $class, Generator $generator, ManagerRegistry $managerRegistry): array
    {
        $entity = null;
        $entityTranslation = null;
        $repository = null;
        /**
         * @var class-string<object> $classTranslation
         */
        $classTranslation = $class . 'Translation';
        if (\class_exists($class)) {
            $entity = new \ReflectionClass($class);

            $repository = new \ReflectionClass($managerRegistry->getRepository($entity->getName()));
            if (0 !== \mb_strpos($repository->getName(), $generator->getRootNamespace())) {
                // not using a custom repository
            }

            if (\class_exists($classTranslation)) {
                $entityTranslation = new \ReflectionClass($classTranslation);
            }
        }

        return [$entity, $entityTranslation, $repository];
    }
}
