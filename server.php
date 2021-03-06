#!/usr/bin/env php
<?php

/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

use Wrench\Server;
use Application\Demo\DemoApplication;
use Application\Status\StatusApplication;

$loader = require __DIR__ . '/vendor/autoload.php';

$server = Server::getInstance();
$server->init('ws://localhost:8000/');
$server->setRateLimiter(100, 6, 20000);
$server->setOriginPolicy(['http://www.wrench.local', 'http://wrench.local']);
$server->registerApplication('demo', new DemoApplication);
$server->registerApplication('status', new StatusApplication);
$server->run();
