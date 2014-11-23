<?php

namespace Application\ServerTime;

use Wrench\Util\Application;

/**
 * Example application demonstrating how to use Application::onUpdate
 *
 * Pushes the server time to all clients every update tick.
 */
class ServerTimeApplication extends Application
{
    protected $lastTimestamp = null;

    public function setEventManager()
    {
        
    }
    /**
     * Handle data received from a client
     *
     * @param Payload    $payload A payload object, that supports __toString()
     * @param Connection $connection
     */
    public function onData($payload, $connection)
    {
        return;
    }
}
