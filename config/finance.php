<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base Currency
    |--------------------------------------------------------------------------
    |
    | The one base currency of this instance, as an ISO 4217 code. Every
    | money column stores minor units of this currency, and Money formats
    | for display with it. Set it deliberately at install time: changing it
    | under existing data silently reinterprets every stored amount.
    |
    */

    'currency' => env('APP_CURRENCY', 'USD'),

];
