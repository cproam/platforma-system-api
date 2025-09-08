<?php declare(strict_types=1);

namespace App\Entity;

enum PackageType: string
{
    case PAID = 'paid';
    case TEST_DRIVE = 'test-drive';
}
