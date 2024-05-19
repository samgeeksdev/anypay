<?php

namespace samgeeksdev\AnyPay\Facades;

 
class AnyPay extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        // Replace 'elanak' with the name of the binding you set in the register method of your service provider
        return 'anypay';
    }
}