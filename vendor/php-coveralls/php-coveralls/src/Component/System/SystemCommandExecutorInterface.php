<?php

namespace PhpCoveralls\Component\System;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
interface SystemCommandExecutorInterface
{
    /**
     * Execute command.
     *
     * @param string $command
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    public function execute($command);
}
