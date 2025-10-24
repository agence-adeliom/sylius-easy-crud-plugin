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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class CreateEntity extends AbstractMaker
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ParameterBagInterface $parameterBag,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:easy-crud:create-entity';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates simple entity, entityTranslation and repository';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('className', InputArgument::REQUIRED, 'Ex : Post')
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL, 'Custom namespace (default: auto-detect from Doctrine)')
        ;

        $inputConfig->setArgumentAsNonInteractive('className');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $className = $input->getArgument('className');

        // Allow custom namespace override via option
        $customNamespace = $input->getOption('namespace');

        if ($customNamespace) {
            // Use the provided namespace
            $class = rtrim($customNamespace, '\\') . '\\Entity\\' . $className;
        } else {

            // Use Generator to automatically resolve the correct namespace based on Doctrine configuration
            $entityClassDetails = $generator->createClassNameDetails(
                $className,
                'Entity\\'
            );
            $class = $entityClassDetails->getFullName();
        }

        $io->comment(sprintf('Creating entity: %s', $class));

        if (!\class_exists($class)) {
            // Extract the root namespace from the class name
            // For "App\Entity\Post", rootNamespace should be "App"
            $classParts = explode('\\', $class);
            if (count($classParts) >= 3) {
                // Remove "Entity" and class name to get root namespace
                array_pop($classParts); // Remove class name
                array_pop($classParts); // Remove "Entity"
                $namespace = implode('\\', $classParts);
            } else {
                $namespace = \trim($generator->getRootNamespace(), '\\');
            }

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

                $resourceConfigGenerator->generateEntity($class);
                $resourceConfigGenerator->generateEntityTranslation($class);
                $resourceConfigGenerator->generateRepository($class);

                $io->comment('Now run bin/console make:easy-crud:create-crud to create a sylius easy crud based on an entity');

                $this->writeSuccessMessage($io);
            } catch (\Exception $exception) {
                $io->error($exception->getMessage());
            }
        } else {
            $io->info(\sprintf('Entity "%s" already exists.', $input->getArgument('className')));
        }
    }

    /**
     * @return array<int, mixed>
     */
    protected function getEntity(string $class, Generator $generator): array
    {
        $entity = null;
        $entityTranslation = null;
        $repository = null;
        if (\class_exists($class)) {
            $entity = new \ReflectionClass($class);

            $repository = new \ReflectionClass($this->managerRegistry->getRepository($entity->getName()));
            if (0 !== \mb_strpos($repository->getName(), $generator->getRootNamespace())) {
                // not using a custom repository
            }

            if (method_exists($entity, 'createTranslation')) {
                $object = get_class($entity->createTranslation());
                if (is_string($object)) {
                    $entityTranslation = new \ReflectionClass($object);
                }
            }
        }

        return [$entity, $entityTranslation, $repository];
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}
