<?php

namespace TheRealJanJanssens\PakkaCore;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TheRealJanJanssens\Pakka\Pakka
 */
class PakkaCoreServiceProvider extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pakka-core';
    }
}
