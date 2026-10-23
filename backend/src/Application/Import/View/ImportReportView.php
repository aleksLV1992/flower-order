<?php

declare(strict_types=1);

namespace App\Application\Import\View;

final readonly class ImportReportView
{
    /**
     * @param list<ImportResultView> $results
     */
    public function __construct(
        public ImportSummaryView $summary,
        public array $results,
    ) {
    }
}
