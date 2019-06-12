<?php

/**
 * Response from API
 */
class ApiResponse
{
    /** @var string Request is invalid and cannot be sent */
    const STATUS_INVALID_REQUEST = 'Invalid';
    /** @var string Item added to queue */
    const STATUS_ITEM_IN_QUEUE = 'InQueue';
    /** @var string Item processing finished */
    const STATUS_ITEM_PROCESSED = 'Finished';
    /** @var string Item processing failed */
    const STATUS_REQUEST_FAILED = 'Failed';

    /** @var string */
    private $status;
    /** @var int|null */
    private $messageQueueId;
    /** @var array */
    private $itemDetails;
    /** @var string */
    private $explanationMessage;
    /** @var int */
    private $attemptNumber = 1;

    /**
     * ApiResponse constructor.
     * @param $status
     * @throws Exception
     */
    public function __construct($status)
    {
        $validStatuses = array(
            self::STATUS_INVALID_REQUEST,
            self::STATUS_ITEM_IN_QUEUE,
            self::STATUS_ITEM_PROCESSED,
            self::STATUS_REQUEST_FAILED
        );

        $this->status = in_array($status, $validStatuses) ? $status : self::STATUS_REQUEST_FAILED;
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

    /**
     * @return mixed
     */
    public function getItemDetails()
    {
        return $this->itemDetails;
    }

    /**
     * @param mixed $itemDetails
     */
    public function setItemDetails($itemDetails)
    {
        $this->itemDetails = $itemDetails;
    }

    /**
     * @return string
     */
    public function getExplanationMessage()
    {
        return $this->explanationMessage;
    }

    /**
     * @param string $explanationMessage
     */
    public function setExplanationMessage($explanationMessage)
    {
        $this->explanationMessage = (string) $explanationMessage;
    }

    /**
     * @return bool
     */
    public function isStatusInQueue()
    {
        return $this->status === self::STATUS_ITEM_IN_QUEUE;
    }

    /**
     * @return bool
     */
    public function isStatusFinished()
    {
        return $this->status === self::STATUS_ITEM_PROCESSED;
    }

    /**
     * @return bool
     */
    public function isStatusFailed()
    {
        return $this->status === self::STATUS_REQUEST_FAILED;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getAttemptNumber()
    {
        return $this->attemptNumber;
    }

    /**
     * @param int $attemptNumber
     */
    public function setAttemptNumber($attemptNumber)
    {
        $attemptNumber = (int) $attemptNumber;
        $this->attemptNumber = $attemptNumber > 0 ? $attemptNumber : 1;
    }
}
