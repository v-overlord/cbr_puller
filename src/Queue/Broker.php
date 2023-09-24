<?php

namespace CbrPuller\Queue;

use CbrPuller\Config;
use Psr\Log\LoggerInterface;
use RdKafka\Conf;
use Symfony\Component\Console\Output\OutputInterface;

class Broker
{
    protected ?Conf $kafkaConf = null;

    /**
     * @var array{host: ?string, port: ?int, topic: ?string, timeout: ?int}
     */
    protected array $config = [
        'host' => null,
        'port' => null,
        'topic' => null,
        'timeout' => null
    ];

    private array $verbosityLevelMap = [
        OutputInterface::VERBOSITY_QUIET => LOG_CRIT,
        OutputInterface::VERBOSITY_NORMAL => LOG_ERR,
        OutputInterface::VERBOSITY_VERBOSE => LOG_WARNING,
        OutputInterface::VERBOSITY_VERY_VERBOSE => LOG_NOTICE,
        OutputInterface::VERBOSITY_DEBUG => LOG_DEBUG,
    ];

    private int $defaultVerbosityLevel = LOG_NOTICE;

    public function __construct(public LoggerInterface $logger)
    {
        $this->setUpConfig();
    }

    public function setUpConfig(): void
    {
        $this->fetchKafkaValues();

        if ($this->config['host'] === null
            || $this->config['port'] === null
            || $this->config['topic'] === null
            || $this->config['timeout'] === null
        ) {
            $this->logger->critical("The configuration of the kafka variables has encountered an issue, resulting in the unavailability of the kafka instance.");

            foreach ($this->config as $cfgName => $cfgValue) {
                $this->logger->error("$cfgName: $cfgValue");
            }

            return;
        }

        $this->kafkaConf = new Conf();

        $this->kafkaConf->set('metadata.broker.list', sprintf("%s:%d", $this->config['host'], $this->config['port']));
        $this->kafkaConf->set('log_level', $this->getVerbosityLevel());
    }

    /**
     * @TODO: Perhaps consider separating the values into a separate class?
     */
    private function fetchKafkaValues(): void
    {
        $this->config = [
            "host" => $_ENV['KAFKA_HOST'] ?? null,
            "port" => $_ENV['KAFKA_PORT'] ?? null,
            "topic" => $_ENV['KAFKA_TOPIC'] ?? null,
            "timeout" => $_ENV['KAFKA_TIMEOUT'] ?? null,
        ];
    }

    public function getConsumer(): ?Consumer
    {
        if ($this->kafkaConf === null) {
            $this->logger->critical("Configuration issue preventing consumer instance, check log above.");
            return null;
        }

        return new Consumer($this, $this->logger);
    }

    public function getProducer(): ?Producer
    {
        if ($this->kafkaConf === null) {
            $this->logger->critical("Configuration issue preventing producer instance, check log above.");
            return null;
        }

        return new Producer($this, $this->logger);
    }

    /**
     * @return ?Conf
     */
    public function getKafkaConf(): ?Conf
    {
        return $this->kafkaConf;
    }

    /**
     * @return array{host: ?string, port: ?int, topic: ?string, timeout: ?int}
     */
    public function getConfig(): array
    {
        return $this->config;
    }


    private function getVerbosityLevel(): string
    {
        return (string) $this->verbosityLevelMap[Config::$verbosityLevel] ?? $this->defaultVerbosityLevel;
    }
}