<?php

require '../lib/MMConfig.php';
require '../lib/Logger/MMStdoutLogger.php';
require '../lib/MMServiceWorker.php';

// Load configuration
$config = new MMConfig('../config.ini');

// Init logger
$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

$worker = new MMServiceWorker($config, $logger);
$worker->start();

