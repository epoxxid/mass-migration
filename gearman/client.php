<?php

require '../lib/MMConfig.php';
require '../lib/Logger/MMStdoutLogger.php';
require '../lib/MMServiceClient.php';

// Load configuration
$config = new MMConfig('../config.ini');

// Init logger
$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

$client = new MMServiceClient($logger);
$client->start();