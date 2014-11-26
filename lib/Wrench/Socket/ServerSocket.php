<?php

namespace Wrench\Socket;

use Wrench\Exception\ConnectionException;

use Wrench\Socket\UriSocket;

/**
 * Server socket
 *
 * Used for a server's "master" socket that binds to the configured
 * interface and listens
 */
class ServerSocket extends UriSocket
{
    const TIMEOUT_ACCEPT = 5;

    /**
     * Whether the socket is listening
     *
     * @var boolean
     */
    protected $listening = false;
    
    protected $backlog = 50;
    
    protected $ssl_cert_file = null;
    
    protected $ssl_passphrase = null;
    
    protected $ssl_allow_self_signed = false;

    public function __construct($uri)
    {
        parent::__construct($uri);
    }

    /**
     * Listens
     *
     * @throws ConnectionException
     */
    public function listen()
    {
        $this->socket = stream_socket_server(
            $this->getUri(),
            $errno,
            $errstr,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN.
            $this->getStreamContext()
        );

        if (!$this->socket) {
            throw new ConnectionException(sprintf(
                'Could not listen on socket: %s (%d)',
                $errstr,
                $errno
            ));
        }
        $this->listening = true;
    }

    /**
     * Accepts a new connection on the socket
     *
     * @throws ConnectionException
     * @return resource
     */
    public function accept()
    {
        $new = stream_socket_accept(
            $this->socket,
            self::TIMEOUT_ACCEPT
        );

        if (!$new) {
            throw new ConnectionException(socket_strerror(socket_last_error($new)));
        }

        return $new;
    }

    /**
     * @see Wrench\Socket.UriSocket::getSocketStreamContextOptions()
     */
    protected function getSocketStreamContextOptions()
    {
        if (isset($this->backlog)) {
            return ['backlog' => $this->backlog];
        }
        return [];
    }

    /**
     * @see Wrench\Socket.UriSocket::getSslStreamContextOptions()
     */
    protected function getSslStreamContextOptions()
    {
        $options = [];

        if ($this->ssl_cert_file)
        {
            $options['local_cert'] = $this->ssl_cert_file;
            if ($this->ssl_passphrase)
            {
                $options['passphrase'] = $this->ssl_passphrase;
            }
        }
        return $options;
    }
}