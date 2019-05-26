<?php

require_once dirname(__DIR__) . '/Helpers/XmlHelper.php';

class ApiFolderCreator
{
    const DEFAULT_NAME = '__Fronter_exported__';
    const ACTION_CREATE_FOLDER = 'Create.Course.Element.Folder';

    /** @var ApiClient */
    private $apiClient;

    /** @var MMLogger */
    private $logger;

    /**
     * ApiFolderCreator constructor.
     * @param MMConfig $config
     * @param MMLogger $logger
     */
    public function __construct(MMConfig $config, MMLogger $logger)
    {
        $this->apiClient = new ApiClient($config, $logger);
        $this->logger = $logger;
    }

    /**
     * @param null $parentSyncKey
     * @param array $params
     * @return ApiResponse
     * @throws Exception
     */
    public function createFolder($parentSyncKey = null, array $params = array())
    {
        try {
            $xml = $this->composeMessageXml($parentSyncKey, $params);

            $folderTitle = isset($params['Title']) ? $params['Title'] : self::DEFAULT_NAME;
            $infoMsg = "Attempt to create folder `$folderTitle`";
            $infoMsg .= $parentSyncKey ? " in parent with sync key `$parentSyncKey`" : ' in course root';
            $this->logger->info($infoMsg, __METHOD__);

            $queueId = $this->apiClient->sendMessage(self::ACTION_CREATE_FOLDER, $xml);

            $response = new ApiResponse(ApiResponse::STATUS_ITEM_IN_QUEUE);
            $response->setMessageQueueId($queueId);
            return $response;

        } catch (ApiRequestValidationException $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        }
    }

    /**
     * Compose and return valid XML string
     *
     * @param string|null $parentSyncKey
     * @param array $params
     * @return string
     *
     * @throws Exception
     */
    private function composeMessageXml($parentSyncKey, array $params)
    {
        $msg = new SimpleXMLElement('<Message xmlns="urn:message-schema"/>');

        // Force sync key for the folder
        if (isset($params['SyncKey'])) {
            $syncKeys = $msg->addChild('SyncKeys');
            $syncKeys->addChild('SyncKey', $params['SyncKey']);
        }

        $CreateCourseElementFolder = $msg->addChild('CreateCourseElementFolder');

        if ($parentSyncKey) {
            $CreateCourseElementFolder->addChild('ParentSyncKey', $parentSyncKey);
        }

        // TODO: Validate value
        if (!empty($params['CourseSyncKey'])) {
            $CreateCourseElementFolder->addChild('CourseSyncKey', $params['CourseSyncKey']);
        } else {
            throw new ApiRequestValidationException('CourseSyncKey is required');
        }

        // TODO: Validate value
        if (!empty($params['UserSyncKey'])) {
            $CreateCourseElementFolder->addChild('UserSyncKey', $params['UserSyncKey']);
        } else {
            throw new ApiRequestValidationException('UserSync key is required');
        }

        // TODO: Validate value
        if (!empty($params['Title'])) {
            $CreateCourseElementFolder->addChild('Title', $params['Title']);
        } else {
            $CreateCourseElementFolder->addChild('Title', self::DEFAULT_NAME);
        }

        if (!empty($params['Description'])) {
            $CreateCourseElementFolder->addChild('Description', (string) $params['Description']);
        }

        // True by default
        if (isset($params['Active'])) {
            $boolValue = $params['Active'] ? 'true': 'false';
            $CreateCourseElementFolder->addChild('Active', $boolValue);
        }

        $allowedValues = array('Inherit', 'Secure', 'Locked');
        if (!empty($params['Security'])) {
            $value = (string) $params['Security'];
            if (!in_array($value, $allowedValues)) {
                $allowedValuesStr = implode(', ', $allowedValues);
                $err = 'Security value should be one of the following: ' . $allowedValuesStr;
                throw new ApiRequestValidationException($err);
            }
            $CreateCourseElementFolder->addChild('Security', $value);
        } else {
            // 'Inherit by default
            $CreateCourseElementFolder->addChild('Security', $allowedValues[0]);
        }

        return XmlHelper::convertToPlainXml($msg);
    }
}
