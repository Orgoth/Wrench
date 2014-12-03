<?php

namespace Application\Status;

use Wrench\Util\Application;
use Wrench\Connection;
use Wrench\Protocol\Protocol;
use Wrench\Server;

use Wrench\Exception\HandshakeException;

use Application\Status\Events\StatusEvents;

/**
 * Shiny WSS Status Application
 * Provides live server infos/messages to client/browser.
 *
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
class StatusApplication extends Application
{
    protected $events = [
        'socket_connect'    => 'clientConnected',
        'socket_disconnect' => 'clientDisconnected'
    ];
    
    public function setEventManager()
    {
        $this->eventManager = new StatusEvents($this);
    }
    
    public function configureRouting()
    {
        $this->routes = Server::getInstance()->getRouter()->load('Status', 'routing.json');
    }
    
    public function shutdown($client, $data)
    {
        Server::getInstance()->shutdown();
    }

    public function clientConnected($ip, $port)
    {
        $this->statusMsg('Client connected: ' . $ip . ':' . $port);
        
        $serverMemory = Server::getInstance()->getMemoryManager();

        $this->_sendAll($this->_encodeData('clientConnected', [
            'ip'            => $ip,
            'port'          => $port,
            'clientCount'   => count(Server::getInstance()
                               ->getConnectionManager()
                               ->getConvertedConnections()),
            'currentMemory' => $serverMemory->getCurrentMemory(),
            'maxMemory'     => $serverMemory->getMaxMemory()
        ]));
    }

    public function clientDisconnected($ip, $port)
    {
        $this->statusMsg('Client disconnected: ' . $ip . ':' . $port);
        
        $serverMemory = Server::getInstance()->getMemoryManager();

        $this->_sendAll($this->_encodeData('clientDisconnected', [
            'port'          => $port,
            'clientCount'   => count(Server::getInstance()
                               ->getConnectionManager()
                               ->getConvertedConnections()),
            'currentMemory' => $serverMemory->getCurrentMemory(),
            'maxMemory'     => $serverMemory->getMaxMemory()
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
    
    public function _sendServerInfo($client = null)
    {
        $server = Server::getInstance();
        
        $serverOptions = $server->getRateLimiter()->getOptions();
        $serverClients = $server->getConnectionManager()->getConvertedConnections();
        $serverMemory = $server->getMemoryManager();
        
        $encodedData = $this->_encodeData('serverInfo', [
            'clientCount'           => count($serverClients),
            'maxClients'            => $serverOptions['connections'],
            'maxConnections'        => $serverOptions['connections_per_ip'],
            'maxRequestsPerMinute'  => $serverOptions['requests_per_minute'],
            'currentMemory'         => $serverMemory->getCurrentMemory(),
            'maxMemory'             => $serverMemory->getMaxMemory()
        ]);
        
        if($client === null)
        {
            $this->_sendAll($encodedData);
            return true;
        }
        $client->send($encodedData);
        return true;
    }

    public function _sendAll($encodedData)
    {
        $clients = Server::getInstance()->getConnectionManager()->getConnections();
        foreach ($clients as $sendto)
        {
            try
            {
                $sendto->send($encodedData);
            }
            catch (HandshakeException $ex)
            {
                continue;
            }
        }
    }
}
