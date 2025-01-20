<?php

declare(strict_types=1);

namespace Daycode\Sketch\Facades;

use Illuminate\Support\Facades\Facade;

class Sketch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sketch';
    }
}
