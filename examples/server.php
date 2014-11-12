#!/usr/bin/env php
<?php

/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

use Wrench\Server;
use Wrench\Application\EchoApplication;
use Wrench\Application\StatusApplication;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require(__DIR__ . '/../lib/SplClassLoader.php');

$classLoader = new SplClassLoader('Wrench', __DIR__ . '/../lib');
$classLoader->register();

$server = Server::getInstance();
$server->init('ws://localhost:8000/', [
    'allowed_origins' => [
        'mysite.localhost'
    ],
    'maxClients' => 100,
    'maxConnections' => 6,
    'maxRequestsPerMinute' => 2000000
// Optional defaults:
//     'check_origin'               => true,
//     'connection_manager_class'   => 'Wrench\ConnectionManager',
//     'connection_manager_options' => array(
//         'timeout_select'           => 0,
//         'timeout_select_microsec'  => 200000,
//         'socket_master_class'      => 'Wrench\Socket\ServerSocket',
//         'socket_master_options'    => array(
//             'backlog'                => 50,
//             'ssl_cert_file'          => null,
//             'ssl_passphrase'         => null,
//             'ssl_allow_self_signed'  => false,
//             'timeout_accept'         => 5,
//             'timeout_socket'         => 5,
//         ),
//         'connection_class'         => 'Wrench\Connection',
//         'connection_options'       => array(
//             'socket_class'           => 'Wrench\Socket\ServerClientSocket',
//             'socket_options'         => array(),
//             'connection_id_secret'   => 'asu5gj656h64Da(0crt8pud%^WAYWW$u76dwb',
//             'connection_id_algo'     => 'sha512'
//         )
//     )
]);

$server->registerApplication('demo', new EchoApplication);
$server->registerApplication('status', new StatusApplication);
$server->run();
