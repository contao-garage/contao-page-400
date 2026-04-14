<?php

declare(strict_types=1);

/*
 * This file is part of contao-garage/contao-page-400.
 *
 * @author    Martin Schumann <martin.schumann@ontao-garage.de>
 * @license   MIT
 * @copyright Contao Garage 2026
 */

namespace ContaoGarage\Page400\Controller\Page;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\FrontendIndex;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BadRequestPageController extends AbstractController implements ContentCompositionInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(PageModel $pageModel, Request $request): Response
    {
        return $this->framework
            ->createInstance(FrontendIndex::class)
            ->renderPage($pageModel)
        ;
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        return \in_array($pageModel->type, ['error_invalidrequesttoken', 'error_badrequest'], true);
    }

    protected function setCacheHeaders(Response $response, PageModel $pageModel): Response
    {
        // Never cache error pages
        $response->headers->set('Cache-Control', 'no-cache, no-store');

        return $response->setPrivate();
    }
}
