<?php

namespace Wrench\Util;

/**
 * Wrench Server Application
 */
abstract class Application
{
    /**
     * Optional: handle a connection
     */
    // abstract public function onConnect($connection);

    /**
     * Optional: handle a disconnection
     *
     * @param
     */
	// abstract public function onDisconnect($connection);

    /**
     * Optional: allow the application to perform any tasks which will result in a push to clients
     */ 
    // abstract public function onUpdate();

    /**
     * Handle data received from a client
     *
     * @param Payload $payload A payload object, that supports __toString()
     * @param Connection $connection
     */
    abstract public function onData($payload, $connection);
        
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
