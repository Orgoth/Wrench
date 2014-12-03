<?php

    use Wrench\Frame\HybiFrame;
    use Wrench\Protocol\Protocol;

    $loader = require __DIR__ . '/vendor/autoload.php';

    $protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
    $host = (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'wrench.local';
    $path = (!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '/status';
     
    $stream = pfsockopen('tcp://localhost', 8000, $errno, $errstr);
    
    $headers =
        "GET /status $protocol\r\n" .
        "Host: $host\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Key: x3JJHMbDL1EzLkh9GBhXDw==\r\n" .
        "Sec-WebSocket-Version: 13\r\n" . 
        "Origin: http://wrench.local\r\n" . 
        "\r\n\r\n"
    ;
    
    fputs($stream, $headers);
    
    $response = fread($stream, 5120);
    
    $responseHeaders = explode("\r\n", $response);
    
    if($responseHeaders[0] !== 'HTTP/1.1 101 Switching Protocols')
    {
        die('Handshake failed');
    }
    
    $frame = new HybiFrame();
    $payload = $frame->encode(json_encode([
        'action' => '_sendTemplate',
        'path' => $path
    ]))->getFrameBuffer();
    
    fputs($stream, $payload);
    
    $response = fread($stream, 5120);
    
    var_dump($response);
    
    fputs($stream, $frame->encode('', Protocol::TYPE_CLOSE)->getFrameBuffer());
    
    var_dump(fread($stream, 5120));
    fclose($stream);