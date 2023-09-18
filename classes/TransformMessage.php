<?php

namespace CollectLogsModule;

interface TransformMessage
{

    /**
     * @return void
     */
    public function synchronize();

    /**
     * @param string $message
     *
     * @return string
     */
    public function transform(string $message): string;
}