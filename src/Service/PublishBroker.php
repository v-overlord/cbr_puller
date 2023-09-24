<?php

namespace CbrPuller\Service;

use CbrPuller\Queue\Broker;
use Psr\Log\LoggerInterface;

class PublishBroker
{
    public function __construct(public LoggerInterface $logger)
    {
    }

    public function publish(array $history): bool
    {
        $broker = new Broker($this->logger);
        $producer = $broker->getProducer();
        $producer->produce(json_encode($history, JSON_PRETTY_PRINT));

        return true;
    }
}