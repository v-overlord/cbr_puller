<?php

namespace CbrPuller\Queue;

use Psr\Log\LoggerInterface;
use RdKafka\Message;

class Consumer
{
    private \RdKafka\KafkaConsumer $consumer;
    private int $timeout = 10;
    protected Broker $broker;

    protected LoggerInterface $logger;

    public function __construct(Broker $broker, LoggerInterface $logger)
    {
        $this->broker = $broker;
        $this->logger = $logger;

        $this->createBrokerConsumer();
    }

    protected function createBrokerConsumer(): void
    {
        $this->consumer = new \RdKafka\KafkaConsumer($this->broker->getKafkaConf());
        $this->timeout = 1000 * $this->broker->getConfig()['timeout'];

        $this->consumer->subscribe($this->broker->getConfig()['topic']);
    }

    public function __destruct()
    {
        $this->consumer->close();
    }

    public function getNextMessage(): Message
    {
        return $this->consumer->consume($this->timeout);
    }
}