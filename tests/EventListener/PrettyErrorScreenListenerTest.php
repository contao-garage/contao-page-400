<?php

declare(strict_types=1);

/*
 * This file is part of contao-garage/contao-page-400.
 *
 * @author    Martin Schumann <martin.schumann@ontao-garage.de>
 * @license   MIT
 * @copyright Contao Garage 2026
 */

namespace ContaoGarage\Page400\Tests\EventListener;

use Contao\CoreBundle\Exception\BadRequestException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use ContaoGarage\Page400\EventListener\PrettyErrorScreenListener;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PrettyErrorScreenListenerTest extends ContaoTestCase
{
    public static function exceptionProvider(): iterable
    {
        yield 'BadRequestException' => [
            'contaoException' => BadRequestException::class,
            'exceptionMessage' => 'A Contao bad request exception for unit testing.',
            'pageType' => 'error_badrequest',
        ];

        yield 'InvalidRequestTokenException' => [
            'contaoException' => InvalidRequestTokenException::class,
            'exceptionMessage' => 'A Contao invalid request token exception for unit testing.',
            'pageType' => 'error_invalidrequesttoken',
        ];
    }

    #[DataProvider('exceptionProvider')]
    public function testRendersPretty400ErrorPage(string $contaoException, string $exceptionMessage, string $pageType): void
    {
        $regularPage = $this->mockClassWithProperties(PageModel::class, ['type' => 'regular']);
        $regularPage
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $request = new Request();
        $request->headers->set('Accept', 'text/html');
        $request->attributes->add([
            '_scope' => 'frontend',
            '_format' => 'html',
            'pageModel' => $regularPage,
        ]);

        $errorPage = $this->mockClassWithProperties(PageModel::class, ['type' => $pageType]);
        $errorPage
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        $subRequest = null;
        $framework = $this->mockContaoFramework();
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturnMap([['ROLE_USER', false]])
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $httpKernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->isInstanceOf(Request::class),
                HttpKernelInterface::SUB_REQUEST,
                false,
            )
            ->willReturnCallback(
                static function (Request $request) use (&$subRequest): Response {
                    $subRequest = $request;
                    $pageModel = $request->attributes->get('pageModel');

                    return new Response(\sprintf('Response with error page type: %s;', $pageModel->type), 400);
                },
            )
        ;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findFirstPageOfTypeForRequest')
            ->with(
                $this->identicalTo($request),
                $pageType,
            )
            ->willReturn($errorPage)
        ;

        $route = $this->createStub(PageRoute::class);
        $route
            ->method('getDefaults')
            ->willReturn([
                'pageModel' => $errorPage,
                '_scope' => 'frontend',
                '_format' => 'html',
            ])
        ;

        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($errorPage)
            ->willReturn($route)
        ;

        $exception = new BadRequestHttpException(
            'A Symfony bad request HTTP exception for unit testing.',
            new $contaoException($exceptionMessage),
        );

        $event = new ExceptionEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $listener = new PrettyErrorScreenListener($framework, $security, $pageRegistry, $httpKernel, $pageFinder);
        $listener($event);

        $this->assertTrue(
            $event->hasResponse(),
            \sprintf(
                'No response was set. Current throwable: %s: %s',
                $event->getThrowable()::class,
                $event->getThrowable()->getMessage(),
            ),
        );

        $this->assertInstanceOf(Request::class, $subRequest);
        $this->assertSame($errorPage, $subRequest->attributes->get('pageModel'));
        $this->assertSame('frontend', $subRequest->attributes->get('_scope'));
        $this->assertSame('html', $subRequest->attributes->get('_format'));

        $this->assertSame(400, $event->getResponse()->getStatusCode());
        $this->assertInstanceOf(BadRequestHttpException::class, $event->getThrowable());
        $this->assertInstanceOf($contaoException, $event->getThrowable()->getPrevious());
        $this->assertStringContainsString("Response with error page type: {$pageType}", $event->getResponse()->getContent());
    }
}
