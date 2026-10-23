<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace;

use App\Domain\Order\PhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class MarketplaceCustomer
{
    public function __construct(
        #[Assert\NotBlank(message: 'Не заполнено обязательное поле: имя покупателя', normalizer: 'trim', payload: ['code' => 'missing_field'])]
        public ?string $name = null,
        #[Assert\NotBlank(message: 'Не заполнено обязательное поле: телефон покупателя', normalizer: 'trim', payload: ['code' => 'missing_field'])]
        public ?string $phone = null,
    ) {
    }

    #[Assert\Callback(payload: ['code' => 'invalid_phone'])]
    public function validatePhone(ExecutionContextInterface $context): void
    {
        if ($this->phone !== null && trim($this->phone) !== '' && !PhoneNumber::tryFromRaw($this->phone) instanceof PhoneNumber) {
            $context->buildViolation(sprintf('Телефон «%s» не приводится к +7XXXXXXXXXX', $this->phone))->atPath('phone')->addViolation();
        }
    }
}
