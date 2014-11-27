<?php

namespace Wrench\Util;

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

    /**
     * Handle data received from a client
     *
     * @param Payload $payload A payload object, that supports __toString()
     * @param Connection $connection
     */
    abstract public function onData($payload, $connection);
    
    public function __construct()
    {
        $this->configureRouting();
        $this->setEventManager();
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
