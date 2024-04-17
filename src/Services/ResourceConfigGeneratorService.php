<?php

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Yaml\Yaml;

class ResourceConfigGeneratorService
{
    const YAML_ROUTES_FILE = 'config/routes.yaml';
    const YAML_RESOURCE_FILE = 'config/packages/sylius_resource.yaml';

    private string $scope;

    public function generateConfig($entityName, $namespaces, $io, $scope = ''): void
    {
        $this->scope = $scope;

        $this->appendRoutingConfig($yaml, $entityName);
        file_put_contents(
            self::YAML_ROUTES_FILE,
            Yaml::dump($yaml, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK),
            FILE_APPEND
        );
        $io->writeln('Updated: '.self::YAML_ROUTES_FILE);

        $yamlGenerator = new YamlSourceManipulator(file_get_contents(self::YAML_RESOURCE_FILE));
        $yaml = $yamlGenerator->getData();
        $this->appendResourceConfig($yaml, $entityName, $namespaces);
        $yamlGenerator->setData($yaml);
        file_put_contents(self::YAML_RESOURCE_FILE, $yamlGenerator->getContents());
        $io->writeln('Updated: '.self::YAML_RESOURCE_FILE);
    }

    private function appendRoutingConfig(&$data, $entityName): void
    {
        $resourceDataBlock =
            "alias: happy_cms.".mb_strtolower($this->scope)."_".mb_strtolower(Str::asSnakeCase($entityName))."\n"
            ."section: admin\n"
            ."templates: \"@SyliusEasyCrudPlugin\\\\crud\"\n"
            ."redirect: update\n"
            ."grid: happy_cms_admin_".mb_strtolower($this->scope)."_".mb_strtolower(Str::asSnakeCase($entityName))."\n"
            ."form:\n"
            ."    type: App\Admin\HappyCMS\\".$this->scope."\\".$entityName."Admin\n"
            ."    options:\n"
            ."        context: \$context\n"
            ."vars:\n"
            ."    all:\n"
            ."        icon: 'file'\n"
            ."        subheader: happy_cms.".mb_strtolower($this->scope)."_".mb_strtolower(Str::asSnakeCase($entityName)).".admin.ui.subheader\n"
            ."        breadcrumb: happy_cms.".mb_strtolower($this->scope)."_".mb_strtolower(Str::asSnakeCase($entityName)).".admin.ui.index\n"
            ."        templates:\n"
            ."            form: \"@SyliusEasyCrudPlugin\\\\crud\\\\form\\\\_form.html.twig\"\n"
            ."    index:\n"
            ."        header: happy_cms.".mb_strtolower($this->scope)."_".mb_strtolower(Str::asSnakeCase($entityName)).".admin.ui.index\n"
            ."    create:\n"
            ."        header: happy_cms.".mb_strtolower($this->scope)."_".mb_strtolower(Str::asSnakeCase($entityName)).".admin.ui.create\n"
            ."    update:\n"
            ."        header: happy_cms.".mb_strtolower($this->scope)."_".mb_strtolower(Str::asSnakeCase($entityName)).".admin.ui.update\n"
            ."        redirect:\n"
            ."            route: update\n"
            ."            parameters:\n"
            ."                context: \$context\n"
            ."                id: \$id\n"
            ."        route:\n"
            ."            parameters:\n"
            ."                context: \$context\n"
            ."                id: \$id\n";

        $data["happy_cms_admin_" . mb_strtolower($this->scope) . "_" . mb_strtolower(Str::asSnakeCase($entityName))] =
            [
                "resource" => $resourceDataBlock,
                'type' => 'sylius.resource',
                'prefix' => 'admin'
            ];
    }

    private function appendResourceConfig(&$data, $entityName, $namespaces): void
    {
        $data['sylius_resource']['resources'][] = YamlSourceManipulator::EMPTY_LINE_PLACEHOLDER_VALUE;
        $data['sylius_resource']['resources']
        ["happy_cms." . mb_strtolower($this->scope) . "_" . mb_strtolower(Str::asSnakeCase($entityName))] =
            [
                "driver" => "doctrine/orm",
                "classes" => [
                    "model" => $namespaces['entity'] . $entityName,
                    "repository" => $namespaces['repository'] . $entityName . "Repository",
                    "form" => $namespaces['admin'] . $entityName . "Admin"
                ],
                "translation" => [
                    "classes" => [
                        "model" => $namespaces['entity'] . $entityName . "Translation",
                        "controller" => "Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController",
                        "form" => $namespaces['admin'] . $entityName . "Admin"
                    ]
                ]
            ];
    }
}
