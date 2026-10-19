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

    /*
    |--------------------------------------------------------------------------
    | Week Start
    |--------------------------------------------------------------------------
    |
    | The day the calendar week begins on this instance: "monday" or
    | "sunday". Weekly Cycles — what a weekly Budget is measured against —
    | start on this day at midnight in the instance timezone (APP_TIMEZONE).
    | Monthly Cycles follow the calendar month and are unaffected by it.
    |
    */

    'week_start' => env('APP_WEEK_START', 'monday'),

];
