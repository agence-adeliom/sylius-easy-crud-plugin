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
        $entryTranslationClassName = $entryClassName . 'Translation';

        if (!class_exists($entryClassName)) {
            $entryClassNameDetail = $generator->createClassNameDetails(
                $entryClassName,
                'Entity\\',
            );
            $entryClassName = $entryClassNameDetail->getFullName();
        }
        if (!class_exists($entryTranslationClassName)) {
            $entryTranslationClassNameDetail = $generator->createClassNameDetails(
                $entryClassName,
                'Entity\\',
                'Translation',
            );
            $entryTranslationClassName = $entryTranslationClassNameDetail->getFullName();
        }

        $entryShortClassName = Str::getShortClassName($entryClassName);

        $namespace = \trim($generator->getRootNamespace(), '\\');

        [$entity, $entityTranslation, $repository] = CrudMakerService::getEntity(
            $entryClassName,
            $generator,
            $this->managerRegistry,
        );

        $projectDir = $this->parameterBag->get('kernel.project_dir');

        try {
            $resourceConfigGenerator = new CrudMakerService(
                is_string($projectDir) ? $projectDir : '',
                $generator,
                $namespace,
                $entity,
                $repository,
                $entityTranslation,
            );

            // Generate Admin class
            $adminClassName = str_replace('Entity', 'Admin', $entryClassName) . 'Admin';
            $adminClassDetails = $generator->createClassNameDetails(
                $entryShortClassName,
                'Admin',
                'Admin'
            );
            $adminFilePath = $generator->getRootDirectory() . '/' . $adminClassDetails->getRelativeName();

            if (class_exists($adminClassName) || file_exists($adminFilePath)) {
                $io->note(sprintf('Admin class already exists, skipping: %s', $adminClassName));
            } else {
                $adminDetails = $resourceConfigGenerator->generateAdmin(
                    className: $entryClassName,
                    variables: [
                       'entityShortName' => $entryShortClassName,
                       'namespace' => str_replace('Entity', 'Admin', $entryClassName),
                    ],
                );
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
}
