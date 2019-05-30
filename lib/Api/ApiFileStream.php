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

//        $file = __DIR__ . '/../../files/Lavrov1.jpg';
//
//        // First Example
//        $encodedFile = $this->getDataURI($file);
//        $content =  str_replace(':', '', $encodedFile);
//        $content = str_replace('%', '', $content);
//        $content = str_replace('?', '', $content);
//
//
//        $parameters = [
//            'Content' => $content,
//        ];
//        echo ($return = $this->upload($data)) ? "File Uploaded : $return bytes"."\n" : "Error Uploading Files";

        $raw = '<StreamMessage xmlns="http://tempuri.org/"><Content><inc:Include href="cid:Lavrov1.jpg" xmlns:inc="http://www.w3.org/2004/08/xop/include"/></Content></StreamMessage>';
        $xml = new SimpleXMLElement($raw);

        try {
            $this->apiClient->uploadFile($xml);

        } catch (SOAPFault $f) {
            print_r($f) . "\n";
        }

        echo  XmlHelper::formatXml(($this->apiClient->getLastRequest()));

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
}
