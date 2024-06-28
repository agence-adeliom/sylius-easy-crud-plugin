<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        return 'make:easy-crud:generate';
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
        $class = $input->getArgument('entity');

        if (!\class_exists($class)) {
            $class = $generator->createClassNameDetails($class, 'Entity\\')->getFullName();
        }

        if (!\class_exists($class)) {
            //throw new RuntimeCommandException(\sprintf('Entity "%s" not found.', $input->getArgument('entity')));
        }

        $namespace = \trim($generator->getRootNamespace(), '\\');

        [$entity, $entityTranslation, $repository] = $this->getEntity($class, $generator);

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

            $resourceConfigGenerator->generateMenuListener($class);

            $resourceConfigGenerator->generateAdmin();

            $resourceConfigGenerator->generateController();

            $route = $resourceConfigGenerator->generateRoute();

            $io->comment(sprintf(
                '%s: %s',
                '<fg=yellow>updated</>',
                $route,
            ));

            $resource = $resourceConfigGenerator->generateResource();

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
     * @return array<int, mixed>
     */
    protected function getEntity(string $class, Generator $generator): array
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

            $repository = new \ReflectionClass($this->managerRegistry->getRepository($entity->getName()));
            if (0 !== \mb_strpos($repository->getName(), $generator->getRootNamespace())) {
                // not using a custom repository
            }

            if (is_string($classTranslation)) {
                $entityTranslation = new \ReflectionClass($classTranslation);
            }
        }

        return [$entity, $entityTranslation, $repository];
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
