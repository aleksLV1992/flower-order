<?php

declare(strict_types=1);

namespace App\Infrastructure\Marketplace;

use App\Application\Import\MarketplaceOrderParser;
use App\Application\Import\OrderRejected;
use App\Application\Import\ParsedOrder;
use App\Application\Import\RejectionCode;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SymfonyMarketplaceOrderParser implements MarketplaceOrderParser
{
    private DenormalizerInterface $serializer;
    private CamelCaseToSnakeCaseNameConverter $names;

    public function __construct(
        PropertyTypeExtractorInterface $types,
        private ValidatorInterface $validator,
        private ObjectMapperInterface $mapper,
    ) {
        $this->names = new CamelCaseToSnakeCaseNameConverter();
        $this->serializer = new Serializer([
            new ArrayDenormalizer(),
            new ObjectNormalizer(nameConverter: $this->names, propertyTypeExtractor: $types),
        ]);
    }

    #[\Override]
    public function extractId(mixed $raw): ?string
    {
        $id = is_array($raw) ? ($raw['id'] ?? null) : null;

        return match (true) {
            is_int($id) => (string) $id,
            is_string($id) && trim($id) !== '' => trim($id),
            default => null,
        };
    }

    #[\Override]
    public function parse(mixed $raw): ParsedOrder
    {
        if (!is_array($raw) || array_is_list($raw)) {
            throw new OrderRejected(RejectionCode::InvalidPayload, 'Запись заказа должна быть объектом');
        }

        foreach (is_array($raw['items'] ?? null) ? array_values($raw['items']) : [] as $i => $item) {
            if (!is_array($item) || ($item !== [] && array_is_list($item))) {
                throw new OrderRejected(RejectionCode::InvalidItem, 'Позиция должна быть объектом', sprintf('items[%d]', $i));
            }
        }

        try {
            $order = $this->serializer->denormalize($raw, MarketplaceOrder::class, null, [
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ]);
        } catch (PartialDenormalizationException $e) {
            throw $this->typeError($e->getNotNormalizableValueErrors()[0]);
        }

        $violations = $this->validator->validate($order);
        if (count($violations) > 0) {
            throw $this->rejection($violations->get(0));
        }

        return $this->mapper->map($order, ParsedOrder::class);
    }

    private function rejection(ConstraintViolationInterface $violation): OrderRejected
    {
        $payload = $violation->getConstraint()?->payload;
        $code = is_array($payload) && is_string($payload['code'] ?? null) ? $payload['code'] : RejectionCode::InvalidPayload->value;

        return new OrderRejected(RejectionCode::from($code), (string) $violation->getMessage(), $this->wireField($violation->getPropertyPath()));
    }

    private function typeError(NotNormalizableValueException $error): OrderRejected
    {
        $field = $this->wireField((string) $error->getPath());
        $code = match (true) {
            $field === 'items' => RejectionCode::NoItems,
            str_starts_with($field, 'items[') => RejectionCode::InvalidItem,
            in_array($field, ['created_at', 'delivery.date', 'delivery.time_from', 'delivery.time_to'], true) => RejectionCode::InvalidDatetime,
            $field === 'customer.phone' => RejectionCode::InvalidPhone,
            $field === 'delivery.type' => RejectionCode::InvalidDeliveryType,
            default => RejectionCode::InvalidPayload,
        };

        return new OrderRejected($code, sprintf('Некорректное значение поля %s', $field), $field);
    }

    private function wireField(string $propertyPath): string
    {
        return implode('.', array_map(
            fn (string $segment): string => $this->names->normalize($segment),
            explode('.', $propertyPath),
        ));
    }
}
