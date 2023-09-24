<?php

namespace CbrPuller\Puller;

use CbrPuller\VO\PairHistory;

abstract class AbstractPuller
{
    /**
     * @return ?array<PairHistory>
     */
    abstract public function fetch(): ?array;
}