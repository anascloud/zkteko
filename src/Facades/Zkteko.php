<?php

namespace Anascloud\Zkteko\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Anascloud\Zkteko\ZKTeco
 */
class Zkteko extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'zkteko';
    }
}
