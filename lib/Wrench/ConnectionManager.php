<?php

namespace Wrench;

use InvalidArgumentException;
use Wrench\Protocol\Protocol;
use Wrench\Resource;
use Wrench\Util\Configurable;
use Wrench\Exception\Exception as WrenchException;
use Wrench\Exception\CloseException;
use \Exception;
use \Countable;
use Wrench\Server;

class ConnectionManager extends Configurable implements Countable
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
    protected $connections = array();

    /**
     * An array of raw socket resources, corresponding to connections, roughly
     *
     * @var array<int => resource>
     */
    protected $resources = array();

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        
        $this->configure();
    }

    /**
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->connections);
    }

    /**
     * @see Wrench\Socket.Socket::configure()
     *   Options include:
     *     - timeout_select          => int, seconds, default 0
     *     - timeout_select_microsec => int, microseconds (NB: not milli), default: 200000
     */
    protected function configure()
    {
        parent::configureOptions(array_merge([
            'socket_master_class'     => 'Wrench\Socket\ServerSocket',
            'socket_master_options'   => [],
            'socket_client_class'     => 'Wrench\Socket\ServerClientSocket',
            'socket_client_options'   => [],
            'connection_class'        => 'Wrench\Connection',
            'connection_options'      => [],
            'timeout_select'          => self::TIMEOUT_SELECT,
            'timeout_select_microsec' => self::TIMEOUT_SELECT_MICROSEC
        ], $this->options));

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
        $this->socket = new $this->options['socket_master_class'](
            $this->getUri(), $this->options['socket_master_options']
        );
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
            $this->options['timeout_select'],
            $this->options['timeout_select_microsec']
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

        try
        {
            $new = $this->socket->accept();
        }
        catch (Exception $e)
        {
            $this->log('Socket error: ' . $e, 'err');
            return;
        }

        $connection = $this->createConnection($new);
        Server::getInstance()->notify(Server::EVENT_SOCKET_CONNECT, [$new, $connection]);
        Server::getInstance()->notifyApplications(Server::EVENT_SOCKET_CONNECT, [$new, $connection]);
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
        
        $connection = new $this->options['connection_class'](
            new $this->options['socket_client_class'](
                $resource, $this->options['socket_client_options']
            ),
            $this->options['connection_options']
        );

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

        if (!$connection)
        {
            $this->log('No connection for client socket', 'warning');
            return;
        }

        try
        {
            $connection->process();
        }
        catch (CloseException $e)
        {
            $this->log('Client connection closed: ' . $e, 'notice');
            $connection->close($e);
        }
        catch (WrenchException $e)
        {
            $this->log('Error on client socket: ' . $e, 'warning');
            $connection->close($e);
        }
        catch (\InvalidArgumentException $e)
        {
            $this->log('Wrong input arguments: ' . $e, 'warning');
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
     * Logs a message
     *
     * @param string $message
     * @param string $priority
     */
    public function log($message, $priority = 'info')
    {
        Server::getInstance()->log(sprintf(
            '%s: %s',
            __CLASS__,
            $message
        ), $priority);
    }

    /**
     * Removes a connection
     *
     * @param Connection $connection
     */
    public function removeConnection(Connection $connection)
    {
        $socket = $connection->getSocket();

        $index = 
            ($socket->getResource())
            ? $socket->getResourceId()
            : array_search($connection, $this->connections)
        ;

        if (!$index) {
            $this->log('Could not remove connection: not found', 'warning');
        }

        unset($this->connections[$index]);
        unset($this->resources[$index]);

        Server::getInstance()->notify(Server::EVENT_SOCKET_DISCONNECT,array($connection->getSocket(), $connection));
        Server::getInstance()->notifyApplications(Server::EVENT_SOCKET_DISCONNECT, array($connection->getSocket(), $connection));
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
