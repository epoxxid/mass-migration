<?php

require '../lib/MMConfig.php';
require '../lib/Logger/MMStdoutLogger.php';
require '../lib/Api/ApiClient.php';

$config = new MMConfig('../config.ini');

$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

$client = new ApiClient($config, $logger);
$result = $client->getMessageResult(13330);
