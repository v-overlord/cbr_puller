<?php

namespace CbrPuller\Worker;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractWorker
{
    public bool $run = false;
    public int $timeout = 1;

    /**
     * @throws \Exception
     */
    public function __construct(public ContainerInterface $container, public LoggerInterface $logger)
    {
        $this->bindSignals();
    }

    public function start(): void
    {
        $this->run = true;
    }

    public function stop(): void
    {
        $this->run = false;
    }

    /**
     * @throws \Exception
     */
    public function bindSignals(): void
    {
        foreach ($this->getSignalsMap() as $signal => $callback) {
            if (!is_callable($callback)) {
                $errorMessage = sprintf('The callback function "%s" not found!', $callback[1]);
                $this->logger->critical($errorMessage);

                throw new Exception($errorMessage);
            }

            \pcntl_signal($signal, $callback);
        }

        // We only require the major/minor version as PHP 7.1 introduced pcntl_async_signals, which greatly improves signal handling via asynchronous operations.
        if ((float) phpversion() > 7.1) {
            pcntl_async_signals(true);
        } else {
            declare(ticks = 1);
        }

        \pcntl_signal_dispatch();
    }

    /**
     * @return array<int, callable>
     */
    private function getSignalsMap(): array
    {
        return [
            SIGINT => [$this, 'stop'],  // CTRL-C
            SIGQUIT => [$this, 'stop'], // CTRL-\
            SIGTERM => function () {    // This is the default signal used by the kill command
                $this->logger->info("{worker}: Terminated!", ['worker' => get_class($this)]);

                $this->stop();
            }
        ];
    }
}