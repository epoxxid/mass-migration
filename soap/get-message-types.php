<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../lib/OrgApiClient.php';

$client = new OrgApiClient(true);
$client->getMessageTypes();

