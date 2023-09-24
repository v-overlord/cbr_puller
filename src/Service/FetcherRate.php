<?php

namespace CbrPuller\Service;

use CbrPuller\Cache\AbstractCache;
use CbrPuller\Config;
use CbrPuller\Puller\AbstractPuller;
use CbrPuller\VO\PairHistory;
use Psr\Log\LoggerInterface;

class FetcherRate
{
    public string $pairHistoryCacheKey = '%s_history';

    // Since we're unsure about the specific time when the bank publishes quotes,
    // setting a 12-hour cache could be a reasonable compromise. It allows enough time for cache warming
    // while minimizing the number of requests made to the bank.
    public int $ttlForHistoryItemInSec = 60 * 60 * 12; // 43200 sec

    public function __construct(public AbstractCache $cache, public AbstractPuller $puller, public LoggerInterface $logger)
    {
    }

    protected function fetch(): ?PairHistory
    {
        $histories = $this->puller->fetch();
        $history = null;

        if ($histories !== null) {
            foreach ($histories as $_history) {
                $pair = $_history->getPair();
                if ($pair === Config::$currencyExchangeRate->getPair() || $pair === Config::$currencyExchangeRate->getSwappedPair()) {
                    $history = $_history;
                }

                $this->cache->set($this->getPairHistoryKey($_history->getPair()), $history, $this->ttlForHistoryItemInSec);
            }
        }

        return $history;
    }

    public function enrich(): ?PairHistory
    {
        /**
         * @var ?PairHistory $history
         */
        $history = null;

        if (Config::$useCache) {
            $history = $this->cache->get($this->getPairHistoryKey(Config::$currencyExchangeRate->getPair()));
        } else {
            $this->logger->info("The cache is not in use, retrieving data directly from the source...");
        }

        if ($history === null || !$history->isDateWithIn(Config::$date)) {
            $history = $this->fetch();
        }

        if ($history === null) {
            return null;
        }

        $this->cache->set($this->getPairHistoryKey(Config::$currencyExchangeRate->getPair()), $history);

        return $history;
    }

    protected function getPairHistoryKey(string $pair): string
    {
        return sprintf($this->pairHistoryCacheKey, $pair);
    }
}