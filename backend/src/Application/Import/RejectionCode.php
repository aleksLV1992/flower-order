<?php

declare(strict_types=1);

namespace App\Application\Import;

enum RejectionCode: string
{
    case MissingField = 'missing_field';
    case InvalidPhone = 'invalid_phone';
    case UnknownStatus = 'unknown_status';
    case NoItems = 'no_items';
    case InvalidItem = 'invalid_item';
    case InvalidDeliveryType = 'invalid_delivery_type';
    case InvalidDatetime = 'invalid_datetime';
    case InvalidInterval = 'invalid_interval';
    case UnknownDistrict = 'unknown_district';
    case InvalidPayload = 'invalid_payload';
}
