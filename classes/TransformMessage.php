<?php

namespace CollectLogsModule;

interface TransformMessage
{

    /**
     * @param bool $force
     *
     * @return void
     */
    public function synchronize(bool $force = false);

    /**
     * @param string $message
     *
     * @return string
     */
    public function transform(string $message): string;
}