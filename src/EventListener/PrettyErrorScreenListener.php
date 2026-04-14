<?php

declare(strict_types=1);

/*
 * This file is part of contao-garage/contao-page-400.
 *
 * @author    Martin Schumann <martin.schumann@ontao-garage.de>
 * @license   MIT
 * @copyright Contao Garage 2026
 */

namespace ContaoGarage\Page400\EventListener;

use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\PageFinder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class PrettyErrorScreenListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly PageRegistry $pageRegistry,
        private readonly HttpKernelInterface $httpKernel,
        private readonly PageFinder $pageFinder,
    ) {
    }

    /**
     * Map an exception to an error screen.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ('html' !== $request->getRequestFormat()) {
            return;
        }

        if (!AcceptHeader::fromString($request->headers->get('Accept'))->has('text/html')) {
            return;
        }

        $this->handleException($event);
    }

    private function handleException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        try {
            $isBackendUser = $this->security->isGranted('ROLE_USER');
        } catch (AuthenticationCredentialsNotFoundException) {
            $isBackendUser = false;
        }

        if (400 !== $exception->getStatusCode()) {
            return;
        }

        switch (true) {
            case $isBackendUser:
                return;

            case $exception->getPrevious() instanceof InvalidRequestTokenException:
                $this->renderError400ScreenByType('error_invalidrequesttoken', $event);
                break;

            case $exception instanceof BadRequestHttpException:
                $this->renderError400ScreenByType('error_badrequest', $event);
                break;
        }
    }

    private function renderError400ScreenByType(string $type, ExceptionEvent $event): void
    {
        static $processing;

        if (true === $processing) {
            return;
        }

        $processing = true;

        try {
            $this->framework->initialize();

            $request = $event->getRequest();
            $errorPage = $this->pageFinder->findFirstPageOfTypeForRequest($request, $type);

            if (!$errorPage) {
                return;
            }

            $route = $this->pageRegistry->getRoute($errorPage);
            $subRequest = $request->duplicate(null, null, $route->getDefaults());

            try {
                $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
                $event->setResponse($response);
            } catch (ResponseException $e) {
                $event->setResponse($e->getResponse());
            } catch (\Throwable $e) {
                $event->setThrowable($e);
            }
        } finally {
            $processing = false;
        }
    }
}
