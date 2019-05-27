<?php

require 'ApiResponse.php';

/**
 * Client class to interact with Org API
 */
class ApiClient
{
    /** @var SoapClient */
    private $soapClient;
    /** @var MMLogger */
    private $logger;
    /** @var array */
    private $messageTypes = array();
    /** @var string */
    private $soapHeaderNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    /** @var string */
    private $msgDataNS = 'http://schemas.datacontract.org/2004/07/Itslearning.Integration.ContentImport.Services.Entities';

    /**
     * @param MMConfig $config
     * @param MMLogger $logger
     */
    public function __construct(MMConfig $config, MMLogger $logger)
    {
        $this->logger = $logger;
        $this->initSoapClient($config);
    }

    /**
     * @param MMConfig $config
     */
    private function initSoapClient(MMConfig $config)
    {
        try {
            $clientConfig = array('trace' => $config->getLogLevel() !== 'off');
            $this->soapClient = new SoapClient($config->getWSDLUri(), $clientConfig);
            $this->soapClient->__setSoapHeaders($this->composeSoapHeader($config));
            $this->loadMessageTypes();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        }
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

            $typeId = $this->getMessageTypeIdByAction($action);

            if (!$typeId) {
                $err = "Unable to determine message type ID for action `$action`";
                throw new OrgApiRequestException($err);
            }

            $this->logger->dbg('Start sending OrgAPI message', __METHOD__);

            $request = $this->composeSoapMessage($typeId, $messageXml);

            /** @noinspection PhpUndefinedMethodInspection */
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

    /**
     * Returns an actual message result
     *
     * @param $messageId
     * @return ApiResponse|null
     * @throws Exception
     */
    public function getMessageResult($messageId, $attemptNumber = 1)
    {
        try {
            $request = new stdClass();
            $request->messageId = (int) $messageId;

            /** @noinspection PhpUndefinedMethodInspection */
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

            $Status = (string) $GetMessageResultResult->Status;
            switch ($Status) {
                case ApiResponse::STATUS_ITEM_IN_QUEUE:
                    $response = new ApiResponse($Status);
                    $response->setMessageQueueId($messageId);
                    $response->setAttemptNumber($attemptNumber);
                    break;

                case ApiResponse::STATUS_ITEM_PROCESSED:
                    $response = new ApiResponse($Status);
                    if (isset($GetMessageResultResult->StatusDetails)) {
                        $response->setItemDetails($GetMessageResultResult->StatusDetails);
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
            return null;
        }
    }

    /**
     * Fetch from OrgAPI list of available message types
     *
     * @return bool
     */
    private function loadMessageTypes()
    {
        $this->logger->dbg('Get list of message types', __METHOD__);

        // Prevent multiple request to message type loader
        if (count($this->messageTypes)) {
            $this->logger->dbg('List of message types already loaded', __METHOD__);
            return true;
        }

        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $response = $this->soapClient->GetMessageTypes();

            if (!property_exists($response, 'GetMessageTypesResult')) {
                $err = 'GetMessageTypes response does not have `GetMessageTypesResult` property';
                throw new OrgApiResponseException($err);
            }

            $result = $response->GetMessageTypesResult;

            if (!property_exists($result, 'DataMessageType')) {
                $err = 'GetMessageTypes response does not have `DataMessageType` property';
                throw new OrgApiResponseException($err);
            }

            $types = $result->DataMessageType;
            if (!$types || !is_array($types)) {
                $err = 'GetMessageTypes response have invalid list of types';
                throw new OrgApiResponseException($err);
            }

            // Build list of types
            foreach ($types as $type) {
                $this->messageTypes[$type->Name] = $type->Identifier;
            }

            if ($num = count($this->messageTypes)) {
                $this->logger->dbg("Fetched $num OrgAPI Message types", __METHOD__);
            } else {
                throw new OrgApiResponseException('No message types were loaded');
            }
            
            return true;
        } catch (OrgApiResponseException $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
            $this->logger->dbgXml('GetMessageTypes request', $this->soapClient->__getLastRequest(), __METHOD__);
            $this->logger->dbgXml('GetMessageTypes response', $this->soapClient->__getLastResponse(), __METHOD__);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        }
        
        return false;
    }

    /**
     * Build and return valid SOAP header
     *
     * @param MMConfig $config
     * @return SoapHeader
     */
    private function composeSoapHeader(MMConfig $config)
    {
        $userName = new SoapVar(
            $config->getApiUserName(),
            XSD_STRING,
            null,
            null,
            'Username',
            $this->soapHeaderNS
        );

        $password = new SoapVar(
            $config->getApiPassword(),
            XSD_STRING,
            null,
            null,
            'Password',
            $this->soapHeaderNS
        );

        $token = new SoapVar(
            array($userName, $password),
            SOAP_ENC_OBJECT,
            null,
            null,
            'UsernameToken',
            $this->soapHeaderNS
        );

        return new SoapHeader(
            $this->soapHeaderNS,
            'Security',
            new SoapVar(array($token), SOAP_ENC_OBJECT),
            '1'
        );
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

    /**
     * @param $action
     * @return int|null
     */
    private function getMessageTypeIdByAction($action)
    {
        if (isset($this->messageTypes[$action])) {
            return (int)$this->messageTypes[$action];
        }
        return null;
    }
}
