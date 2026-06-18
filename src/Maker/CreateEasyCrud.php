<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Maker;

use Adeliom\SyliusEasyCrudPlugin\Services\CrudMakerService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class CreateEasyCrud extends AbstractMaker
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ParameterBagInterface $parameterBag,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:easy-crud:create-crud';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Sylius "easy crud" for a Doctrine entity class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('entity', InputArgument::OPTIONAL, 'Entity class to create a admin for')
        ;

        $inputConfig->setArgumentAsNonInteractive('entity');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('entity')) {
            return;
        }

        $argument = $command->getDefinition()->getArgument('entity');
        $entity = $io->choice($argument->getDescription(), $this->entityChoices());

        $input->setArgument('entity', $entity);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entryClassName = $input->getArgument('entity');

        assert(is_string($entryClassName), 'entity must be a string.');

        $namespace = \trim($generator->getRootNamespace(), '\\');
        $entryClassName = $this->resolveEntityClassName($entryClassName, $generator);

        if (!class_exists($entryClassName)) {
            throw new RuntimeCommandException(sprintf(
                'Entity class "%s" does not exist. Create it first with make:easy-crud:create-entity or pass an existing Doctrine entity.',
                $entryClassName,
            ));
        }

        $entryTranslationClassName = class_exists($entryClassName . 'Translation') ? $entryClassName . 'Translation' : null;
        $entryShortClassName = Str::getShortClassName($entryClassName);
        $attributeModeEnabled = $this->isAttributeModeEnabled();

        [$entity, $entityTranslation, $repository] = CrudMakerService::getEntity(
            $entryClassName,
            $generator,
            $this->managerRegistry,
        );

        try {
            $resourceConfigGenerator = new CrudMakerService(
                $generator,
                $namespace,
                $entity,
                $repository,
                $entityTranslation,
            );

            // Generate Admin class
            $adminClassName = $this->adminClassNameForEntity($entryClassName);
            $adminClassDetails = $generator->createClassNameDetails(
                '\\' . $adminClassName,
                'Admin\\',
            );
            $adminFilePath = $this->getClassFilePath($adminClassName);

            if (class_exists($adminClassName) || ('' !== $adminFilePath && file_exists($adminFilePath))) {
                $io->note(sprintf('Admin class already exists, skipping: %s', $adminClassName));
            } else {
                $adminDetails = $resourceConfigGenerator->generateAdmin(
                    className: $entryClassName,
                    variables: [
                       'entityShortName' => $entryShortClassName,
                       'namespace' => str_replace('Entity', 'Admin', $entryClassName),
                       'useAttributeMetadata' => $attributeModeEnabled,
                    ],
                    registerService: !$attributeModeEnabled,
                );
                $adminFilePath = $resourceConfigGenerator->getGeneratedClassPath($adminDetails->getFullName()) ?? $adminFilePath;
                $io->success(sprintf('Created: %s', $adminDetails->getFullName()));
            }

            // Generate Menu Listener
            $namespaceParts = explode('\\', $entryClassName);
            array_pop($namespaceParts); // Remove class name
            $lastPart = array_pop($namespaceParts);
            if ($lastPart !== 'Entity') {
                $namespaceParts[] = $lastPart;
            }
            $baseNamespace = implode('\\', $namespaceParts);
            $menuListenerFqcn = $baseNamespace . '\\Menu\\AdminMenuListener';

            // Build the file path for the menu listener
            $menuListenerPath = str_replace('\\', '/', $menuListenerFqcn);
            $menuListenerPath = str_replace($generator->getRootNamespace() . '/', '', $menuListenerPath);
            $menuListenerFilePath = $generator->getRootDirectory() . '/' . $menuListenerPath . '.php';

            if (class_exists($menuListenerFqcn) || file_exists($menuListenerFilePath)) {
                $io->note(sprintf('Menu listener already exists, skipping: %s', $menuListenerFqcn));
            } else {
                $resourceConfigGenerator->generateMenuListener($entryClassName);
                $io->success(sprintf('Created: %s', $menuListenerFqcn));
            }

            if ($attributeModeEnabled) {
                // Attribute mode: declare the resource via #[AsEasyCrudAdmin] on
                // the Admin class instead of the config/routes.yaml + sylius_resource.yaml blocks.
                // Carry over a convention-based custom controller if one exists (legacy parity).
                $conventionController = str_replace('Entity', 'Controller', $entryClassName) . 'Controller';
                $customController = class_exists($conventionController) ? $conventionController : null;

                $annotatedPath = $resourceConfigGenerator->addEasyCrudAttributeToClass(
                    $adminFilePath,
                    $adminClassDetails->getShortName(),
                    $customController,
                    Str::asClassName($entryShortClassName),
                    'admin_' . mb_strtolower(Str::asSnakeCase($entryClassName)),
                );

                $io->comment(sprintf(
                    '%s: %s (#[AsEasyCrudAdmin])',
                    '<fg=yellow>updated</>',
                    $annotatedPath,
                ));

                $this->warnIfClassOutsideScannedPaths($io, $annotatedPath);
            } else {
                // Legacy mode: declare the resource through the generated YAML blocks.
                trigger_deprecation(
                    'agence-adeliom/sylius-easy-crud-plugin',
                    '2.1',
                    'Declaring easy-crud resources through the generated "config/routes.yaml" and ' .
                    '"config/packages/sylius_resource.yaml" blocks is deprecated and will be removed in 3.0. ' .
                    'Enable "sylius_easy_crud.attributes" and declare the resource with the #[AsEasyCrudAdmin] ' .
                    'attribute on the Admin class instead.',
                );

                $io->warning(
                    'Resource declared via YAML (deprecated). Set "sylius_easy_crud.attributes.enabled: true" ' .
                    '(with "paths" pointing to your Admin directory) to declare it with #[AsEasyCrudAdmin] instead.',
                );

                // Generate/Update routes
                $route = $resourceConfigGenerator->generateRoute(
                    entityName: $entryClassName,
                );

                $io->comment(sprintf(
                    '%s: %s',
                    '<fg=yellow>updated</>',
                    $route,
                ));

                // Generate/Update resource configuration
                $resource = $resourceConfigGenerator->generateResource(
                    entityName: $entryClassName,
                    entityTranslationName: $entryTranslationClassName,
                );

                $io->comment(sprintf(
                    '%s: %s',
                    '<fg=yellow>updated</>',
                    $resource,
                ));
            }

            $this->writeSuccessMessage($io);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        //$io->info(sprintf(
        //    'Don\'t forget to import `%s` in your config/packages/_sylius.yaml configuration file',
        //    $yamlPath,
        //));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // No dependencies needed
    }

    private function isAttributeModeEnabled(): bool
    {
        return $this->parameterBag->has('sylius_easy_crud.attributes.enabled') &&
            true === $this->parameterBag->get('sylius_easy_crud.attributes.enabled');
    }

    /**
     * Warns when the Admin class is not located under one of the scanned attribute
     * paths, in which case the #[AsEasyCrudAdmin] attribute would be ignored.
     */
    private function warnIfClassOutsideScannedPaths(ConsoleStyle $io, string $classPath): void
    {
        if (!$this->parameterBag->has('sylius_easy_crud.attributes.paths')) {
            return;
        }

        /** @var list<string> $paths */
        $paths = $this->parameterBag->get('sylius_easy_crud.attributes.paths');
        $realClassPath = realpath($classPath) ?: $classPath;

        foreach ($paths as $path) {
            $realPath = realpath($path) ?: $path;
            if (str_starts_with($realClassPath, $realPath)) {
                return;
            }
        }

        $io->warning(sprintf(
            'The Admin class "%s" is not under any "sylius_easy_crud.attributes.paths" directory (%s), ' .
            'so its #[AsEasyCrudAdmin] attribute will not be discovered. Add its directory to the paths.',
            $classPath,
            implode(', ', $paths) ?: '(none configured)',
        ));
    }

    /**
     * @return array<int, string>
     */
    private function entityChoices(): array
    {
        $choices = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                $choices[] = $metadata->getName();
            }
        }

        \sort($choices);

        if (empty($choices)) {
            throw new RuntimeCommandException('No entities found.');
        }

        return $choices;
    }

    private function resolveEntityClassName(string $entity, Generator $generator): string
    {
        $entity = ltrim($entity, '\\');
        if (class_exists($entity)) {
            return $entity;
        }

        if (str_contains($entity, '\\')) {
            return $entity;
        }

        return $generator->createClassNameDetails($entity, 'Entity\\')->getFullName();
    }

    /**
     * @param class-string $entityClassName
     */
    private function adminClassNameForEntity(string $entityClassName): string
    {
        return str_replace('\\Entity\\', '\\Admin\\', $entityClassName) . 'Admin';
    }

    private function getClassFilePath(string $className): string
    {
        if (class_exists($className)) {
            $fileName = (new \ReflectionClass($className))->getFileName();
            if (is_string($fileName)) {
                return $fileName;
            }
        }

        return '';
    }
}
