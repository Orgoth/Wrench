<?php

namespace Application;

use Wrench\Util\Application;

/**
 * Example application for Wrench: echo server
 */
class EchoApplication extends Application
{
    /**
     * @see Wrench\Util\Application::onData()
     */
    public function onData($data, $client)
    {
        $client->send($data);
    }
}