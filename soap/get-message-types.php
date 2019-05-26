<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../lib/ApiClient.php';

$client = new ApiClient(true);
$client->loadMessageTypes();

