<?php

require 'ApiClient.php';
require_once dirname(__DIR__) . '/Helpers/XmlHelper.php';

/**
 * Service for sending requests for creating folders
 */
class ApiFileStream
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

    public function uploadFile()
    {
//        try {
//            $xml = $this->composeMessageXml($parentSyncKey, $params);
//
//            $folderTitle = isset($params['Title']) ? $params['Title'] : self::DEFAULT_NAME;
//            $infoMsg = "Sending request to create folder `$folderTitle`";
//            $infoMsg .= $parentSyncKey ? " in parent with sync key `$parentSyncKey`" : ' in root resources folder';
//            $this->logger->info($infoMsg, __METHOD__);
//
//            $response = $this->apiClient->sendMessage(self::ACTION_CREATE_FOLDER, $xml);
//
//            if ($response->isStatusInQueue()) {
//                $queueId = $response->getMessageQueueId();
//                $this->logger->info("Request for creating a folder has added to queue with ID = $queueId", __METHOD__);
//            }
//
//            return $response;
//        } catch (OrgApiRequestException $e) {
//            $response = new ApiResponse(ApiResponse::STATUS_INVALID_REQUEST);
//            $response->setExplanationMessage($e->getMessage());
//            return $response;
//        } catch (Throwable $e) {
//            $this->logger->error($e->getMessage(), __METHOD__);
//            throw $e;
//        }

        $file = __DIR__ . '/../../files/Lavrov1.jpg';

        // First Example
        $encodedFile = $this->getDataURI($file);
        $content =  str_replace(':', '', $encodedFile);
        $content = str_replace('%', '', $content);
        $content = str_replace('?', '', $content);


        $parameters = [
            'ExtensionId' => 5000,
            'Name' => 'Lavrov1.jpg',
            'Content' => $content,
        ];
//        echo ($return = $this->upload($data)) ? "File Uploaded : $return bytes"."\n" : "Error Uploading Files";

        try {
            $ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
            $headerbody = array('UsernameKey'=>array('Username'=>$UserID,
                'Password'=>$Pwd));

            $header = new SOAPHeader($ns, 'RequestorCredentials', $headerbody);
            $client = new SoapClient("https://f19-fronter.itslbeta.com:4421/FileStreamService.svc?wsdl");
//            $xmlRequest = file_get_contents(__DIR__ . '/../../orgapi-xml-examples/UploadFile.xml');
            $func = $client->__getFunctions();
//            print_r($func);
//            $params = '<Content>
//                <inc:Include href="cid:Lavrov1.jpg" xmlns:inc="http://www.w3.org/2004/08/xop/include"/>
//            </Content>';

            $upl = $client->UploadFile($parameters);
            echo $upl . "\n";
//            $response = $client->__doRequest($xmlRequest, 'f19-fronter.itslbeta.com:4421', 'UploadFile', 1);
//            echo $response;
        } catch (SOAPFault $f) {
            print_r($f) . "\n";
        }

    }

    public function upload($args)
    {
        $file = __DIR__ . '/../../files/' . $args['name'];
        return file_put_contents($file, file_get_contents($args['data']));

    }

    public function getDataURI($image, $mime = '')
    {
        return base64_encode(file_get_contents($image));
//                mime_content_type($image) : $mime)  .
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
            $CreateCourseElementFolder->addChild('Description', (string)$params['Description']);
        }

        // True by default
        if (isset($params['Active'])) {
            $boolValue = $params['Active'] ? 'true' : 'false';
            $CreateCourseElementFolder->addChild('Active', $boolValue);
        }

        $allowedValues = array('Inherit', 'Secure', 'Locked');
        if (!empty($params['Security'])) {
            $value = (string)$params['Security'];
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
