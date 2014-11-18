<?php

namespace Wrench\Listener;

use Wrench\Util\Configurable;
use Wrench\Server;

class RateLimiter extends Configurable implements Listener
{
    /**
     * Connection counts per IP address
     *
     * @var array<int>
     */
    protected $ips = [];

    /**
     * Request tokens per IP address
     *
     * @var array<array<int>>
     */
    protected $requests = [];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->configure();
    }

    /**
     * @param array $options
     */
    protected function configure()
    {
        parent::configureOptions(array_merge([
            'connections'         => 200, // Total
            'connections_per_ip'  => 5,   // At once
            'requests_per_minute' => 200  // Per connection
        ], $this->options));
    }

    /**
     * @see Wrench\Listener.Listener::listen()
     */
    public function listen()
    {
        $server = Server::getInstance();
        
        $server->addListener(
            Server::EVENT_SOCKET_CONNECT,
            array($this, 'onSocketConnect')
        );

        $server->addListener(
            Server::EVENT_SOCKET_DISCONNECT,
            array($this, 'onSocketDisconnect')
        );

        $server->addListener(
            Server::EVENT_CLIENT_DATA,
            array($this, 'onClientData')
        );
    }

    /**
     * Event listener
     *
     * @param Connection $connection
     */
    public function onSocketConnect($connection)
    {
        $this->checkConnections($connection);
        $this->checkConnectionsPerIp($connection);
    }

    /**
     * Event listener
     *
     * @param Connection $connection
     */
    public function onSocketDisconnect($connection)
    {
        $this->releaseConnection($connection);
    }

    /**
     * Event listener
     *
     * @param Connection $connection
     */
    public function onClientData($connection)
    {
        $this->checkRequestsPerMinute($connection);
    }

    /**
     * Idempotent
     *
     * @param Connection $connection
     */
    protected function checkConnections($connection)
    {
        if (Server::getInstance()->getConnectionManager()->count() > $this->options['connections']) {
            $this->limit($connection, 'Max connections');
        }
    }

    /**
     * NOT idempotent, call once per connection
     *
     * @param Connection $connection
     */
    protected function checkConnectionsPerIp($connection)
    {
        if (!($ip = $connection->getIp()) === false)
        {
            $this->log('Cannot check connections per IP', 'warning');
            return;
        }

        $this->ips[$ip] =
            (!isset($this->ips[$ip]))
            ? 1
            : min($this->options['connections_per_ip'], $this->ips[$ip] + 1)
        ;

        if ($this->ips[$ip] > $this->options['connections_per_ip'])
        {
            $this->limit($connection, 'Connections per IP');
        }
    }

    /**
     * NOT idempotent, call once per disconnection
     *
     * @param Connection $connection
     */
    protected function releaseConnection($connection)
    {
        if (!($ip = $connection->getIp()) === false)
        {
            $this->log('Cannot release connection', 'warning');
            return;
        }

        $this->ips[$ip] = 
            (!isset($this->ips[$ip]))
            ? 0 
            : max(0, $this->ips[$ip] - 1)
        ;
        unset($this->requests[$connection->getId()]);
    }

    /**
     * NOT idempotent, call once per data
     *
     * @param Connection $connection
     */
    protected function checkRequestsPerMinute($connection)
    {
        $id = $connection->getId();

        if (!isset($this->requests[$id])) {
            $this->requests[$id] = [];
        }

        // Add current token
        $this->requests[$id][] = time();

        // Expire old tokens
        while (reset($this->requests[$id]) < time() - 60)
        {
            array_shift($this->requests[$id]);
        }

        if (count($this->requests[$id]) > $this->options['requests_per_minute'])
        {
            $this->limit($connection, 'Requests per minute');
        }
    }

    /**
     * Limits the given connection
     *
     * @param Connection $connection
     * @param string $limit Reason
     */
    protected function limit($connection, $limit)
    {
        $this->log("Limiting connection {$connection->getIp()}: $limit", 'notice');
        $connection->close(new RateLimiterException($limit));
    }

    /**
     * Logger
     *
     * @param string $message
     * @param string $priority
     */
    public function log($message, $priority = 'info')
    {
        Server::getInstance()->log('RateLimiter: ' . $message, $priority);
    }
}