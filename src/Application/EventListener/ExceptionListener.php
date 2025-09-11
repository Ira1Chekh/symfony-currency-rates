<?php

namespace App\Application\EventListener;

use App\Application\Exception\ServiceException;
use App\Application\Exception\UseCaseException;
use App\Application\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = match (true) {
            $exception instanceof ValidationException => new JsonResponse(
                [
                    'error' => $exception->getMessage(),
                    'details' => $exception->getErrors()
                ],
                $exception->getCode()
            ),
            $exception instanceof ServiceException => new JsonResponse(
                ['error' => 'Service error: ' . $exception->getMessage()],
                $exception->getCode() ?: 500
            ),
            $exception instanceof UseCaseException => new JsonResponse(
                ['error' => 'Application error: ' . $exception->getMessage()],
                $exception->getCode() ?: 500
            ),
            default => null
        };

        if ($response) {
            $event->setResponse($response);
        }
    }
}
