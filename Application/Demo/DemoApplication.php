<?php

namespace Application\Demo;

use Wrench\Util\Application;
use Wrench\Interfaces\ApplicationRouting;
use Wrench\Connection;
use Wrench\Server;

/**
 * Example application for Wrench: echo server
 */
class DemoApplication extends Application implements ApplicationRouting
{
    /**
     * @see Wrench\Util\Application::onData()
     */
    public function onData($data, Connection $client)
    {
        $client->send($data);
    }
    
    public function setEventManager()
    {
        
    }
    
    public function configureRouting()
    {
        $this->routes = Server::getInstance()->getRouter()->load('Demo', 'routing.json');
    }
}