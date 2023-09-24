<?php

namespace CbrPuller;

use CbrPuller\VO\CurrencyExchangeRate;

// @TODO: There may be a less optimal approach, but considering accessibility and prototyping, why not give it a try?
class Config
{
    public static ?CurrencyExchangeRate $currencyExchangeRate = null;

    public static ?\DateTimeImmutable $date = null;
    public static bool $useCache = true;

    public static bool $exactDate = false;

    public static string $rendererType = '';

    public static string $defaultBaseCurrency = 'RUR';

    public static bool $resetCache = false;

    public static int $verbosityLevel = 256;

    public static function reset():void
    {
        self::$currencyExchangeRate = null;

        self::$date = null;
        self::$useCache = true;

        self::$exactDate = false;

        self::$rendererType = '';

        self::$defaultBaseCurrency = 'RUR';

        self::$resetCache = false;

        self::$verbosityLevel = 256;
    }
}