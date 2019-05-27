<?php

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

    /**
     * @param MMConfig $config
     * @param MMLogger $logger
     */
    public function __construct(MMConfig $config, MMLogger $logger)
    {
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

        while($this->worker->work())
        {
            if ($this->worker->returnCode() != GEARMAN_SUCCESS)
            {
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
        $dep = unserialize($job->workload());
        $taskName = "[#{$dep['id']}: CREATE FOLDER]";
        $this->logger->dbg("Gearman has got new task $taskName");

        try {
            $task = new CreateFolderTask($dep);

            $result = $this->folderCreator->createFolder(
                $task->getParentSyncKey(),
                $task->toRequestFormat()
            );

            if ($result->isStatusInQueue()) {
                return serialize(array(
                   'event' => MMServiceClient::EVENT_ITEM_ENQUEUED,
                   'itemId' => $dep['id'],
                   'messageQueueId' => $result->getMessageQueueId()
                ));
            }

            // TODO: Maybe more details?

        } catch (TaskInvalidDataException $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        }

        return serialize(array(
            'event' => MMServiceClient::EVENT_TASK_PROCESSING_FAILED,
            'itemId' => $dep['id'],
            'message' => "Gearman unable to accomplish task $taskName"
        ));
    }

    public function checkStatus($job)
    {
        $messageId = (int)unserialize($job->workload());
    }
}
