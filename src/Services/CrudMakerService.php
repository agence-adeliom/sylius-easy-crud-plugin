<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Mockery\Exception;
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

    protected array $namespaces;

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
     * @throws \Exception
     */
    public function generateAdmin(): ClassNameDetails
    {
        return $this->generateFileFromTpm('Admin');
    }

    /**
     * @throws \Exception
     */
    public function generateController(): ClassNameDetails
    {
        return $this->generateFileFromTpm('Controller');
    }

    /**
     * @throws \Exception
     */
    public function generateEntity(string $className): void
    {
        $this->generator->generateClass(
            $className,
            __DIR__ . '/../Resources/skeleton/Entity.tpl.php',
            [],
        );
        $this->generator->writeChanges();
        $this->namespaces['entity'] = $className;
    }

    /**
     * @throws \Exception
     */
    public function generateEntityTranslation(string $className): void
    {
        $this->generator->generateClass(
            $className . 'Translation',
            __DIR__ . '/../Resources/skeleton/Translation.tpl.php',
            [],
        );
        $this->generator->writeChanges();
        $this->namespaces['entityTranslation'] = $className . 'Translation';
    }

    /**
     * @throws \Exception
     */
    public function generateRepository(string $className): void
    {
        $this->generator->generateClass(
            str_replace('Entity', 'Repository', $className) . 'Repository',
            __DIR__ . '/../Resources/skeleton/Repository.tpl.php',
            [
                'entity_name' => Str::getShortClassName($className),
            ],
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
                    'route' => $this->namespace. '_admin_'. mb_strtolower(Str::asSnakeCase($className)),
                ],
            );
            $this->generator->writeChanges();

            $yaml = [];
            $yaml['app.listener.admin.menu_builder'] = [
                "class" => 'App\Menu\AdminMenuListener',
                "tags" => [
                    0 => [
                        'name' => 'kernel.event_listener',
                        'event' => 'sylius.menu.admin.main',
                        'method' => 'addAdminMenuItems',
                    ]
                ],
            ];
            $content = Yaml::dump($yaml, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            file_put_contents(
                self::YAML_SERVICES_FILE,
                "    " . str_replace("\n","\n    ",$content),
                \FILE_APPEND,
            );
        }
    }

    /**
     * @throws \Exception
     */
    protected function generateFileFromTpm(string $template, ?string $suffix = null, ?string $className = null): ClassNameDetails
    {
        if (null === $suffix) {
            $suffix = $template;
        }
        $file = $this->generator->createClassNameDetails(
            $className ?? $this->entity->getShortName(),
            $template,
            $suffix,
        );
        $this->generator->generateClass(
            $file->getFullName(),
            __DIR__ . '/../Resources/skeleton/' . $template . '.tpl.php',
            [
                'entity' => $this->entity,
                'repository' => $this->repository,
            ],
        );
        $this->generator->writeChanges();
        $this->namespaces[strtolower($template)] = $file->getFullName();

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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            return $e->getCode() . ' : ' . $e->getMessage();
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function appendRoutingConfig(array &$data, string $entityName): void
    {
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
            . "        icon: 'file'\n"
            . "        subheader: sylius_easy_crud_plugin.admin.ui.default.subheader\n"
            . "        breadcrumb: sylius_easy_crud_plugin.admin.ui.default.index\n"
            . "        templates:\n"
            . "            form: \"@SyliusEasyCrudPlugin\\\\crud\\\\form\\\\_form.html.twig\"\n"
            . "    index:\n"
            . "        header: sylius_easy_crud_plugin.admin.ui.default.index\n"
            . "    create:\n"
            . "        header: sylius_easy_crud_plugin.admin.ui.default.create\n"
            . "    update:\n"
            . "        header: sylius_easy_crud_plugin.admin.ui.default.update\n"
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
}
