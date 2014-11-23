<?php

namespace Application\Demo;

use Wrench\Util\Application;

/**
 * Example application for Wrench: echo server
 */
class DemoApplication extends Application
{
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