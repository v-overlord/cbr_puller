<?php

namespace CbrPuller\Command;

use CbrPuller\Config;
use CbrPuller\Service\PublishBroker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

// @TODO: Why not experiment with using AMPHP or fibers to retrieve all rates? Maybe add some proxies? Well, you know what? YAGNI and APO for now, let's keep it simple, st... superstar!
#[AsCommand(name: 'publish', description: 'This command retrieves the rates of all currencies and publishes them to the Kafka instance.')]
class Publisher extends AbstractCommand
{
    protected function do(): int
    {
        Config::$currencyExchangeRate->setHistory($this->history);

        $history = $this->getHistoryData(Config::$currencyExchangeRate->getHistory()->getDateSplice(to: Config::$date, length: null));

        /**
         * @var PublishBroker $publishBrokerService
         */
        $publishBrokerService = $this->container->get(PublishBroker::class);

        if ($publishBrokerService->publish($history)) {
            $this->io->success("Sent");

            return Command::SUCCESS;
        } else {
            $this->io->error("Something wrong, see the log above");

            return Command::FAILURE;
        }
    }
}