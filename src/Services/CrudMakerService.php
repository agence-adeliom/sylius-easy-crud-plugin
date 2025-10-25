<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Model\ResourceInterface;
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
     * @param \ReflectionClass<RepositoryInterface>|null $repository
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
        // Extract namespace parts from full class name
        // For "App\Entity\Post", we get: entity_namespace = "App\Entity", repository_namespace = "App\Repository"
        $namespaceParts = explode('\\', $className);
        $shortClassName = array_pop($namespaceParts); // Remove class name
        $entityNamespace = implode('\\', $namespaceParts); // App\Entity

        // Calculate repository namespace by replacing "Entity" with "Repository"
        $repositoryNamespaceParts = $namespaceParts;
        $lastPart = array_pop($repositoryNamespaceParts);
        if ($lastPart === 'Entity') {
            $repositoryNamespaceParts[] = 'Repository';
        } else {
            $repositoryNamespaceParts[] = $lastPart;
            $repositoryNamespaceParts[] = 'Repository';
        }
        $repositoryNamespace = implode('\\', $repositoryNamespaceParts);

        $this->generator->generateClass(
            $className,
            (is_string($template) && file_exists($template)) ? $template : __DIR__ . '/../Resources/skeleton/Entity.tpl.php',
            array_merge([
                'entity_namespace' => $entityNamespace,
                'repository_namespace' => $repositoryNamespace,
            ], $variables),
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
        // Extract namespace from full class name
        $namespaceParts = explode('\\', $className);
        array_pop($namespaceParts); // Remove class name
        $entityNamespace = implode('\\', $namespaceParts);

        $this->generator->generateClass(
            $className . 'Translation',
            (is_string($template) && file_exists($template)) ? $template : __DIR__ . '/../Resources/skeleton/Translation.tpl.php',
            array_merge([
                'entity_namespace' => $entityNamespace,
            ], $variables),
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
        // Extract namespace parts
        $namespaceParts = explode('\\', $className);
        $shortClassName = array_pop($namespaceParts); // Remove class name
        $entityNamespace = implode('\\', $namespaceParts);

        // Calculate repository namespace
        $repositoryNamespaceParts = $namespaceParts;
        $lastPart = array_pop($repositoryNamespaceParts);
        if ($lastPart === 'Entity') {
            $repositoryNamespaceParts[] = 'Repository';
        } else {
            $repositoryNamespaceParts[] = $lastPart;
            $repositoryNamespaceParts[] = 'Repository';
        }
        $repositoryNamespace = implode('\\', $repositoryNamespaceParts);

        $this->generator->generateClass(
            str_replace('Entity', 'Repository', $className) . 'Repository',
            (is_string($template) && file_exists($template)) ? $template : __DIR__ . '/../Resources/skeleton/Repository.tpl.php',
            array_merge([
                'entity_name' => Str::getShortClassName($className),
                'entity_namespace' => $entityNamespace,
                'repository_namespace' => $repositoryNamespace,
            ], $variables),
        );
        $this->generator->writeChanges();
    }

    /**
     * @throws \Exception
     */
    public function generateMenuListener(string $className): void
    {
        // Extract namespace from entity class name
        $namespaceParts = explode('\\', $className);
        array_pop($namespaceParts); // Remove class name (Post)

        // Remove "Entity" if it's the last part
        $lastPart = array_pop($namespaceParts);
        if ($lastPart !== 'Entity') {
            // If it's not "Entity", put it back
            $namespaceParts[] = $lastPart;
        }

        // Now add "Menu"
        $baseNamespace = implode('\\', $namespaceParts);
        $menuNamespace = $baseNamespace . '\\Menu';
        $menuListenerFqcn = $menuNamespace . '\\AdminMenuListener';

        if (!\class_exists($menuListenerFqcn)) {
            $this->generator->generateClass(
                $menuListenerFqcn,
                __DIR__ . '/../Resources/skeleton/AdminMenuListener.tpl.php',
                [
                    'route' => $this->namespace . '_admin_' . mb_strtolower(Str::asSnakeCase($className)),
                    'menu_namespace' => $menuNamespace,
                ],
            );
            $this->generator->writeChanges();

            // Use a simpler service name based on the menu namespace
            $serviceId = strtolower(str_replace('\\', '_', $menuNamespace)) . '.admin.menu_builder';
            $yaml[$serviceId] = [
                 'class' => $menuListenerFqcn,
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
                "\n    " . str_replace("\n", "\n    ", $content),
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
            str_replace('Entity', $templateName, $className) . $templateName,
            (is_string($templatePath) && file_exists($templatePath)) ? $templatePath : __DIR__ . '/../Resources/skeleton/' . $templateName . '.tpl.php',
            array_merge([
                            'entity' => $this->entity ? ($this->entity->getName() ?? $this->namespaces['entity']) : $this->namespaces['entity'],
                            'repository' => $this->repository,
                        ], $variables),
        );
        $this->generator->writeChanges();
        $this->namespaces[strtolower($templateName)] = $file->getFullName();

        return $file;
    }

    public function generateRoute(bool $returnContent = false, ?string $entityName = null): string
    {
        try {
            $yaml = [];
            if (null === $entityName) {
                $entityName = $this->namespaces['entity']->getShortName() ?? $this->entity->getShortName();
            }
            $this->appendRoutingConfig($yaml, $entityName);
            if (true === $returnContent) {
                return Yaml::dump($yaml, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            }
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

    public function generateResource(bool $returnContent = false, ?string $entityName = null, ?string $entityTranslationName = null): string
    {
        try {
            $entityName = $entityName ?? $this->namespaces['entity'] ?? $this->entity->getName();
            if (true === $returnContent) {
                $yamlGenerator = new YamlSourceManipulator("sylius_resource:\n  resources:");
                $yaml = $yamlGenerator->getData();
                $this->appendResourceConfig($yaml, $entityName, $entityTranslationName);
                $yamlGenerator->setData($yaml);

                return $yamlGenerator->getContents();
            }

            $filePath = $this->projectDir . '/' . self::YAML_RESOURCE_FILE;
            $filesystem = new Filesystem();
            if (!$filesystem->exists($filePath)) {
                $filesystem->touch($filePath);
                $filesystem->appendToFile($filePath, "sylius_resource:\n  resources:");
            }
            $yamlGenerator = new YamlSourceManipulator(file_get_contents($filePath) ?: '');
            $yaml = $yamlGenerator->getData();
            $this->appendResourceConfig($yaml, $entityName, $entityTranslationName);
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
            . '    type: ' . str_replace('Entity', 'Admin', $entityName) . "Admin\n"
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
    private function appendResourceConfig(array &$data, string $entityName, ?string $entityTranslationName = null): void
    {
        $alias = mb_strtolower($this->namespace . '.' . Str::asSnakeCase($entityName));
        if (!isset($data['sylius_resource'])) {
            $data['sylius_resource'] = [];
        }
        if (!isset($data['sylius_resource']['resources'])) {
            $data['sylius_resource']['resources'] = [];
        }
        $data['sylius_resource']['resources'][] = YamlSourceManipulator::EMPTY_LINE_PLACEHOLDER_VALUE;

        $controller = str_replace('Entity', 'Controller', $entityName) . 'Controller';

        $data['sylius_resource']['resources'][$alias] =
            [
                'driver' => 'doctrine/orm',
                'classes' => [
                    'model' => $entityName,
                    'repository' => str_replace('Entity', 'Repository', $entityName) . 'Repository',
                    'controller' => class_exists($controller) ? $controller : 'Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController',
                    'form' => str_replace('Entity', 'Admin', $entityName) . 'Admin',
                ],
            ];
        if (null !== $entityTranslationName) {
            $data['sylius_resource']['resources'][$alias]['translation'] = [
                'classes' => [
                    'model' => $entityTranslationName,
                    'controller' => 'Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController',
                    'form' => str_replace('Entity', 'Admin', $entityName) . 'Admin',
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
