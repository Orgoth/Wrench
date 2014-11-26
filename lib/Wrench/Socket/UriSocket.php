<?php

namespace Wrench\Socket;

use Wrench\Protocol\Protocol;

use Wrench\Socket\Socket;
use Wrench\Server;

abstract class UriSocket extends Socket
{
    protected $scheme;
    protected $host;
    protected $port;

    /**
     * URI Socket constructor
     *
     * @param string $uri     WebSocket URI, e.g. ws://example.org:8000/chat
     * @param array  $options (optional)
     */
    public function __construct($uri)
    {
        list($this->scheme, $this->host, $this->port)
            = Server::getInstance()->getProtocol()->validateSocketUri($uri);
    }

    /**
     * Gets the canonical/normalized URI for this socket
     *
     * @return string
     */
    protected function getUri()
    {
        return sprintf(
            '%s://%s:%d',
            $this->scheme,
            $this->host,
            $this->port
        );
    }

    /**
     * @todo DNS lookup? Override getIp()?
     * @see Wrench\Socket.Socket::getName()
     */
    protected function getName()
    {
        return sprintf('%s:%s', $this->host, $this->port);
    }

    /**
     * Gets the host name
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @see Wrench\Socket.Socket::getPort()
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Gets a stream context
     */
    protected function getStreamContext($listen = false)
    {
        if ($this->scheme == Protocol::SCHEME_UNDERLYING_SECURE
            || $this->scheme == Protocol::SCHEME_UNDERLYING) {
            $options['socket'] = $this->getSocketStreamContextOptions();
        }

        if ($this->scheme == Protocol::SCHEME_UNDERLYING_SECURE) {
            $options['ssl'] = $this->getSslStreamContextOptions();
        }

        return stream_context_create(
            $options,
            []
        );
    }

    /**
     * Returns an array of socket stream context options
     *
     * See http://php.net/manual/en/context.socket.php
     *
     * @return array
     */
    abstract protected function getSocketStreamContextOptions();

    /**
     * Returns an array of ssl stream context options
     *
     * See http://php.net/manual/en/context.ssl.php
     *
     * @return array
     */
    abstract protected function getSslStreamContextOptions();
}
