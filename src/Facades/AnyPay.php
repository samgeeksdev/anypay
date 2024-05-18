<?php

namespace samgeeksdev\AnyPay\Facades;

 
class AnyPay extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        //test
         return 'anypay';
    }
}
