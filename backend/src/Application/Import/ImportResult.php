<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Application\View\NoteView;
use App\Domain\Order\Order;
use App\Domain\Order\OrderStatus;

final readonly class ImportResult
{
    /**
     * @param list<NoteView> $notes
     */
    private function __construct(
        public int $index,
        public ?string $externalId,
        public ImportOutcome $outcome,
        public ?Order $order = null,
        public ?OrderStatus $previousStatus = null,
        public ?OrderRejected $rejection = null,
        public array $notes = [],
    ) {
    }

    /**
     * @param list<NoteView> $notes
     */
    public static function of(int $index, ImportOutcome $outcome, Order $order, array $notes = [], ?OrderStatus $previousStatus = null): self
    {
        return new self($index, $order->externalId, $outcome, $order, $previousStatus, null, $notes);
    }

    public static function duplicate(int $index, string $externalId, int $firstIndex): self
    {
        return new self($index, $externalId, ImportOutcome::Duplicate, notes: [new NoteView(
            'duplicate_in_batch',
            sprintf('Заказ %s уже встречался в этой пачке (запись №%d), повтор пропущен', $externalId, $firstIndex + 1),
        )]);
    }

    public static function rejected(int $index, ?string $externalId, OrderRejected $e): self
    {
        return new self($index, $externalId, ImportOutcome::Rejected, rejection: $e);
    }
}
