<?php

require_once 'OrgApiClient.php';

$client = new OrgApiClient();
$client->getMessageTypes();
echo '<pre>';
$client->dumpRequestXml(true);
$client->dumpResponseXml(true);
