<?php

namespace GearmanSaga;

final class GearmanBatch
{
    private $commands;

    public function __construct(array $commands)
    {
        $this->commands = $commands;
    }

    public function getCommands()
    {
        return $this->commands;
    }
}
