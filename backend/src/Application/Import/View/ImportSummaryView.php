<?php

declare(strict_types=1);

namespace App\Application\Import\View;

final readonly class ImportSummaryView
{
    public function __construct(
        public int $total,
        public int $created,
        public int $updated,
        public int $unchanged,
        public int $duplicate,
        public int $rejected,
    ) {
    }
}
