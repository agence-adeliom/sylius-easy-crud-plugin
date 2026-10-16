<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Mate\Tool;

use Adeliom\SyliusEasyCrudPlugin\Mate\AdminReader;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Sylius\MateExtension\Output\Envelope;
use Symfony\AI\Mate\Attribute\MateTool;

final class ListAdmins
{
    public function __construct(
        private readonly HostContainerProvider $host,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[MateTool(
        name: 'easy_crud_list_admins',
        description: 'List the easy-crud resources of the application: alias, Admin class, model, controller, declaration (#[AsAdmin] "attribute" or legacy "yaml"), grid name and generated routes. Filter by alias prefix.',
    )]
    public function __invoke(?string $alias_prefix = null): array
    {
        return Envelope::guard(function () use ($alias_prefix): array {
            $admins = (new AdminReader($this->host->getContainer()))->all($alias_prefix);

            if ([] === $admins) {
                return Envelope::empty(sprintf(
                    'No easy-crud resource matched%s. Call sylius_domain_list_resources to see every Sylius resource.',
                    null !== $alias_prefix ? sprintf(' prefix "%s"', $alias_prefix) : '',
                ));
            }

            return Envelope::items($admins);
        });
    }
}
