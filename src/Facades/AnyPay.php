<?php

namespace samgeeksdev\AnyPay\Facades;

 
class AnyPay extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
          return 'anypay';
    }
}
