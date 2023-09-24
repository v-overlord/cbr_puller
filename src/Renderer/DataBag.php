<?php

namespace CbrPuller\Renderer;

class DataBag
{
    public array|string $data = '';

    public int $flags = 0;

    /**
     * @param array|string $data
     * @param int $flags
     */
    public function __construct(array|string $data, int $flags = 0)
    {
        $this->data = $data;
        $this->flags = $flags;
    }
}