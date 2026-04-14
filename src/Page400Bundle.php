<?php

declare(strict_types=1);

/*
 * This file is part of contao-garage/contao-page-400.
 *
 * @author    Martin Schumann <martin.schumann@ontao-garage.de>
 * @license   MIT
 * @copyright Contao Garage 2026
 */

namespace ContaoGarage\Page400;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Page400Bundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
