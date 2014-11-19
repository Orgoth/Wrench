<?php

namespace Wrench;

use Wrench\Payload\Payload;

use Wrench\Payload\PayloadHandler;

use Wrench\Util\Configurable;

use Wrench\Socket\ClientSocket;
use Wrench\Protocol\Protocol;
use Wrench\Protocol\Rfc6455Protocol;


use \InvalidArgumentException;
use \RuntimeException;

/**
 * Client class
 *
 * Represents a websocket client
 */
class Client
{
    /**
     * @var int bytes
     */
    const MAX_HANDSHAKE_RESPONSE = '1500';

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $origin;

    /**
     * @var ClientSocket
     */
    protected $socket;

    /**
     * Request headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Whether the client is connected
     *
     * @var boolean
     */
    protected $connected = false;

    /**
     * @var PayloadHandler
     */
    protected $payloadHandler = null;

    /**
     * Complete received payloads
     *
     * @var array<Payload>
     */
    protected $received = [];

    /**
     * Constructor
     *
     * @param string $uri
     * @param string $origin  The origin to include in the handshake (required
     *                          in later versions of the protocol)
     */
    public function __construct($uri, $origin)
    {
        if (!$uri)
        {
            throw new InvalidArgumentException('No URI specified');
        }
        $this->uri = $uri;

        if (!$origin)
        {
            throw new InvalidArgumentException('No origin specified');
        }
        $this->origin = $origin;

        $protocol = Server::getInstance()->getProtocol();
        $protocol->validateUri($this->uri);
        $protocol->validateOriginUri($this->origin);

        $this->configureSocket();
        $this->configurePayloadHandler();
    }

    /**
     * Configures the client socket
     */
    protected function configureSocket()
    {
        $this->socket = new ClientSocket($this->uri);
    }

    /**
     * Configures the payload handler
     */
    protected function configurePayloadHandler()
    {
        $this->payloadHandler = new PayloadHandler('Wrench\Client::onData');
    }

    /**
     * Payload receiver
     *
     * Public because called from our PayloadHandler. Don't call us, we'll call
     * you (via the on_data_callback option).
     *
     * @param Payload $payload
     */
    public function onData(Payload $payload)
    {
        
    }

    /**
     * Adds a request header to be included in the initial handshake
     *
     * For example, to include a Cookie header
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function addRequestHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Sends data to the socket
     *
     * @param string $data
     * @param int $type See Protocol::TYPE_*
     * @param boolean $masked
     * @return boolean Success
     */
    public function sendData($data, $type = Protocol::TYPE_TEXT, $masked = true)
    {
        if (is_string($type) && isset(Protocol::$frameTypes[$type]))
        {
            $type = Protocol::$frameTypes[$type];
        }

        $payload = Servver::getInstance()->getProtocol()->getPayload();

        $payload->encode($data, $type, $masked);

        return $payload->sendToSocket($this->socket);
    }

    /**
     * Receives data sent by the server
     *
     * @return array<Payload> Payload received since the last call to receive()
     */
    public function receive()
    {
        if (!$this->isConnected())
        {
            return false;
        }

        if (($data = $this->socket->receive()) === false)
        {
            return $data;
        }

        $old = $this->received;
        $this->payloadHandler->handle($data);
        return array_diff_assoc($this->received, $old);
    }

    /**
     * Connect to the server
     *
     * @return boolean Whether a new connection was made
     */
    public function connect()
    {
        if ($this->isConnected())
        {
            return false;
        }

        $this->socket->connect();
        $protocol = Server::getInstance()->getProtocol();
        
        
        $key       = $protocol->generateKey();
        $handshake = $protocol->getRequestHandshake(
            $this->uri,
            $key,
            $this->origin,
            $this->headers
        );

        $this->socket->send($handshake);
        return ($this->connected = $protocol->validateResponseHandshake(
            $this->socket->receive(self::MAX_HANDSHAKE_RESPONSE),
            $key
        ));
    }

    /**
     * Returns whether the client is currently connected
     *
     * Also checks the state of the underlying socket
     *
     * @return boolean
     */
    public function isConnected()
    {
        if (!$this->connected)
        {
            return false;
        }

        // Check if the socket is still connected
        if (!$this->socket->isConnected())
        {
            $this->disconnect();
            return false;
        }
        return true;
    }

    /**
     * Disconnects the underlying socket, and marks the client as disconnected
     */
    public function disconnect()
    {
        if ($this->socket)
        {
            $this->socket->disconnect();
        }
        $this->connected = false;
    }
}
