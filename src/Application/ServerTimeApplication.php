<?php

namespace Application;

use Wrench\Util\Application;

/**
 * Example application demonstrating how to use Application::onUpdate
 *
 * Pushes the server time to all clients every update tick.
 */
class ServerTimeApplication extends Application
{
    protected $clients = array();
    protected $lastTimestamp = null;

    /**
     * @see Wrench\Util.Application::onConnect()
     */
    public function onConnect($client)
    {
        $this->clients[] = $client;
    }
    
    /**
     * @see Wrench\Util.Application::onDisconnect()
     */
    public function onDisconnect($connection) {
        unset($this->clients[$connection->getPort()]);
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
    
    /**
     * @see Wrench\Util.Application::onNotification()
     */
    public function onNotification() {
        // limit updates to once per second
        if(time() > $this->lastTimestamp) {
            $this->lastTimestamp = time();

            foreach ($this->clients as $sendto) {
                $sendto->send(date('d-m-Y H:i:s'));
            }
        }
    }
}
