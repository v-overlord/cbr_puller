<?php

namespace CbrPuller;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Bramus\Monolog\Formatter\ColorSchemes\TrafficLight;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// @TODO: Should we use OpenTelemetry with Monolog? Not today...
class Logger
{
    public const LOGGER_NAME = 'CbrPullerLogger';

    public const DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    public const OUTPUT_MESSAGE_FORMAT = "[%datetime%] %channel%->%level_name%: %message%";

    public static ?MonologLogger $logger = null;
    public static ?ContainerInterface $container = null;

    private static array $verbosityLevelMap = [
        OutputInterface::VERBOSITY_QUIET => Level::Error,
        OutputInterface::VERBOSITY_NORMAL => Level::Warning,
        OutputInterface::VERBOSITY_VERBOSE => Level::Notice,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Level::Info,
        OutputInterface::VERBOSITY_DEBUG => Level::Debug,
    ];

    public static function build(ContainerInterface $container): MonologLogger
    {
        self::$container = $container;

        self::buildMonologLogger();

        return self::$logger;
    }

    public static function buildMonologLogger(): void
    {
        $verbosityLevel = Config::$verbosityLevel;

        self::$logger = new MonologLogger(self::LOGGER_NAME);

        self::$logger->pushProcessor(new PsrLogMessageProcessor(self::DATE_FORMAT));

        $streamHandler = new StreamHandler('php://stdout', $verbosityLevel ? self::$verbosityLevelMap[$verbosityLevel] : Level::Debug);

        $formatter = new ColoredLineFormatter(new TrafficLight(), self::OUTPUT_MESSAGE_FORMAT);
        $streamHandler->setFormatter($formatter);

        self::$logger->pushHandler($streamHandler);

        self::$container?->set(LoggerInterface::class, self::$logger);
    }
}