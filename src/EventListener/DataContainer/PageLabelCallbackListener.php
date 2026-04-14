<?php

declare(strict_types=1);

namespace ContaoGarage\Page400\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

#[AsCallback(table: 'tl_page', target: 'list.label.label')]
class PageLabelCallbackListener
{
    public function __invoke(array $row, string $label, DataContainer $dc, string $imageAttribute = '', bool $returnImage = false, bool|null $isProtected = null): string
    {
        // Simulate type 404 for selecting same icon
        if (\in_array($row['type'], ['error_badrequest', 'error_invalidrequesttoken'], true)) {
            $row['type'] = 'error_404';
        }

        return Backend::addPageIcon($row, $label, $dc, $imageAttribute, $returnImage, $isProtected);
    }
}
