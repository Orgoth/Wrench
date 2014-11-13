#!/usr/bin/env php
<?php

use Wrench\Server;
use Application\EchoApplication;
use Application\ServerTimeApplication;
use Wrench\Util\Ssl;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

// Generate PEM file
$pemFile                = __DIR__ . '/generated.pem';
$pemPassphrase          = null;
$countryName            = "DE";
$stateOrProvinceName    = "none";
$localityName           = "none";
$organizationName       = "none";
$organizationalUnitName = "none";
$commonName             = "example.com";
$emailAddress           = "someone@example.com";

Ssl::generatePEMFile(
    $pemFile,
    $pemPassphrase,
    $countryName,
    $stateOrProvinceName,
    $localityName,
    $organizationName,
    $organizationalUnitName,
    $commonName,
    $emailAddress
);

// User can use tls in place of ssl
$server = new Server('wss://127.0.0.1:8000/', array(
     'connection_manager_options' => array(
         'socket_master_options' => array(
             'server_ssl_cert_file'         => $pemFile,
             'server_ssl_passphrase'        => $pemPassphrase,
             'server_ssl_allow_self_signed' => true,
             'server_ssl_verify_peer'       => false
         )
     )
));

$server->registerApplication('echo', new EchoApplication());
$server->registerApplication('time', new ServerTimeApplication());
$server->run();
