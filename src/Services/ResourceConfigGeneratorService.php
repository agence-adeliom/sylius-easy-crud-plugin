<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Mockery\Exception;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Yaml\Yaml;

class ResourceConfigGeneratorService
{
    public const YAML_ROUTES_FILE = 'config/routes.yaml';

    public const YAML_RESOURCE_FILE = 'config/packages/sylius_resource.yaml';

    /**
     * @param array<int|string, string> $namespaces
     */
    public function __construct(
        private string $scope,
        private array $namespaces,
    ) {
        if ($this->scope === '' || 0 === count($this->namespaces)) {
            throw new Exception('Configurator is not well-declared.');
        }
    }

    /**
     * @return array<int, string>|string
     */
    public function generateConfig(string ...$entitiesName): array|string
    {
        try {
            $yaml = [];
            if (0 === count($entitiesName)) {
                throw new Exception('At least one entity name must be given.');
            }
            foreach ($entitiesName as $entityName) {
                $this->appendRoutingConfig($yaml, $entityName);
                file_put_contents(
                    self::YAML_ROUTES_FILE,
                    Yaml::dump($yaml, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK),
                    \FILE_APPEND,
                );
                if (file_exists(self::YAML_RESOURCE_FILE)) {
                    $yamlGenerator = new YamlSourceManipulator(file_get_contents(self::YAML_RESOURCE_FILE) ?: '');
                    $yaml = $yamlGenerator->getData();
                    $this->appendResourceConfig($yaml, $entityName);
                    $yamlGenerator->setData($yaml);
                    file_put_contents(self::YAML_RESOURCE_FILE, $yamlGenerator->getContents());
                }
            }

            return [self::YAML_ROUTES_FILE, self::YAML_RESOURCE_FILE];
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
            'alias: happy_cms.' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName)) . "\n"
            . "section: admin\n"
            . "templates: \"@SyliusEasyCrudPlugin\\\\crud\"\n"
            . "redirect: update\n"
            . 'grid: happy_cms_admin_' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName)) . "\n"
            . "form:\n"
            . "    type: App\Admin\HappyCMS\\" . $this->scope . '\\' . $entityName . "Admin\n"
            . "    options:\n"
            . "        context: \$context\n"
            . "vars:\n"
            . "    all:\n"
            . "        icon: 'file'\n"
            . '        subheader: happy_cms.' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName)) . ".admin.ui.subheader\n"
            . '        breadcrumb: happy_cms.' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName)) . ".admin.ui.index\n"
            . "        templates:\n"
            . "            form: \"@SyliusEasyCrudPlugin\\\\crud\\\\form\\\\_form.html.twig\"\n"
            . "    index:\n"
            . '        header: happy_cms.' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName)) . ".admin.ui.index\n"
            . "    create:\n"
            . '        header: happy_cms.' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName)) . ".admin.ui.create\n"
            . "    update:\n"
            . '        header: happy_cms.' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName)) . ".admin.ui.update\n"
            . "        redirect:\n"
            . "            route: update\n"
            . "            parameters:\n"
            . "                context: \$context\n"
            . "                id: \$id\n"
            . "        route:\n"
            . "            parameters:\n"
            . "                context: \$context\n"
            . "                id: \$id\n";

        $data['happy_cms_admin_' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName))] =
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
        $data['sylius_resource']['resources'][] = YamlSourceManipulator::EMPTY_LINE_PLACEHOLDER_VALUE;
        $data['sylius_resource']['resources']['happy_cms.' . mb_strtolower($this->scope) . '_' . mb_strtolower(Str::asSnakeCase($entityName))] =
            [
                'driver' => 'doctrine/orm',
                'classes' => [
                    'model' => $this->namespaces['entity'] . $entityName,
                    'repository' => $this->namespaces['repository'] . $entityName . 'Repository',
                    'form' => $this->namespaces['admin'] . $entityName . 'Admin',
                ],
                'translation' => [
                    'classes' => [
                        'model' => $this->namespaces['entity'] . $entityName . 'Translation',
                        'controller' => "Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController",
                        'form' => $this->namespaces['admin'] . $entityName . 'Admin',
                    ],
                ],
            ];
    }
}
