<?php

namespace CbrPuller\VO;

use CbrPuller\ADT\Price;

class PairHistory implements \Iterator, \ArrayAccess, \Countable
{
    public string $baseCurrency;
    public string $counterCurrency;

    /**
     * @var array<Price>
     */
    public array $history = [];

    private int $position = 0;

    /**
     * @param string $baseCurrency
     * @param string $counterCurrency
     * @param array<Price> $history
     */
    public function __construct(string $counterCurrency, string $baseCurrency, array $history)
    {
        $this->baseCurrency = $baseCurrency;
        $this->counterCurrency = $counterCurrency;
        $this->history = $history;
    }

    public function isDateWithIn(\DateTimeImmutable $dateTime): bool
    {
        if (count($this->history) === 0) {
            return false;
        }

        $minDate = $this->history[0]->getDateTime();
        $maxDate = $this->history[0]->getDateTime();

        foreach ($this->history as $historyItem) {
            if ($historyItem->getDateTime() < $minDate) {
                $minDate = $historyItem->getDateTime();
            }

            if ($historyItem->getDateTime() > $maxDate) {
                $maxDate = $historyItem->getDateTime();
            }
        }

        return $dateTime <= $maxDate && $dateTime >= $minDate;
    }

    public function getDateSplice(?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null, ?int $length = null): PairHistory
    {
        $history = [];

        foreach ($this->history as $historyItem) {
            if (
                ($from !== null && $historyItem->getDateTime() < $from)
                || ($to !== null && $historyItem->getDateTime() > $to)
            ) {
                continue;
            }

            if ($length !== null && count($history) >= $length) {
                break;
            }

            $history[] = $historyItem;
        }

        return new PairHistory($this->counterCurrency, $this->baseCurrency, $history);
    }

    public function getPair(): string
    {
        return sprintf("%s/%s", $this->baseCurrency, $this->counterCurrency);
    }

    /**
     * @return string
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * @param string $baseCurrency
     */
    public function setBaseCurrency(string $baseCurrency): void
    {
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * @return string
     */
    public function getCounterCurrency(): string
    {
        return $this->counterCurrency;
    }

    /**
     * @param string $counterCurrency
     */
    public function setCounterCurrency(string $counterCurrency): void
    {
        $this->counterCurrency = $counterCurrency;
    }

    public function current(): ?Price
    {
        if ($this->valid()) {
            return $this->history[$this->position];
        }

        return null;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->history[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->history[$offset]);
    }

    public function offsetGet(mixed $offset): ?Price
    {
        return $this->history[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->history[] = $value;
        } else {
            $this->history[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->history[$offset]);
    }

    public function count(): int
    {
        return count($this->history);
    }
}