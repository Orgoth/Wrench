<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Server;

/**
 * Shiny WSS Status Application
 * Provides live server infos/messages to client/browser.
 *
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
class StatusApplication extends Application
{
    private $_clients           = array();
    private $_serverClients     = array();
    private $_serverInfo        = array();
    private $_serverClientCount = 0;

    /**
     * @param Connection $client
     */
    public function onConnect($client)
    {
        $this->_clients[$client->getId()] = $client;
        $this->clientConnected($client->getIp(), $client->getPort());
        $this->_sendServerInfo($client);
    }

    /**
     * @param Connection $client
     */
    public function onDisconnect($client)
    {
        $this->clientDisconnected($client->getIp(), $client->getPort());
        unset($this->_clients[$client->getId()]);
    }

    public function onData($data, $client)
    {
        // currently not in use...
    }

    public function setServerInfo($serverInfo)
    {
        if (is_array($serverInfo)) {
            $this->_serverInfo = $serverInfo;
            return true;
        }
        return false;
    }

    public function clientConnected($ip, $port)
    {
        $this->_serverClients[$port] = $ip;
        $this->_serverClientCount++;
        $this->statusMsg('Client connected: ' . $ip . ':' . $port);

        $data = array(
            'ip' => $ip,
            'port' => $port,
            'clientCount' => $this->_serverClientCount,
        );

        $encodedData = $this->_encodeData('clientConnected', $data);

        $this->_sendAll($encodedData);
    }

    public function clientDisconnected($ip, $port)
    {
        if (!isset($this->_serverClients[$port])) {
            return false;
        }

        unset($this->_serverClients[$port]);

        $this->_serverClientCount--;
        $this->statusMsg('Client disconnected: ' . $ip . ':' . $port);

        $data = array(
            'port' => $port,
            'clientCount' => $this->_serverClientCount,
        );

        $encodedData = $this->_encodeData('clientDisconnected', $data);

        $this->_sendAll($encodedData);
    }

    public function clientActivity($port)
    {
        $encodedData = $this->_encodeData('clientActivity', $port);
        $this->_sendAll($encodedData);
    }

    public function statusMsg($text, $type = 'info')
    {
        $data = array(
            'type' => $type,
            'text' => '[' . strftime('%m-%d %H:%M', time()) . '] ' . $text,
        );

        $encodedData = $this->_encodeData('statusMsg', $data);

        $this->_sendAll($encodedData);
    }

    private function _sendServerInfo($client)
    {
        if (count($this->_clients) < 1) {
            return false;
        }
        
        $serverOptions = Server::getInstance()->getOptions();
        
        $client->send($this->_encodeData('serverInfo', [
            'clientCount'           => $this->_serverClientCount,
            'clients'               => $this->_serverClients,
            'maxClients'            => $serverOptions['maxClients'],
            'maxConnections'        => $serverOptions['maxConnections'],
            'maxRequestsPerMinute'  => $serverOptions['maxRequestsPerMinute']
        ]));
    }

    private function _sendAll($encodedData)
    {
        if (count($this->_clients) < 1) {
            return false;
        }

        foreach ($this->_clients as $sendto) {
            $sendto->send($encodedData);
        }
    }
}
