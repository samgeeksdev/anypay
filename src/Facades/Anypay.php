<?php

namespace Samgeeksdev\Anypay\Facades;

 
class Anypay extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
         return 'anypay';
    }
}
