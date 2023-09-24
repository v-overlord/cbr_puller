<?php

namespace CbrPuller\Queue;

use Psr\Log\LoggerInterface;
use RdKafka\Topic;

class Producer
{
    protected \RdKafka\Producer $producer;
    protected Topic $topic;
    protected Broker $broker;

    protected LoggerInterface $logger;

    public const DESTRUCT_FLUSH_TIMEOUT_MS = 30_000;

    public function __construct(Broker $broker, LoggerInterface $logger)
    {
        $this->broker = $broker;
        $this->logger = $logger;

        $this->createBrokerProducer();
    }

    protected function createBrokerProducer(): void
    {
        $this->producer = new \RdKafka\Producer($this->broker->getKafkaConf());
        $this->topic = $this->producer->newTopic($this->broker->getConfig()['topic']);
    }

    public function __destruct()
    {
        $this->producer->flush(self::DESTRUCT_FLUSH_TIMEOUT_MS);
    }

    public function produce(string $message): void
    {
        $this->logger->info('Trying to send the rates to the topic: "{topic}"', [
            'topic' => $this->topic->getName(),
        ]);

        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
    }
}