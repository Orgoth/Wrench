<?php

namespace Wrench;

use \Closure;
use \InvalidArgumentException;

use Wrench\Service\ConnectionManager;
use Wrench\Service\MemoryManager;
use Wrench\Service\Logger;

use Wrench\Protocol\Protocol;
use Wrench\Protocol\Rfc6455Protocol;

/**
 * WebSocket server
 *
 * The server extends socket, which provides the master socket resource. This
 * resource is listened to, and an array of clients managed.
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Simon Samtleben <web@lemmingzshadow.net>
 * @author Dominic Scheirlinck <dominic@varspool.com>
 */
class Server
{
    /**#@+
     * Events
     *
     * @var string
     */
    const EVENT_SOCKET_CONNECT       = 'socket_connect';
    const EVENT_SOCKET_DISCONNECT    = 'socket_disconnect';
    const EVENT_HANDSHAKE_REQUEST    = 'handshake_request';
    const EVENT_HANDSHAKE_SUCCESSFUL = 'handshake_successful';
    const EVENT_CLIENT_DATA          = 'client_data';
    /**#@-*/

    /**
     * The URI of the server
     *
     * @var string
     */
    protected $uri;

    /**
     * A logging callback
     *
     * The default callback simply prints to stdout. You can pass your own logger
     * in the options array. It should take a string message and string priority
     * as parameters.
     *
     * @var Closure
     */
    protected $logger;

    /**
     * Event listeners
     *
     * Add listeners using the addListener() method.
     *
     * @var array<string => array<Closure>>
     */
    protected $listeners = [];

    /**
     * Connection manager
     *
     * @var ConnectionManager
     */
    protected $connectionManager;
    
    /**
     * Memory manager
     * 
     * @var MemoryManager 
     */
    protected $memoryManager;
    
    /**
     * @var Protocol
     */
    protected $protocol;

    /**
     * Applications
     *
     * @var array<string => Application>
     */
    protected $applications = [];
    
    /** @var integer **/
    protected $maxRequestsPerMinute;
    
    /** @var integer **/
    protected $maxRequestsPerIp;
    
    /** @var integer **/
    protected $maxClients;
    
    /**
     * Contains the instance for Singleton pattern
     * 
     * @var Server
     */
    private static $instance;

    public function __construct()
    {
    }
    
    /**
     * Manual constructor
     *
     * @param string $uri Websocket URI, e.g. ws://localhost:8000/, path will
     *                     be ignored
     * @param array $options (optional) See configure
     */
    public function init($uri)
    {
        $this->uri = $uri;

        $this->configure();
        
        $this->log("Server initialized on $uri", 'info');
    }

    /**
     * Configure options
     *
     * Options include
     *   - socket_class      => The socket class to use, defaults to ServerSocket
     *   - socket_options    => An array of socket options
     *   - logger            => Closure($message, $priority = 'info'), used
     *                                 for logging
     *
     * @param array $options
     * @return void
     */
    protected function configure()
    {
        $this->configureProtocol();
        $this->configureConnectionManager();
        $this->configureLogger();
        $this->configureMemoryManager();
    }
    
    /**
     * Configures the protocol option
     *
     * @throws InvalidArgumentException
     */
    protected function configureProtocol()
    {
        $this->protocol = new Rfc6455Protocol();

        if (!$this->protocol || !($this->protocol instanceof Protocol))
        {
            throw new InvalidArgumentException('Invalid protocol option');
        }
    }

    /**
     * Configures the logger
     *
     * @return void
     */
    protected function configureLogger()
    {
        $this->logger = new Logger();
    }

    /**
     * Configures the connection manager
     *
     * @return void
     */
    protected function configureConnectionManager()
    {
        $this->connectionManager = new ConnectionManager();
    }
    
    protected function configureMemoryManager()
    {
        $this->memoryManager = new MemoryManager();
    }

    /**
     * Gets the connection manager
     *
     * @return \Wrench\ConnectionManager
     */
    public function getConnectionManager()
    {
        return $this->connectionManager;
    }
    
    /**
     * Gets the memory manager
     * 
     * @return MemoryManager
     */
    public function getMemoryManager()
    {
        return $this->memoryManager;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Main server loop
     *
     * @return void This method does not return!
     */
    public function run()
    {
        $this->connectionManager->listen();
        $this->memoryManager->init();

        while (true) {
            /*
             * If there's nothing changed on any of the sockets, the server
             * will sleep and other processes will have a change to run. Control
             * this behaviour with the timeout options.
             */
            $this->connectionManager->selectAndProcess();
            $this->memoryManager->refreshMemory();
        }
    }

    /**
     * Logs a message to the server log
     *
     * The default logger simply prints the message to stdout. You can provide
     * a logging closure. This is useful, for instance, if you've daemonized
     * and closed STDOUT.
     *
     * @param string $message Message to display.
     * @param string $type Type of message.
     * @return void
     */
    public function log($message, $type = 'info')
    {
        $this->logger->log($type, $message);
    }

    /**
     * Notifies listeners of an event
     *
     * @param string $event
     * @param array $arguments Event arguments
     * @return void
     */
    public function notify($event, array $arguments = array())
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            call_user_func_array($listener, $arguments);
        }
    }
    
    /**
     * Notifies applications of an event
     * 
     * @param string $type
     * @param array $data
     * @return void
     */
    public function notifyApplications($type, $data = [])
    {
        reset($this->applications);
        
        while($key = key($this->applications))
        {
            $this->applications[$key]->onNotification($type, $data);
            next($this->applications);
        }
    }

    /**
     * Adds a listener
     *
     * Provide an event (see the Server::EVENT_* constants) and a callback
     * closure. Some arguments may be provided to your callback, such as the
     * connection the caused the event.
     *
     * @param string $event
     * @param Closure $callback
     * @return void
     * @throws InvalidArgumentException
     */
    public function addListener($event, $callback)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = array();
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Invalid listener');
        }

        $this->listeners[$event][] = $callback;
    }

    /**
     * Returns a server application.
     *
     * @param string $key Name of application.
     * @return Application The application object.
     */
    public function getApplication($key)
    {
        if (empty($key)) {
            return false;
        }

        if (array_key_exists($key, $this->applications)) {
            return $this->applications[$key];
        }

        return false;
    }

    /**
     * Adds a new application object to the application storage.
     *
     * @param string $key Name of application.
     * @param object $application The application object
     * @return void
     */
    public function registerApplication($key, $application)
    {
        $this->applications[$key] = $application;
        
        $this->log('Application added : '.$key, 'info');
    }
    
    public function getProtocol()
    {
        return $this->protocol;
    }
    
    public static function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setMaxRequestsPerMinute($maxRequestsPerMinute)
    {
        $this->maxRequestsPerMinute = $maxRequestsPerMinute;
    }
    
    public function getMaxRequestsPerMinute()
    {
        return $this->maxRequestsPerMinute;
    }
    
    public function setMaxClients($maxClients)
    {
        $this->maxClients = $maxClients;
    }
    
    public function getMaxClients()
    {
        return $this->maxClients;
    }
    
    public function setMaxRequestsPerIp($maxRequestsPerIp)
    {
        $this->maxRequestsPerIp = $maxRequestsPerIp;
    }
    
    public function getMaxRequestsPerIp()
    {
        return $this->maxRequestsPerIp;
    }
}
