<?php

declare(strict_types=1);

namespace App\Application\Import\View;

use App\Application\View\NoteView;
use Symfony\Component\ObjectMapper\Attribute\Map;

final readonly class ImportResultView
{
    /**
     * @param list<NoteView> $notes
     */
    public function __construct(
        public int $index,
        public ?string $externalId,
        #[Map(transform: [ImportViewTransform::class, 'enumValue'])]
        public string $outcome,
        #[Map(source: 'order', transform: [ImportViewTransform::class, 'orderId'])]
        public ?int $orderId,
        #[Map(source: 'order', transform: [ImportViewTransform::class, 'orderStatus'])]
        public ?string $status,
        #[Map(transform: [ImportViewTransform::class, 'enumValue'])]
        public ?string $previousStatus,
        #[Map(source: 'rejection', transform: [ImportViewTransform::class, 'rejection'])]
        public ?RejectionView $reason,
        public array $notes,
    ) {
    }
}
