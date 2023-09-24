<?php

namespace CbrPuller\Command;

use CbrPuller\Config;
use CbrPuller\Renderer\DataBag;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'pull', description: 'This command fetches the cross rate for the specified currency from the CBR, using the base currency (default RUR).')]
class SinglePuller extends AbstractCommand
{
    protected function do(): int
    {
        Config::$currencyExchangeRate->setHistory($this->history);

        $history = $this->getHistoryData(Config::$currencyExchangeRate->getHistory()->getDateSplice(to: Config::$date, length: 2));

        if (count($history) > 0) {
            $interval = Config::$date->diff($history[0]['date']);

            // Yoda conditions, use when checking a variable against an expression, to avoid inside the condition statement, accidental assignment.
            if (0 !== $interval->days && Config::$exactDate) {
                $pair = Config::$currencyExchangeRate->getPair();
                $this->renderer->render(new DataBag("The rate for '$pair' on the requested date could not be found."));

                return Command::FAILURE;
            }

            if (0 !== $interval->days) {
                $this->logger->info("On the requested date the price not found, there is the closest to the date that the bank have:");
            }
        }

        $this->logger->info("This is the result of your request:");

        $this->renderer->render(new DataBag($history, JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}