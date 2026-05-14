<?php

namespace App\EventSubscriber;

use App\Exception\APIException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof APIException) {
            $response = new JsonResponse([
                'success' => false,
                'data' => null,
                'error' => [$exception->getMessage()]
            ], $exception->getStatusCode());

            $event->setResponse($response);
            return;
        }

        $response = new JsonResponse([
            'success' => false,
            'data' => null,
            'error' => ['Erro interno']
        ], 500);

        $event->setResponse($response);
    }
}