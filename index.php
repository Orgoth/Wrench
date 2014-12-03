<?php

    $protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
    $host = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'wrench.local';
    $path = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '/demo';
     
    $stream = stream_socket_client('tcp://localhost:8000/');
    
    $headers =
        "GET $path $protocol\r\n" .
        "Host: $host\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Key: x3JJHMbDL1EzLkh9GBhXDw==\r\n" .
        "Sec-WebSocket-Version: 13\r\n" . 
        "Origin: http://wrench.local\r\n" . 
        "\r\n\r\n"
    ;
    
    stream_socket_sendto($stream, $headers . json_encode([
        'action' => 'askInformations',
        'data'   => [
            'path' => $path
        ]
    ]));
    
    $response = stream_socket_recvfrom($stream, 5120);
    
    var_dump($response);