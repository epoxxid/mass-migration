<?php

require_once 'XmlHelper.php';

class OrgApiFolderCreator
{
    const DEFAULT_NAME = '__Fronter_exported__';

    /** @var OrgApiClient */
    private $apiClient;

    /** @var bool */
    private $debug;

    /**
     * OrgApiFolderCreator constructor.
     * @param OrgApiClient $apiClient
     * @param bool $debug
     */
    public function __construct($apiClient, $debug = false)
    {
        $this->apiClient = $apiClient;
        $this->debug = $debug;

        // Request available message types
    }

    /**
     * @param null $parentSyncKey
     * @param array $params
     * @throws Exception
     */
    public function createFolder($parentSyncKey = null, array $params = array())
    {
        $xml = $this->composeMessageXml($parentSyncKey, $params);
        $this->apiClient->sendMessage(2, $xml);

        if ($this->debug) {
            echo '<pre>';
            $this->apiClient->dumpRequestXml(true);
            $this->apiClient->dumpResponseXml(true);
        }
    }

    public function createFolderObject($parentSyncKey = null, array $params = array())
    {
        $msg = new stdClass();

        // Force sync key for the folder, TODO: Validate
        if (isset($params['SyncKey'])) {
            $msg->syncKeys = new \ArrayObject();
            $syncKey = new SoapVar(
                $params['SyncKey'],
                XSD_STRING,
                null,
                null,
                'SyncKey'
            );
            $msg->syncKeys->append($syncKey);
        }

        $this->apiClient->sendMessage(2, new SoapVar($msg, SOAP_ENC_OBJECT, null, null, 'Message'));

        if ($this->debug) {
            echo '<pre>';
            $this->apiClient->dumpRequestXml(true);
            $this->apiClient->dumpResponseXml(true);
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
            throw new \Exception('CourseSyncKey is required');
        }

        // TODO: Validate value
        if (!empty($params['UserSyncKey'])) {
            $CreateCourseElementFolder->addChild('UserSyncKey', $params['UserSyncKey']);
        } else {
            throw new \Exception('UserSync key is required');
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
                throw new \Exception('Security value should be one of the following: ' . $allowedValuesStr);
            }
            $CreateCourseElementFolder->addChild('Security', $value);
        } else {
            // 'Inherit by default
            $CreateCourseElementFolder->addChild('Security', $allowedValues[0]);
        }

        return XmlHelper::convertToPlainXml($msg);
    }
}
