<?php

namespace Wrench\Service;

use InvalidArgumentException;
use Wrench\Protocol\Protocol;
use Wrench\Resource;
use Wrench\Util\Configurable;
use Wrench\Exception\Exception as WrenchException;
use Wrench\Exception\CloseException;
use \Exception;
use \Countable;
use Wrench\Server;
use Wrench\Connection;
use Wrench\Socket\ServerSocket;
use Wrench\Socket\ServerClientSocket;

class ConnectionManager implements Countable
{
    const TIMEOUT_SELECT          = 0;
    const TIMEOUT_SELECT_MICROSEC = 200000;

    /**
     * Master socket
     *
     * @var ServerSocket
     */
    protected $socket;

    /**
     * An array of client connections
     *
     * @var array<int => Connection>
     */
    protected $connections = [];

    /**
     * An array of raw socket resources, corresponding to connections, roughly
     *
     * @var array<int => resource>
     */
    protected $resources = [];

    /**
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->connections);
    }

    public function __construct()
    {
        $this->configureMasterSocket();
    }

    /**
     * Gets the application associated with the given path
     *
     * @param string $path
     */
    public function getApplicationForPath($path)
    {
        return Server::getInstance()->getApplication(ltrim($path, '/'));
    }

    /**
     * Configures the main server socket
     *
     * @param string $uri
     */
    protected function configureMasterSocket()
    {
        $this->socket = new ServerSocket($this->getUri());
    }

    /**
     * Listens on the main socket
     *
     * @return void
     */
    public function listen()
    {
        $this->socket->listen();
        $this->resources[$this->socket->getResourceId()] = $this->socket->getResource();
    }

    /**
     * Gets all resources
     *
     * @return array<int => resource)
     */
    protected function getAllResources()
    {
        return array_merge($this->resources, [
            $this->socket->getResourceId() => $this->socket->getResource()
        ]);
    }

    /**
     * Returns the Connection associated with the specified socket resource
     *
     * @param resource $socket
     * @return Connection
     */
    protected function getConnectionForClientSocket($socket)
    {
        if (!isset($this->connections[$this->resourceId($socket)])) {
            return false;
        }
        return $this->connections[$this->resourceId($socket)];
    }

    /**
     * Select and process an array of resources
     */
    public function selectAndProcess()
    {
        $read             = $this->resources;
        $unused_write     = null;
        $unused_exception = null;

        stream_select(
            $read,
            $unused_write,
            $unused_exception,
            self::TIMEOUT_SELECT,
            self::TIMEOUT_SELECT_MICROSEC
        );

        foreach ($read as $socket)
        {
            if ($socket == $this->socket->getResource())
            {
                $this->processMasterSocket();
                continue;
            }
            $this->processClientSocket($socket);
        }
    }

    /**
     * Process events on the master socket ($this->socket)
     *
     * @return void
     */
    protected function processMasterSocket()
    {
        $new;
        $server = Server::getInstance();

        try
        {
            $new = $this->socket->accept();
        }
        catch (Exception $e)
        {
            $server->log('Socket error: ' . $e, 'err');
            return;
        }

        $connection = $this->createConnection($new);
        $server->notify(Server::EVENT_SOCKET_CONNECT, [$new, $connection]);
        $server->notifyApplications(Server::EVENT_SOCKET_CONNECT, [$new, $connection]);
    }

    /**
     * Creates a connection from a socket resource
     *
     * The create connection object is based on the options passed into the
     * constructor ('connection_class', 'connection_options'). This connection
     * instance and its associated socket resource are then stored in the
     * manager.
     *
     * @param resource $resource A socket resource
     * @return Connection
     */
    protected function createConnection($resource)
    {
        if (!$resource || !is_resource($resource)) {
            throw new InvalidArgumentException('Invalid connection resource');
        }
        
        $connection = new Connection(new ServerClientSocket($resource));

        $id = $this->resourceId($resource);
        $this->resources[$id] = $resource;
        $this->connections[$id] = $connection;

        return $connection;
    }

    /**
     * Process events on a client socket
     *
     * @param resource $socket
     */
    protected function processClientSocket($socket)
    {
        $connection = $this->getConnectionForClientSocket($socket);
        $server = Server::getInstance();
        
        if (!$connection)
        {
            $server->log('No connection for client socket', 'warning');
            return;
        }

        try
        {
            $connection->process();
        }
        catch (CloseException $e)
        {
            $server->log('Client connection closed: ' . $e, 'notice');
            $connection->close($e);
        }
        catch (WrenchException $e)
        {
            $server->log('Error on client socket: ' . $e, 'warning');
            $connection->close($e);
        }
        catch (\InvalidArgumentException $e)
        {
            $server->log('Wrong input arguments: ' . $e, 'warning');
            $connection->close($e);
        }
    }

    /**
     * This server makes an explicit assumption: PHP resource types may be cast
     * to a integer. Furthermore, we assume this is bijective. Both seem to be
     * true in most circumstances, but may not be guaranteed.
     *
     * This method (and $this->getResourceId()) exist to make this assumption
     * explicit.
     *
     * This is needed on the connection manager as well as on resources
     *
     * @param resource $resource
     */
    protected function resourceId($resource)
    {
        return (int)$resource;
    }

    /**
     * Gets the connection manager's listening URI
     *
     * @return string
     */
    public function getUri()
    {
        return Server::getInstance()->getUri();
    }

    /**
     * Removes a connection
     *
     * @param Connection $connection
     */
    public function removeConnection(Connection $connection)
    {
        $socket = $connection->getSocket();
        $server = Server::getInstance();
        $index = 
            ($socket->getResource())
            ? $socket->getResourceId()
            : array_search($connection, $this->connections)
        ;

        if (!$index) {
            $server->log('Could not remove connection: not found', 'warning');
        }

        unset($this->connections[$index]);
        unset($this->resources[$index]);

        $server->notify(Server::EVENT_SOCKET_DISCONNECT,array($connection->getSocket(), $connection));
        $server->notifyApplications(Server::EVENT_SOCKET_DISCONNECT, array($connection->getSocket(), $connection));
    }
    
    /**
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }
    
    public function getConvertedConnections()
    {
        $connections = [];
        foreach($this->connections as $connection)
        {
            $connections[$connection->getPort()] = $connection->getIp();
        }
        return $connections;
    }
}
