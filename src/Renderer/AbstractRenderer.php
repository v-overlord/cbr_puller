<?php

namespace CbrPuller\Renderer;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractRenderer
{
    public SymfonyStyle $io;
    public LoggerInterface $logger;

    public string $toStringDateFormat = 'd/m/Y';

    public function __construct(SymfonyStyle $io, LoggerInterface $logger)
    {
        $this->io = $io;
        $this->logger = $logger;
    }

    public static function getRendererByType(string $rendererType, SymfonyStyle $io, LoggerInterface $logger): AbstractRenderer
    {
        if ($rendererType === 'cli') {
            return new Cli($io, $logger);
        } elseif ($rendererType === 'json' ) {
            return new Json($io, $logger);
        }

        return new Cli($io, $logger);
    }

    protected function datesToString(array $data): array
    {
        foreach ($data as &$datum) {
            $datum['date'] = $datum['date']->format($this->toStringDateFormat);
        }

        return $data;
    }

    abstract function render(DataBag $data): bool;
}