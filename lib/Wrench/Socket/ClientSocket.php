<?php
namespace Wrench\Socket;

use Wrench\Socket\UriSocket;

/**
 * Options:
 *  - timeout_connect      => int, seconds, default 2
 */
class ClientSocket extends UriSocket
{
    /**
     * Default connection timeout
     *
     * @var int seconds
     */
    const TIMEOUT_CONNECT = 2;
    
    protected $ssl_verify_peer = false;
    
    protected $ssl_allow_self_signed = true;

    public function __construct($uri)
    {
        parent::__construct($uri);
    }

    /**
     * Connects to the given socket
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return true;
        }

        $errno = null;
        $errstr = null;

        // Supress PHP error, we're handling it
        $this->socket = @stream_socket_client(
            $this->getUri(),
            $errno,
            $errstr,
            self::TIMEOUT_CONNECT,
            STREAM_CLIENT_CONNECT,
            $this->getStreamContext()
        );

        if (!$this->socket) {
            throw new \Wrench\Exception\ConnectionException(sprintf(
                'Could not connect to socket: %s (%d)',
                $errstr,
                $errno
            ));
        }

        stream_set_timeout($this->socket, self::TIMEOUT_SOCKET);

        return ($this->connected = true);
    }

    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * @see Wrench\Socket.UriSocket::getSocketStreamContextOptions()
     */
    protected function getSocketStreamContextOptions()
    {
        $options = array();
        return $options;
    }

    /**
     * @see Wrench\Socket.UriSocket::getSslStreamContextOptions()
     */
    protected function getSslStreamContextOptions()
    {
        $options = array();

        if ($this->ssl_verify_peer) {
            $options['verify_peer'] = true;
        }

        if ($this->ssl_allow_self_signed) {
            $options['allow_self_signed'] = true;
        }

        return $options;
    }
}
