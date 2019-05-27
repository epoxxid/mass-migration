<?php

require_once '../lib/MassMigrationService.php';

require '../lib/MMConfig.php';
require '../lib/Logger/MMStdoutLogger.php';
require '../lib/Api/ApiClient.php';

$config = new MMConfig('../config.ini');

$logger = new MMStdoutLogger();
$logger->setLevel($config->getLogLevel());

$migration = new MMServiceClient($logger);
$migration->start();