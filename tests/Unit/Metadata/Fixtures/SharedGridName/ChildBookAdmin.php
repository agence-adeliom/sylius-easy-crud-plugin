<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\SharedGridName;

/**
 * Extends the Admin above without overriding getName(): both Admins would register their grid under "shared_book".
 */
final class ChildBookAdmin extends ParentBookAdmin
{
}
