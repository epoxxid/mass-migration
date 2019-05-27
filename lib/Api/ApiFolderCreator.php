<?php

require 'ApiClient.php';
require_once dirname(__DIR__) . '/Helpers/XmlHelper.php';

/**
 * Service for sending requests for creating folders
 */
class ApiFolderCreator
{
    /** @var string */
    const DEFAULT_NAME = '__Fronter_exported__';
    /** @var string */
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
     * @throws Throwable
     */
    public function createFolder($parentSyncKey = null, array $params = array())
    {
        try {
            $xml = $this->composeMessageXml($parentSyncKey, $params);

            $folderTitle = isset($params['Title']) ? $params['Title'] : self::DEFAULT_NAME;
            $infoMsg = "Sending request to create folder `$folderTitle`";
            $infoMsg .= $parentSyncKey ? " in parent with sync key `$parentSyncKey`" : ' in root resources folder';
            $this->logger->info($infoMsg, __METHOD__);

            $response = $this->apiClient->sendMessage(self::ACTION_CREATE_FOLDER, $xml);

            if ($response->isStatusInQueue()) {
                $queueId = $response->getMessageQueueId();
                $this->logger->info("Request for creating a folder has added to queue with ID = $queueId", __METHOD__);
            }

            return $response;
        } catch (OrgApiRequestException $e) {
            $response = new ApiResponse(ApiResponse::STATUS_INVALID_REQUEST);
            $response->setExplanationMessage($e->getMessage());
            return $response;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Compose and return valid XML string
     *
     * @param string|null $parentSyncKey
     * @param array $params
     * @return string
     *
     * @throws OrgApiRequestException
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

        // TODO: Validate value
        if (!empty($params['CourseSyncKey'])) {
            $CreateCourseElementFolder->addChild('CourseSyncKey', $params['CourseSyncKey']);
        } else {
            throw new OrgApiRequestException('CourseSyncKey is required');
        }

        // TODO: Validate value
        if ($parentSyncKey) {
            $CreateCourseElementFolder->addChild('ParentSyncKey', $parentSyncKey);
        }

        // TODO: Validate value
        if (!empty($params['UserSyncKey'])) {
            $CreateCourseElementFolder->addChild('UserSyncKey', $params['UserSyncKey']);
        } else {
            throw new OrgApiRequestException('UserSync key is required');
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
                throw new OrgApiRequestException($err);
            }
            $CreateCourseElementFolder->addChild('Security', $value);
        } else {
            // 'Inherit by default
            $CreateCourseElementFolder->addChild('Security', $allowedValues[0]);
        }

        return XmlHelper::convertToPlainXml($msg);
    }
}
