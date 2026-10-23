<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace;

use App\Domain\Order\DeliveryType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class MarketplaceDelivery
{
    public function __construct(
        #[Assert\NotBlank(message: 'Не заполнено обязательное поле: способ получения', normalizer: 'trim', payload: ['code' => 'missing_field'])]
        public ?string $type = null,
        #[Assert\When(
            expression: static function (self $d): bool {
                return $d->isDelivery();
            },
            constraints: [new Assert\NotBlank(message: 'Не заполнено обязательное поле: район доставки', normalizer: 'trim', payload: ['code' => 'missing_field'])],
        )]
        public ?string $district = null,
        #[Assert\When(
            expression: static function (self $d): bool { return $d->isDelivery(); },
            constraints: [new Assert\NotBlank(message: 'Не заполнено обязательное поле: адрес доставки', normalizer: 'trim', payload: ['code' => 'missing_field'])],
        )]
        public ?string $address = null,
        #[Assert\When(
            expression: static function (self $d): bool { return $d->isDelivery(); },
            constraints: [new Assert\NotBlank(message: 'Не заполнено обязательное поле: дата доставки', normalizer: 'trim', payload: ['code' => 'missing_field'])],
        )]
        public ?string $date = null,
        #[Assert\When(
            expression: static function (self $d): bool { return $d->isDelivery(); },
            constraints: [new Assert\NotBlank(message: 'Не заполнено обязательное поле: начало интервала доставки', normalizer: 'trim', payload: ['code' => 'missing_field'])],
        )]
        public ?string $timeFrom = null,
        #[Assert\When(
            expression: static function (self $d): bool { return $d->isDelivery(); },
            constraints: [new Assert\NotBlank(message: 'Не заполнено обязательное поле: конец интервала доставки', normalizer: 'trim', payload: ['code' => 'missing_field'])],
        )]
        public ?string $timeTo = null,
    ) {
    }

    public function isDelivery(): bool
    {
        return $this->type !== null && MarketplaceValues::deliveryType($this->type) === DeliveryType::Delivery;
    }

    #[Assert\Callback(payload: ['code' => 'invalid_delivery_type'])]
    public function validateType(ExecutionContextInterface $context): void
    {
        if ($this->type !== null && trim($this->type) !== '' && !MarketplaceValues::deliveryType($this->type) instanceof DeliveryType) {
            $context->buildViolation(sprintf('Неизвестный способ получения «%s»', $this->type))->atPath('type')->addViolation();
        }
    }

    #[Assert\Callback(payload: ['code' => 'invalid_datetime'])]
    public function validateSlotFormat(ExecutionContextInterface $context): void
    {
        if (!$this->isDelivery()) {
            return;
        }
        if ($this->date !== null && trim($this->date) !== '' && !MarketplaceValues::isDate(trim($this->date))) {
            $context->buildViolation(sprintf('Некорректная дата доставки «%s»', $this->date))->atPath('date')->addViolation();
        }
        foreach (['timeFrom' => $this->timeFrom, 'timeTo' => $this->timeTo] as $path => $time) {
            if ($time !== null && trim($time) !== '' && !MarketplaceValues::isTime(trim($time))) {
                $context->buildViolation(sprintf('Некорректное время «%s»', $time))->atPath($path)->addViolation();
            }
        }
    }

    #[Assert\Callback(payload: ['code' => 'invalid_interval'])]
    public function validateInterval(ExecutionContextInterface $context): void
    {
        $from = $this->slotStart();
        $to = $this->slotEnd();
        if ($from instanceof \DateTimeImmutable && $to instanceof \DateTimeImmutable && $to <= $from) {
            $context->buildViolation('Конец интервала доставки не позже начала')->atPath('timeTo')->addViolation();
        }
    }

    public function slotStart(): ?\DateTimeImmutable
    {
        return $this->slot($this->timeFrom);
    }

    public function slotEnd(): ?\DateTimeImmutable
    {
        return $this->slot($this->timeTo);
    }

    private function slot(?string $time): ?\DateTimeImmutable
    {
        if (!$this->isDelivery() || $this->date === null || $time === null
            || !MarketplaceValues::isDate(trim($this->date)) || !MarketplaceValues::isTime(trim($time))) {
            return null;
        }

        return MarketplaceValues::slot(trim($this->date), trim($time));
    }
}
