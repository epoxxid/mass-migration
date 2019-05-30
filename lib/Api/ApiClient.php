<?php

require_once 'MMSoapClient.php';
require_once 'ApiResponse.php';

/**
 * Client class to interact with Org API
 */
class ApiClient
{
    /** @var MMSoapClient */
    private $soapClient;
    /** @var MMLogger */
    private $logger;

    /** @var string */
    private $msgDataNS = 'http://schemas.datacontract.org/2004/07/Itslearning.Integration.ContentImport.Services.Entities';

    /**
     * @param MMConfig $config
     * @param MMLogger $logger
     */
    public function __construct(MMConfig $config, MMLogger $logger)
    {
        try {
            // Singleton to prevent multiple init data loading
            $this->soapClient = MMSoapClient::getInstance($config, $logger);
        } catch (Throwable $e) {
            $err = 'Unable to instantiate SOAP client: ' . $e->getMessage();
            $logger->error($err, __METHOD__);
        }
        $this->logger = $logger;
    }

    /**
     * @param string $action
     * @param string $messageXml
     * @param int $attemptNumber
     * @return ApiResponse
     * @throws Exception
     */
    public function sendMessage($action, $messageXml, $attemptNumber = 1)
    {
        try {
            $typeId = $this->soapClient->getMessageTypeIdByAction($action);

            if (!$typeId) {
                $err = "Unable to determine message type ID for action `$action`";
                throw new OrgApiRequestException($err);
            }

            $this->logger->dbg('Start sending OrgAPI message', __METHOD__);

            $request = $this->composeSoapMessage($typeId, $messageXml);

            $response = $this->soapClient->AddMessage($request);

            if (!property_exists($response, 'AddMessageResult')) {
                $err = 'AddMessage() response does not have `AddMessageResult` property';
                throw new OrgApiResponseException($err);
            }

            $AddMessageResult = $response->AddMessageResult;

            if (!property_exists($AddMessageResult, 'Status')) {
                $err = 'AddMessage() response does not have `Status` property';
                throw new OrgApiResponseException($err);
            }

            if (!property_exists($AddMessageResult, 'MessageId')) {
                $err = 'AddMessage() response does not have `MessageId` property';
                throw new OrgApiResponseException($err);
            }

            $messageQueueId = $AddMessageResult->MessageId;
            $Status = $AddMessageResult->Status;

            if (!is_numeric($messageQueueId) || $Status !== ApiResponse::STATUS_ITEM_IN_QUEUE) {
                throw new OrgApiResponseException('API did not processed item properly');
            }

            $response = new ApiResponse(ApiResponse::STATUS_ITEM_IN_QUEUE);
            $response->setMessageQueueId($messageQueueId);
            $response->setAttemptNumber($attemptNumber);
            return $response;
        } catch (OrgApiResponseException $e) {
            $this->logger->error($e->getMessage(), __METHOD__);

            // We dump the response XML make debug easier
            $this->logger->dbgXml('AddMessage request', $this->soapClient->__getLastRequest(), __METHOD__);
            $this->logger->dbgXml('AddMessage response', $this->soapClient->__getLastResponse(), __METHOD__);

            $response = new ApiResponse(ApiResponse::STATUS_REQUEST_FAILED);
            $response->setAttemptNumber($attemptNumber);
            return $response;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);

            $response = new ApiResponse(ApiResponse::STATUS_INVALID_REQUEST);
            $response->setExplanationMessage($e->getMessage());
            return $response;
        }
    }

    public function uploadFile($xml) {

        return $this->soapClient->UploadFile($xml);

    }

    public function getLastRequest() {

        return $this->soapClient->__getLastRequest();

    }
    /**
     * @param int $messageId
     * @param int $attemptNumber
     * @return ApiResponse
     * @throws Exception
     */
    public function getMessageResult($messageId, $attemptNumber = 1)
    {
        try {
            $request = new stdClass();
            $request->messageId = (int)$messageId;

            $response = $this->soapClient->GetMessageResult($request);

            if (!property_exists($response, 'GetMessageResultResult')) {
                $err = 'AddMessage() response does not have `GetMessageResultResult` property';
                throw new OrgApiResponseException($err);
            }

            $GetMessageResultResult = $response->GetMessageResultResult;

            if (!property_exists($GetMessageResultResult, 'Status')) {
                $err = 'AddMessage() response does not have `Status` property';
                throw new OrgApiResponseException($err);
            }

            $Status = (string)$GetMessageResultResult->Status;
            switch ($Status) {
                case ApiResponse::STATUS_ITEM_IN_QUEUE:
                    $response = new ApiResponse($Status);
                    $response->setMessageQueueId($messageId);
                    $response->setAttemptNumber($attemptNumber);
                    break;

                case ApiResponse::STATUS_ITEM_PROCESSED:
                    $response = new ApiResponse($Status);
                    if (!empty($GetMessageResultResult->StatusDetails)) {
                        if (!empty($GetMessageResultResult->StatusDetails->DataMessageStatusDetail)) {
                            $dataObject = $GetMessageResultResult->StatusDetails->DataMessageStatusDetail;
                            $response->setItemDetails($dataObject);
                        }
                    }
                    break;

                default:
                    throw new OrgApiResponseException("API responded with status `$Status`");
            }
            return $response;
        } catch (OrgApiResponseException $e) {
            $this->logger->dbgXml('GetMessageResult request', $this->soapClient->__getLastRequest(), __METHOD__);
            $this->logger->dbgXml('GetMessageResult response', $this->soapClient->__getLastResponse(), __METHOD__);
            $this->logger->error($e->getMessage(), __METHOD__);

            $response = new ApiResponse(ApiResponse::STATUS_REQUEST_FAILED);
            $response->setExplanationMessage($e->getMessage());
            $response->setAttemptNumber($attemptNumber);
            return $response;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);

            $response = new ApiResponse(ApiResponse::STATUS_REQUEST_FAILED);
            $response->setExplanationMessage($e->getMessage());
            return $response;
        }
    }

    /**
     * Wrap given data into SOAP Message
     *
     * @param int $type
     * @param string $messageXml
     * @return stdClass SOAP-ready class
     */
    private function composeSoapMessage($type, $messageXml)
    {
        $dataMessage = new stdClass();
        $dataMessage->Data = new SoapVar(
            $messageXml,
            XSD_STRING,
            null,
            null,
            'Data',
            $this->msgDataNS
        );

        $dataMessage->Type = new SoapVar(
            $type,
            XSD_INT,
            null,
            null,
            'Type',
            $this->msgDataNS
        );

        $request = new stdClass();
        $request->dataMessage = new SoapVar(
            $dataMessage,
            SOAP_ENC_OBJECT,
            null,
            null,
            'dataMessage',
            $this->msgDataNS
        );

        return $request;
    }

    private function composeUploadContent()
    {
        $content = new stdClass();
        $content->Content = new SoapVar(
            '<inc:Include href="cid:Lavrov1.jpg" xmlns:inc="http://www.w3.org/2004/08/xop/include"/>',
            XSD_ANYXML,
            null,
            null,
            'Content',
            ''
        );

        $request = new stdClass();
        $request->StreamMessage = new SoapVar(
            $content,
            SOAP_ENC_OBJECT,
            null,
            null,
            'StreamMessage',
            'http://tempuri.org/'
        );

        return $request;
    }
}
