<?php

namespace IAMXID\IamxServerWallet\Facades;

use Illuminate\Support\Facades\Facade;

class ServerWallet extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ServerWallet';
    }
}
