<?php

require_once 'Api/ApiFolderCreator.php';
require_once 'Tasks/CreateFolderTask.php';
require_once 'MMServiceClient.php';
require_once 'Exceptions/TaskInvalidDataException.php';

/**
 * Gearman worker class for Mass Migration service
 */
class MMServiceWorker
{
    /** @var string Folder successfully created */
    const TASK_CREATE_FOLDER = 'create-folder';
    /** @var string Queue status checked */
    const TASK_CHECK_STATUS = 'check-status';

    /** @var GearmanWorker */
    private $worker;
    /** @var MMLogger */
    private $logger;
    /** @var ApiFolderCreator */
    private $folderCreator;
    /** @var ApiClient */
    private $apiClient;

    /**
     * @param MMConfig $config
     * @param MMLogger $logger
     */
    public function __construct(MMConfig $config, MMLogger $logger)
    {
        // Need to some kind of api clients manager to get rid of multiple requests
        $this->apiClient = new ApiClient($config, $logger);
        $this->folderCreator = new ApiFolderCreator($config, $logger);
        $this->logger = $logger;

        $this->worker = new GearmanWorker();
        $this->worker->addServer();

        $this->worker->addFunction(self::TASK_CREATE_FOLDER, array($this, 'createFolder'));
        $this->worker->addFunction(self::TASK_CHECK_STATUS, array($this, 'checkStatus'));
    }


    public function start()
    {
        $this->logger->dbg('Gearman started! Waiting for a job...', __METHOD__);

        while ($this->worker->work()) {
            if ($this->worker->returnCode() != GEARMAN_SUCCESS) {
                $returnCode = $this->worker->returnCode();
                $this->logger->dbg("Gearman process interrupted with code `{$returnCode}`");
                break;
            }
        }
    }

    /**
     * @param GearmanJob $job
     */
    public function createFolder($job)
    {
        $data = unserialize($job->workload());
        $taskName = "[#{$data['id']}: CREATE FOLDER]";
        $this->logger->dbg("Gearman has got new task $taskName", __METHOD__);

        try {
            $task = new CreateFolderTask($data);


            $result = $this->folderCreator->createFolder(
                $task->getParentSyncKey(),
                $task->toRequestFormat()
            );

            if ($result->isStatusInQueue()) {
                return serialize(array(
                    'event' => MMServiceClient::EVENT_ITEM_ENQUEUED,
                    'itemId' => $data['id'],
                    'messageQueueId' => $result->getMessageQueueId()
                ));
            }

            // TODO: Maybe more details?

        } catch (TaskInvalidDataException $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        }

        return serialize(array(
            'event' => MMServiceClient::EVENT_TASK_PROCESSING_FAILED,
            'itemId' => $data['id'],
            'message' => "Gearman unable to accomplish task $taskName"
        ));
    }

    /**
     * @param GearmanJob $job
     */
    public function checkStatus($job)
    {
        $data = unserialize($job->workload());
        $taskName = "[#{$data['itemId']}: GET MESSAGE RESULT]";
        $this->logger->dbg("Gearman has got new task $taskName", __METHOD__);

        try {
            if (!isset($data['messageQueueId'])) {
                throw new TaskInvalidDataException('MessageQueueId is required');
            }

            $response = $this->apiClient->getMessageResult((int)$data['messageQueueId']);

            if ($response->isStatusFinished()) {
                $folderDetails = $response->getItemDetails();
                return serialize(array(
                   'event' => MMServiceClient::EVENT_FOLDER_CREATED,
                   'itemId' => $data['itemId'],
                   'syncKey' => $folderDetails->SyncKey
                ));
            }

            if ($response->isStatusInQueue()) {
                return serialize(array(
                    'event' => MMServiceClient::EVENT_ITEM_ENQUEUED,
                    'itemId' => $data['id'],
                    'messageQueueId' => $data['messageQueueId']
                ));
            }

        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        }

        return serialize(array(
            'event' => MMServiceClient::EVENT_TASK_PROCESSING_FAILED,
            'itemId' => $data['id'],
            'message' => "Gearman unable to accomplish task $taskName"
        ));
    }
}
