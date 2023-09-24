<?php

namespace CbrPuller\VO;

class CurrencyExchangeRate
{
    public string $baseCurrency;
    public string $counterCurrency;

    public ?CurrencyInfo $baseCurrencyInfo = null;
    public ?CurrencyInfo $counterCurrencyInfo = null;

    public ?PairHistory $history = null;

    public string $pairFormatMessage = '%s/%s';
    public string $toStringFormatMessage = '%s [%s]';

    /**
     * @param string $baseCurrency
     * @param string $counterCurrency
     * @param PairHistory|null $history
     */
    public function __construct(string $baseCurrency, string $counterCurrency, ?PairHistory $history = null)
    {
        $this->baseCurrency = $baseCurrency;
        $this->counterCurrency = $counterCurrency;
        $this->history = $history;
    }

    /**
     * @return PairHistory|null
     */
    public function getHistory(): ?PairHistory
    {
        return $this->history;
    }

    /**
     * @param PairHistory|null $history
     */
    public function setHistory(?PairHistory $history): void
    {
        $this->history = $history;
    }

    public function getPair(): string
    {
        return sprintf($this->pairFormatMessage, $this->baseCurrency, $this->counterCurrency);
    }

    public function getSwappedPair(): string
    {
        return sprintf($this->pairFormatMessage, $this->counterCurrency, $this->baseCurrency);
    }

    /**
     * @return CurrencyInfo|null
     */
    public function getBaseCurrencyInfo(): ?CurrencyInfo
    {
        return $this->baseCurrencyInfo;
    }

    /**
     * @param CurrencyInfo|null $baseCurrencyInfo
     */
    public function setBaseCurrencyInfo(?CurrencyInfo $baseCurrencyInfo): void
    {
        $this->baseCurrencyInfo = $baseCurrencyInfo;
    }

    /**
     * @return CurrencyInfo|null
     */
    public function getCounterCurrencyInfo(): ?CurrencyInfo
    {
        return $this->counterCurrencyInfo;
    }

    /**
     * @param CurrencyInfo|null $counterCurrencyInfo
     */
    public function setCounterCurrencyInfo(?CurrencyInfo $counterCurrencyInfo): void
    {
        $this->counterCurrencyInfo = $counterCurrencyInfo;
    }

    public function toString(): string
    {
        return sprintf($this->toStringFormatMessage, $this->getPair(), $this->price ?? 'NaN');
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}