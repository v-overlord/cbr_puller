<?php

namespace CbrPuller\Renderer;

class Json extends AbstractRenderer
{

    function render(DataBag $data): bool
    {
        if (is_array($data->data)) {
            $this->io->writeln(json_encode($this->datesToString($data->data), $data->flags));

            return true;
        }

        $this->io->writeln(json_encode([
            'result' => $data->data
        ], $data->flags));

        return true;
    }
}