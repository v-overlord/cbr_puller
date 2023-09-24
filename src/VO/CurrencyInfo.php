<?php

namespace CbrPuller\VO;

use CbrPuller\Cache\AbstractCache;

class CurrencyInfo
{
    public ?string $id = null;
    public ?string $rusName = null;
    public ?string $engName = null;
    public ?int $nominal = null;
    public ?string $parentId = null;

    public ?int $ISONum = null;
    public ?string $ISOChar = null;

    protected string $priceInfoCacheKey = 'info_for_%s';

    // We're not entirely sure about the lifespan of currency definitions,
    // but based on our intuition, they tend to last for years.
    // However, internal IDs can change unexpectedly, and we couldn't find specific information about it.
    // To be on the safe side, we've decided to set the cache TTL to a week.
    public int $ttlForCurrencyInfoInSec = 60 * 60 * 24 * 7; // 604800 sec

    /**
     * @param string|null $id
     * @param string|null $rusName
     * @param string|null $engName
     * @param int|null $nominal
     * @param string|null $parentId
     * @param int|null $ISONum
     * @param string|null $ISOChar
     */
    public function __construct(?string $id = null, ?string $rusName = null, ?string $engName = null, ?int $nominal = null, ?string $parentId = null, ?int $ISONum = null, ?string $ISOChar = null)
    {
        $this->id = $id;
        $this->rusName = $rusName;
        $this->engName = $engName;
        $this->nominal = $nominal;
        $this->parentId = $parentId;
        $this->ISONum = $ISONum;
        $this->ISOChar = $ISOChar ? mb_strtoupper($ISOChar) : $ISOChar;
    }


    public function replenish(string $ISOChar, AbstractCache $cache): ?CurrencyInfo
    {
        $ISOChar = mb_strtoupper($ISOChar);

        if (!$cache->exists($this->getPairInfoKey($ISOChar))) {
            return null;
        }

        /**
         * @var ?CurrencyInfo $currencyInfo
         */
        $currencyInfo = $cache->get($this->getPairInfoKey($ISOChar));

        if ($currencyInfo === null) {
            return null;
        }

        $this->id = $currencyInfo->id;
        $this->rusName = $currencyInfo->rusName;
        $this->engName = $currencyInfo->engName;
        $this->nominal = $currencyInfo->nominal;
        $this->parentId = $currencyInfo->parentId;
        $this->ISONum = $currencyInfo->ISONum;
        $this->ISOChar = $currencyInfo->ISOChar;

        return $currencyInfo;
    }

    public function serialize(AbstractCache $cache): bool
    {
        if ($this->ISOChar === null) {
            return false;
        }

        return $cache->set($this->getPairInfoKey($this->ISOChar), $this, $this->ttlForCurrencyInfoInSec);
    }

    protected function getPairInfoKey(string $ISOChar): string
    {
        return sprintf($this->priceInfoCacheKey, $ISOChar);
    }
}