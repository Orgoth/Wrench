<?php

namespace Application;

use Wrench\Util\Application;

/**
 * Example application for Wrench: echo server
 */
class EchoApplication extends Application
{
    public function onConnect($connection){}
    
    public function onDisconnect($connection){}
    
    /**
     * @see Wrench\Util\Application::onData()
     */
    public function onData($data, $client)
    {
        $client->send($data);
    }
    
    public function setEventManager()
    {
        
    }
}