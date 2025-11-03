<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\Asset\AssetEasyCrudPackage;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\Model\Image;
use function PHPUnit\Framework\assertTrue;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ImageType extends AbstractType implements AdminFormTypeInterface
{
    public function __construct(
        private ImageUploaderInterface $imageUploader,
    ) {
    }

    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\FileType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            assertTrue(null === $data || $data instanceof \SplFileInfo);
            $image = new Image();
            $image->setType('default');
            $image->setFile($data);
            $this->imageUploader->upload($image);
            $event->setData($image->getPath());
        });
    }

    public function getBlockPrefix(): string
    {
        return 'upload_image';
    }

    /**
     * @return array<string, array<int, Asset|string>>
     */
    public static function configureAdminAssets(): array
    {
        return [
            'js' => [
                (Asset::new('field-image.js'))->package(AssetEasyCrudPackage::PACKAGE_NAME),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return ['@SyliusEasyCrudPlugin/field/image/form.html.twig'];
    }
}
