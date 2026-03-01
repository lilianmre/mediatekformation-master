<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Assainit toutes les entrées utilisateur (GET, POST) à chaque requête
 * en supprimant les balises HTML et les espaces superflus.
 */
class InputSanitizerSubscriber implements EventSubscriberInterface
{
    /**
     * Déclare les événements écoutés par ce subscriber.
     *
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    /**
     * Nettoie les paramètres de la requête courante.
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $sanitizedRequestData = $this->sanitizeValue($request->request->all());
            $request->request->replace($sanitizedRequestData);
        }

        $sanitizedQueryData = $this->sanitizeValue($request->query->all());
        $request->query->replace($sanitizedQueryData);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->sanitizeValue($item);
            }

            return $sanitized;
        }

        if (is_string($value)) {
            return trim(strip_tags($value));
        }

        return $value;
    }
}
