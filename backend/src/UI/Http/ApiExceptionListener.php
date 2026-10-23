<?php

declare(strict_types=1);

namespace App\UI\Http;

use App\Application\Exception\UnknownShop;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener]
final readonly class ApiExceptionListener
{
    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        [$status, $code, $message] = $this->describe($event->getThrowable());
        $event->setResponse(new JsonResponse(['error' => ['code' => $code, 'message' => $message]], $status));
    }

    /**
     * @return array{int, string, string}
     */
    private function describe(\Throwable $e): array
    {
        $previous = $e->getPrevious();

        return match (true) {
            $e instanceof UnknownShop => [404, 'shop_not_found', 'Магазин не найден'],
            $e instanceof HttpExceptionInterface && $previous instanceof ValidationFailedException => $this->validation($e->getStatusCode(), $previous),
            $e instanceof HttpExceptionInterface && $previous instanceof NotEncodableValueException => [400, 'invalid_json', 'Тело запроса — не валидный JSON'],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), 'http_' . $e->getStatusCode(), $e->getMessage()],
            default => $this->internal($e),
        };
    }

    /**
     * @return array{int, string, string}
     */
    private function validation(int $status, ValidationFailedException $e): array
    {
        $violations = iterator_to_array($e->getViolations());
        $payload = $e->getViolations()->get(0)->getConstraint()?->payload;
        $code = is_array($payload) && is_string($payload['code'] ?? null) ? $payload['code'] : 'invalid_request';
        $message = implode('; ', array_map(
            static fn (ConstraintViolationInterface $v): string => sprintf('%s: %s', $v->getPropertyPath(), $v->getMessage()),
            $violations,
        ));

        return [$status, $code, $message];
    }

    /**
     * @return array{int, string, string}
     */
    private function internal(\Throwable $e): array
    {
        $this->logger?->error('Unhandled API error', ['exception' => $e]);

        return [500, 'internal_error', 'Внутренняя ошибка сервера, попробуйте ещё раз'];
    }
}
