<?php

namespace CbrPuller\Renderer;

class Cli extends AbstractRenderer
{

    function render(DataBag $data): bool
    {
        if (is_array($data->data)) {
            $this->io->table([], $this->datesToString($data->data));

            return true;
        }

        $this->io->writeln($data->data);

        return true;
    }
}