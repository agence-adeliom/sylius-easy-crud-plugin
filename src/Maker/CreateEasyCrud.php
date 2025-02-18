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

            $resourceConfigGenerator->generateAdmin(
                className: $entryClassName,
                variables: [
                   'entityShortName' => $entryShortClassName,
                   'namespace' => str_replace('Entity', 'Admin', $entryClassName),
                ],
            );

            $resourceConfigGenerator->generateMenuListener($entryClassName);

            $route = $resourceConfigGenerator->generateRoute(
                entityName: $entryClassName,
            );

            $io->comment(sprintf(
                '%s: %s',
                '<fg=yellow>updated</>',
                $route,
            ));

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
