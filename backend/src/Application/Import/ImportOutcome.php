<?php

declare(strict_types=1);

namespace App\Application\Import;

enum ImportOutcome: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Unchanged = 'unchanged';
    case Duplicate = 'duplicate';
    case Rejected = 'rejected';
}
