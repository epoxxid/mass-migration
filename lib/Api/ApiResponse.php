<?php

class ApiResponse
{
    const STATUS_ITEM_IN_QUEUE = 'InQueue';
    const STATUS_ITEM_PROCESSED = 'Finished';
    const STATUS_REQUEST_FAILED = 'Failed';

    /** @var string */
    private $status;

    /** @var int|null */
    private $messageQueueId;

    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return int|null
     */
    public function getMessageQueueId()
    {
        return $this->messageQueueId;
    }

    /**
     * @param int|null $messageQueueId
     */
    public function setMessageQueueId($messageQueueId)
    {
        $this->messageQueueId = (int)$messageQueueId;
    }

}