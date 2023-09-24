<?php

namespace CbrPuller\Cache;

abstract class AbstractCache
{
    protected string $cacheName = 'Abstract cache';

    abstract public function get(string $key): mixed;

    abstract public function set(string $key, mixed $value, ?int $ttl = null): bool;

    abstract public function exists(string $key): bool;

    abstract public function reset(): bool;

    /**
     * @throws \Exception
     */
    public function tryCatch(callable $callback, bool $isBubblingNeeded = false): mixed
    {
        try {
            $result = $callback();
        } catch (\Exception $exception) {
            $this->logger->critical("$this->cacheName: An exception has been thrown: {message}", ['message' => $exception->getMessage()]);

            if ($isBubblingNeeded) {
                throw $exception;
            }

            return null;
        }

        return $result;
    }
}