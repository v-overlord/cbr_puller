<?php

namespace CbrPuller\Puller;

use CbrPuller\ADT\Price;
use CbrPuller\Cache\AbstractCache;
use CbrPuller\Config;
use CbrPuller\VO\CurrencyInfo;
use CbrPuller\VO\PairHistory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Cbr extends AbstractPuller
{
    public string $baseApi = 'https://www.cbr.ru/scripts/';

    // It is advisable to use the user agent as its absence can sometimes result in errors.
    public string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36';

    protected HttpClientInterface $httpClient;

    protected string $defaultISOCode = 'RUR';

    public function __construct(HttpClient $httpClientBuilder, public AbstractCache $cache, public LoggerInterface $logger)
    {
        libxml_use_internal_errors(true);

        $this->setUpTransportClient($httpClientBuilder);
    }

    protected function setUpTransportClient(HttpClient $httpClientBuilder): void
    {
        $this->httpClient = $httpClientBuilder->createForBaseUri($this->baseApi, [
            'headers' => ['User-Agent' => $this->userAgent],
        ]);

        if ($this->httpClient instanceof ScopingHttpClient) {
            $this->httpClient->setLogger($this->logger);
        }
    }

    /**
     * @inheritDoc
     */
    public function fetch(): ?array
    {
        if (!$this->fetchCurrenciesInfo()) {
            return null;
        }

        return $this->fetchCurrencyPairInfo();
    }

    // @TODO: Fix the selection for daily or monthly updates.
    protected function fetchCurrenciesInfo(): bool
    {
        $url = 'XML_valFull.asp';
        $query = fn(bool $isDailyUpdate): array =>
        [
            'd' => $isDailyUpdate ? '0' : '1' // 0 - Daily update, 1 - Monthly update
        ];
        $isDailyUpdate = true;

        $baseCurrencyInfo = new CurrencyInfo();
        $counterCurrencyInfo = new CurrencyInfo();

        $isItPairWithDefaultCurrency = Config::$currencyExchangeRate->baseCurrency === $this->defaultISOCode;

        $isReplenishSuccessful = fn() => (!$isItPairWithDefaultCurrency
                && $baseCurrencyInfo->replenish(Config::$currencyExchangeRate->baseCurrency, $this->cache) === null)
            || $counterCurrencyInfo->replenish(Config::$currencyExchangeRate->counterCurrency, $this->cache) === null;

        if ($isReplenishSuccessful()) {
            $xmlDocument = $this->makeRequest($url, $query($isDailyUpdate));
            if ($xmlDocument === null) {
                return false;
            }

            foreach ($xmlDocument->Item as $item) {
                $currencyISOChar = trim($item->ISO_Char_Code);

                // It can apply to certain outdated currencies, like those that no longer exist but are still returned by the API.
                if ($currencyISOChar === '') {
                    continue;
                }

                $currencyInfo = new CurrencyInfo(
                    trim($item->attributes()->ID),
                    trim($item->Name),
                    trim($item->EngName),
                    (int)$item->Nominal,
                    trim($item->ParentCode),
                    (int)$item->ISO_Num_Code,
                    trim($item->ISO_Char_Code)
                );

                $currencyInfo->serialize($this->cache);

            }
            $this->logger->debug("A total of {number} info records have been successfully saved!", ['number' => count($xmlDocument->Item)]);

            if ($isReplenishSuccessful()) {
                $this->logger->alert("The currency pair {pair} cannot be found on the CBR site!", ['pair' => Config::$currencyExchangeRate->getPair()]);
                return false;
            }
        }

        Config::$currencyExchangeRate->setBaseCurrencyInfo($baseCurrencyInfo);
        Config::$currencyExchangeRate->setCounterCurrencyInfo($counterCurrencyInfo);

        return true;
    }

    /**
     * @return ?array<PairHistory>
     */
    protected function fetchCurrencyPairInfo(): ?array
    {
        $url = 'XML_dynamic.asp';
        $requestDateFormat = 'd/m/Y';
        $query = fn(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, string $currencyId): array => [
            'date_req1' => $startDate->format($requestDateFormat), // The start date
            'date_req2' => $endDate->format($requestDateFormat),   // The end date
            'VAL_NM_RQ' => $currencyId,                            // The inner bank ID for the currency [RXXXXX]
        ];

        $requestedDate = Config::$date;

        // If the rate for the previous day is not available due to a holiday
        // or if the bank doesn't issue rates on that particular day,
        // we can fetch the rates for the whole 180 days before the requested date
        // and then choose the best available date.
        // Also, in case the requested date falls on a holiday, we can try to find the closest available rate to that date.
        $weekBeforeRequestedDate = $requestedDate->sub(new \DateInterval('P180D'));

        $counterCurrencyHistoryDocument = $this->makeRequest($url, $query($weekBeforeRequestedDate, $requestedDate, Config::$currencyExchangeRate->counterCurrencyInfo->id));

        $getPriceByElement = function (\SimpleXMLElement $node): Price
        {
            $responseDateFormat = 'd.m.Y H:i:s u';
            $rawPrice = str_replace(',', '.', $node->Value);
            $fullDate = $node->attributes()->Date . ' 00:00:00 0';

            $normalizePrice = (float) $rawPrice / (int) $node->Nominal;
            $date = \DateTimeImmutable::createFromFormat($responseDateFormat, $fullDate);

            return new Price($normalizePrice, $date);
        };

        $sortAndBuildPairHistory = function (string $counterCurrency, string $baseCurrency, array $prices): PairHistory
        {
            usort($prices, function (Price $priceA, Price $priceB) {
                return $priceB->getDateTime() <=> $priceA->getDateTime();
            });

            return new PairHistory($counterCurrency, $baseCurrency, $prices);
        };

        $getPairHistory = function (string $counterCurrency, string $baseCurrency, ?\SimpleXMLElement $document) use ($getPriceByElement, $sortAndBuildPairHistory): ?PairHistory
        {
            if ($document === null || empty($document->Record)) {
                $this->logger->critical("I couldn't find any historical data for the {pair} currency pair because the Central Bank of Russia says there isn't any available history for the past 180 days.", ['pair' => Config::$currencyExchangeRate->getPair()]);
                return null;
            }

            $prices = [];

            foreach ($document->Record as $record) {
                $prices[] = $getPriceByElement($record);
            }

            return $sortAndBuildPairHistory($counterCurrency, $baseCurrency, $prices);
        };

        $counterCurrencyHistory = $getPairHistory($this->defaultISOCode, Config::$currencyExchangeRate->counterCurrency, $counterCurrencyHistoryDocument);

        if ($counterCurrencyHistory === null) {
            return null;
        }

        if (Config::$currencyExchangeRate->baseCurrency === $this->defaultISOCode) {
            return [$counterCurrencyHistory];
        } else {
            $baseCurrencyHistoryDocument = $this->makeRequest($url, $query($weekBeforeRequestedDate, $requestedDate, Config::$currencyExchangeRate->baseCurrencyInfo->id));

            $baseCurrencyHistory = $getPairHistory($this->defaultISOCode, Config::$currencyExchangeRate->baseCurrency, $baseCurrencyHistoryDocument);

            if ($baseCurrencyHistory === null) {
                return null;
            }

            $prices = [];
            for ($i = 0; $i < count($baseCurrencyHistory); $i++) {
                $base = $baseCurrencyHistory[$i];
                $counter = $counterCurrencyHistory[$i];

                if ($base->getDateTime() != $counter->getDateTime()) {
                    // @TODO: It can be possible to get the closest rate, load the more rates, and we need to go deeper... Maybe
                    $this->logger->critical("An error occurred while generating the {pair} currency pair rate. It is not possible to obtain the rate because it requires the rates for the default RUR currency to be obtained simultaneously.", ['pair' => $counterCurrencyHistory->getPair()]);
                }

                $value = $counter->getValue() / $base->getValue();

                $prices[] = new Price($value, $base->getDateTime());
            }

            $crossPairHistory = $sortAndBuildPairHistory($counterCurrencyHistory->getBaseCurrency(), $baseCurrencyHistory->getBaseCurrency(), $prices);

            return [$counterCurrencyHistory, $crossPairHistory, $baseCurrencyHistory];
        }
    }

    protected function makeRequest(string $url, array $query, string $method = 'GET'): ?\SimpleXMLElement
    {
        try {
            $response = $this->httpClient->request($method, $url, [
                'query' => $query
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new TransportException("The server didn't respond with an 200 status. Instead, we received: {$response->getStatusCode()}");
            }

            $rawXmlResponse = $response->getContent();

            $xmlResponse = simplexml_load_string($rawXmlResponse);

            if ($xmlResponse === false) {
                $this->logger->critical("[{url}]: Expect to be an xml document, but got: {stripped_response}", [
                    'url' => $response->getInfo()['url'],
                    'stripped_response' => substr($rawXmlResponse, 0, 10) . ' ... ' . substr($rawXmlResponse, -10)
                ]);

                return null;
            }

            return $xmlResponse;
        } catch (TransportExceptionInterface $e) {
            $this->logger->critical("Oops! Looks like we couldn't fulfill the request to the Cbr for the following reason: {reason}.", ['reason' => $e->getMessage()]);

            return null;
        }
    }
}