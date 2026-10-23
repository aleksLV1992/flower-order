<?php

declare(strict_types=1);

namespace App\Application\View;

final readonly class NoteView
{
    public function __construct(
        public string $code,
        public string $message,
    ) {
    }
}
