<?php

require_once 'MassMigrationService.php';

echo "Starting\n";

# Создание нового обработчика.
$gmworker= new GearmanWorker();
$gmworker->addServer();

// Register worker methods
$gmworker->addFunction(MassMigrationService::TAKS_CREATE_FOLDER, 'createFolder');
$gmworker->addFunction(MassMigrationService::TASK_CHECK_STATUS, 'checkStatus');

print "Waiting for a job...\n";

while($gmworker->work())
{
  if ($gmworker->returnCode() != GEARMAN_SUCCESS)
  {
    echo 'return_code: ' . $gmworker->returnCode() . "\n";
    break;
  }
}


/**
 * @param GearmanJob $job
 * @return string
 */
function createFolder($job)
{
    list($id, $name) = unserialize($job->workload());
    echo "\nNEW TASK: create folder '$name' with ID = $id\n";

    echo ">>> API REQUEST: createFolder($name)\n";
    sleep(1);
    $qId = mt_rand(1, 100); // Imitate receiving queue item ID
    echo "<<< API RESPONSE: message enqueued ID = {$qId}\n";

    return serialize(array(
        'event' => MassMigrationService::EVENT_ITEM_ENQUEUED,
        'itemId' => $id,
        'itemQueueId' => $qId
    ));
}

/**
 * @param GearmanJob $job
 * @return string
 */
function checkStatus($job)
{
    list($id, $qId) = unserialize($job->workload());
    echo "\nNEW TASK: get status of item with ID = $id" . PHP_EOL;

    echo ">>> API REQUEST: getMessageResult($id)\n";
    sleep(1);
    $syncKey = md5(time() . microtime());
    echo "<<< API RESPONSE: folder created with syncKey = {$syncKey}\n";

    return serialize(array(
        'event' => MassMigrationService::EVENT_FOLDER_CREATED,
        'itemId' => $id,
        'syncKey' => $syncKey
    ));
}
