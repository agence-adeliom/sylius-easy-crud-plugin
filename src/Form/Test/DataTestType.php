<?php

declare(strict_types=1);

/*
 *  * This file has been edited by Adeliom.
 *  * Adeliom team <contact@adeliom.com>
 */

namespace Adeliom\SyliusEasyCrudPlugin\Form\Test;

use Adeliom\SyliusEasyCrudPlugin\Form\AdminFormTypeInterface;
use Adeliom\SyliusEasyCrudPlugin\Form\ResourceAutocompleteChoiceType;
use Adeliom\SyliusEasyCrudPlugin\Form\ResourceChoiceType;
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use App\Entity\Product\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;

class DataTestType extends AbstractType implements FormTypeInterface, AdminFormTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', HiddenType::class)
            ->add('text', TextType::class)
            ->add('text2', TextType::class)
            ->add('text3', TextareaType::class)
            ->add('product', ResourceChoiceType::class, [
                'resource' => 'sylius.product',
                'class' => Product::class,
                'autocomplete' => true,
            ])
            ->add('products', ResourceChoiceType::class, [
                'resource' => 'sylius.product',
                'class' => Product::class,
                'multiple' => true,
                'autocomplete' => true,
                'useResourceTransformers' => false,
            ])
            ->add('textList', SortableCollectionType::class, [
                'label' => 'textList',
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'allow_drag' => true,
                'entry_options' => ['label' => false],
                'prototype_name' => '__textList__',
            ])
        ;
    }

    public static function configureAdminFormThemes(): array
    {
        return array_merge(
            [],
            SortableCollectionType::configureAdminFormThemes(),
        );
    }

    public static function configureAdminAssets(): array
    {
        return array_merge(
            [],
            SortableCollectionType::configureAdminAssets(),
        );
    }
}
