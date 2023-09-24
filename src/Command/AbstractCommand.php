<?php

namespace CbrPuller\Command;

use CbrPuller\ADT\Price;
use CbrPuller\Config;
use CbrPuller\Logger;
use CbrPuller\Renderer\AbstractRenderer;
use CbrPuller\Service\FetcherRate;
use CbrPuller\Validator\Exception;
use CbrPuller\Validator\Validator;
use CbrPuller\VO\CurrencyExchangeRate;
use CbrPuller\VO\PairHistory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

// @TODO: You gotta use the translation tool, like the symfony/translation.
// @TODO: Perhaps it would be a good idea to use templates.

abstract class AbstractCommand extends Command
{
    public LoggerInterface $logger;

    protected ?InputInterface $input = null;
    protected ?OutputInterface $output = null;
    protected ?SymfonyStyle $io = null;

    protected ?AbstractRenderer $renderer;

    protected ?PairHistory $history = null;

    public int $precision = 5;

    public function __construct(public ContainerInterface $container)
    {
        $this->setUpLogger();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('currency', InputArgument::REQUIRED, 'What currency are you requesting a rate for?')
            ->addArgument('date', InputArgument::REQUIRED, 'A date for which you would like to fetch the rates information [yyyy-mm-dd]')

            ->addOption('base-currency', 'b', InputOption::VALUE_OPTIONAL, "What is the base currency for which you would like to obtain a cross rate?", Config::$defaultBaseCurrency)
            ->addOption('renderer', 'd', InputOption::VALUE_OPTIONAL, "Here, configure the renderer that will produce the output format, which can be either 'cli' or 'json'.", 'cli')
            ->addOption('no-cache', 'c', InputOption::VALUE_NONE, "Enabling this option will prevent the use of the cache and force the retrieval of the currency rate from the remote source.")
            ->addOption('reset-cache', 'r', InputOption::VALUE_NONE, "By selecting this option, the cache will be reset.")
            ->addOption('exact-date', 'e', InputOption::VALUE_NONE, "You can use this option to fetch the exchange rate specifically for an exact date, with the default being to select the latest available date.")
        ;
    }

    protected function setUpInput(InputInterface $input, OutputInterface $output): bool
    {
        $inputValues = [
            'currency' => mb_strtoupper($input->getArgument('currency')),
            'date' => mb_strtoupper($input->getArgument('date')),
            'base-currency' => mb_strtoupper($input->getOption('base-currency')),
            'no-cache' => $input->getOption('no-cache'),
            'reset-cache' => $input->getOption('reset-cache'),
            'exact-date' => $input->getOption('exact-date'),
            'renderer' => mb_strtolower($input->getOption('renderer')),
        ];

        try {
            (new Validator())->validate($inputValues);
        } catch (Exception $exception) {
            $numOfIndent = 4;
            $indentStr = str_repeat(' ', $numOfIndent);

            $this->logger->critical("Validation error!");

            foreach ($exception->errorMessages as $key => $messages) {
                $this->logger->critical("Key '$key' has these errors (received value: $inputValues[$key]):");

                foreach ($messages as $message) {
                    $this->logger->critical("$indentStr$message");
                }
            }

            return false;
        }

        $currencyExchangeRate = new CurrencyExchangeRate($inputValues['base-currency'], $inputValues['currency']);

        if ($currencyExchangeRate->baseCurrency == $currencyExchangeRate->counterCurrency) {
            $this->logger->critical("Both currencies are equivalent [{currency}], the early response is [{rate}].", [
                'currency' => $currencyExchangeRate->baseCurrency,
                'rate' => 1
            ]);

            return false;
        }

        if ($inputValues['no-cache']) {
            Config::$useCache = false;
        }

        Config::$currencyExchangeRate = $currencyExchangeRate;
        Config::$resetCache = $inputValues['reset-cache'];
        Config::$date = new \DateTimeImmutable($inputValues['date']);
        Config::$exactDate = $inputValues['exact-date'];
        Config::$rendererType = $inputValues['renderer'];

        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Config::$verbosityLevel = $output->getVerbosity();

        $this->setUpLogger();

        if (!$this->setUpInput($input, $output)) {
            return Command::INVALID;
        }

        if (!$this->setUpOutput($input, $output)) {
            return Command::INVALID;
        }

        $this->logger->debug("I'm attempting to retrieve the rate for {pair}...", ['pair' => Config::$currencyExchangeRate->getPair()]);

        /**
         * @var FetcherRate $fetcherRateService
         */
        $fetcherRateService = $this->container->get(FetcherRate::class);

        $history = $fetcherRateService->enrich();
        if ($history === null) {
            $this->logger->critical("Can't fetch the price for {pair}", ['pair' => Config::$currencyExchangeRate->getPair()]);

            return Command::FAILURE;
        }

        $this->history = $history;

        return $this->do();
    }

    private function setUpOutput(InputInterface $input, OutputInterface $output): bool
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $this->renderer = AbstractRenderer::getRendererByType(Config::$rendererType, $this->io, $this->logger);
        return true;
    }

    private function setUpLogger(): void
    {
        Logger::buildMonologLogger();

        $this->logger = Logger::$logger;
    }

    /**
     * @param PairHistory $history
     * @param int $length
     * @return array<array{host: ?string, port: ?int, topic: ?string, timeout: ?int}>
     */
    protected function getHistoryData(PairHistory $history, int $length = 0): array
    {
        $currencyExchangeRate = Config::$currencyExchangeRate;

        if ($length !== 0 && $length >= count($history)) {

            $this->logger->warning("You are trying to request {length} items from the history, but the maximum allowed is {max}, shrink to the maximum available size.", [
                'length' => $length,
                'max' => count($history)
            ]);

            $length = count($history);
        }

        if ($length === 0) {
            $length = count($history);
        }

        $result = [];

        for($i = 0; $i < $length; $i++) {
            /**
             * @var Price $historyItem
             */
            $historyItem = $history[$i];

            // @TODO: Reminder: Create a value object for this item.
            $result[] = [
                "date" => $historyItem->getDateTime(),
                "pair" => $currencyExchangeRate->getPair(),
                "rate" => $historyItem->getDirectQuotation(),
                "rateDifference" => isset($history[$i + 1]) ? round($historyItem->getDirectQuotation() - $history[$i + 1]->getDirectQuotation(), $this->precision) : 0,
                "swappedPair" => $currencyExchangeRate->getSwappedPair(),
                "swappedPairRate" => $historyItem->getIndirectQuotation(),
                "swappedPairRateDifference" => isset($history[$i + 1]) ? round($historyItem->getIndirectQuotation() - $history[$i + 1]->getIndirectQuotation(), $this->precision) : 0,
            ];
        }

        return $result;
    }

    abstract protected function do(): int;
}