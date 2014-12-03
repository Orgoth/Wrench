<?php

namespace Wrench\Util;

use Wrench\Connection;
use Wrench\Server;
use Wrench\Protocol\Protocol;

/**
 * Wrench Server Application
 */
abstract class Application
{
    protected $events = [];
    protected $eventManager;
    protected $routes;
    
    /**
     * Send an event to the event Handler
     * 
     * @param string $type
     * @param array $data
     */
    public function onNotification($type, $data)
    {
        if(isset($this->events[$type]))
        {
            $this->eventManager->{$this->events[$type]}($data);
        }
    }

    public function onData($data, Connection $client)
    {
        $payload = json_decode($data->getPayload());
        if(method_exists($this, $payload->action))
        {
            $this->{$payload->action}($client, $payload);
            return true;
        }
        $client->close(Protocol::CLOSE_DATA_INVALID);
    }
    
    public function __construct()
    {
        $this->configureRouting();
        $this->setEventManager();
    }
    
    public function _sendTemplate(Connection $client, $payload)
    {
        $server = Server::getInstance();
        $pathParts = explode('/', $payload->path);
        array_shift($pathParts);
        $applicationName = array_shift($pathParts);
        $route = (count($pathParts) > 0) ? implode('/', $pathParts) : null;
        
        if(($application = $server->getApplication($applicationName)) === false)
        {
            $client->close(Protocol::CLOSE_DATA_INVALID);
        }
        
        if(($server->getRouter()->hasRoute($applicationName, $route)) === false)
        {
            $client->close(Protocol::CLOSE_DATA_INCONSISTENT);
        }
        
        
        $client->send($payload->path);
    }
    
    abstract public function setEventManager();
        
    protected function _decodeData($data)
    {
        $decodedData = json_decode($data, true);
        if($decodedData === null)
        {
                return false;
        }

        if(isset($decodedData['action'], $decodedData['data']) === false)
        {
                return false;
        }

        return $decodedData;
    }

    protected function _encodeData($action, $data)
    {
        if(empty($action))
        {
                return false;
        }

        $payload = array(
                'action' => $action,
                'data' => $data
        );

        return json_encode($payload);
    }
}
