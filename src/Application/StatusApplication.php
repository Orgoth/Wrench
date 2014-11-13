<?php

namespace Application;

use Wrench\Util\Application;
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
    private $_serverInfo        = array();

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
        $this->statusMsg('Client connected: ' . $ip . ':' . $port);

        $this->_sendAll($this->_encodeData('clientConnected', [
            'ip'          => $ip,
            'port'        => $port,
            'clientCount' => count(Server::getInstance()
                             ->getConnectionManager()
                             ->getConvertedConnections()),
        ]));
    }

    public function clientDisconnected($ip, $port)
    {
        $this->statusMsg('Client disconnected: ' . $ip . ':' . $port);

        $this->_sendAll($this->_encodeData('clientDisconnected', [
            'port'          => $port,
            'clientCount'   => count(Server::getInstance()
                               ->getConnectionManager()
                               ->getConvertedConnections())
        ]));
    }

    public function clientActivity($port)
    {
        $this->_sendAll($this->_encodeData('clientActivity', $port));
    }

    public function statusMsg($text, $type = 'info')
    {
        $this->_sendAll($this->_encodeData('statusMsg', [
            'type' => $type,
            'text' => '[' . strftime('%m-%d %H:%M', time()) . '] ' . $text,
        ]));
    }

    private function _sendServerInfo($client)
    {
        $server = Server::getInstance();
        
        $serverOptions = $server->getOptions();
        $serverClients = $server->getConnectionManager()->getConvertedConnections();
        
        $client->send($this->_encodeData('serverInfo', [
            'clientCount'           => count($serverClients),
            'clients'               => $serverClients,
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
