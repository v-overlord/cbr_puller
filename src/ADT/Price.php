<?php

namespace CbrPuller\ADT;

class Price
{
    protected float $value;

    public int $precision = 5;

    protected \DateTimeImmutable $dateTime;

    public static string $toStringDateFormat = 'd/m/Y';

    public static string $outputFormat = '%f [at %s]';

    /**
     * @param float $value
     * @param \DateTimeImmutable $dateTime
     * @param string $toStringDateFormat
     * @param string $outputFormat
     */
    public function __construct(float $value, \DateTimeImmutable $dateTime, string $toStringDateFormat = 'd/m/Y', string $outputFormat = '%f [%s]')
    {
        $this->value = $value;
        $this->dateTime = $dateTime;
        self::$toStringDateFormat = $toStringDateFormat;
        self::$outputFormat = $outputFormat;
    }

    /**
     * @return string
     */
    public function getDateTimeAsString(): string
    {
        return $this->dateTime->format(self::$toStringDateFormat);
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDateTime(): \DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTimeImmutable $dateTime
     */
    public function setDateTime(\DateTimeImmutable $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    /**
     * To account for the Bank of the Russian Federation's reverse (indirect) quote, you'll need to divide 1 Russian Ruble (RUR) by the quoted currency rate.
     *
     * @return float
     */
    public function getDirectQuotation(): float
    {
        return round(1 / $this->getValue(), $this->precision);
    }

    /**
     * @return float
     */
    public function getIndirectQuotation(): float
    {
        return $this->getValue();
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return sprintf(self::$outputFormat, $this->getValue(), $this->dateTime->format(self::$toStringDateFormat));
    }


}