<?php declare(strict_types=1);

namespace App\Entity;

enum FranchiseStatus: string
{
    case PUBLISHED = 'published';
    case TESTING = 'testing';
    case UNPUBLISHED = 'unpublished';
}
