<?php


/**
 * @method GetMessageTypes()
 * @method GetMessageResult(stdClass $request)
 * @method AddMessage(stdClass $request)
 */
class MMSoapClient extends SoapClient
{
    /** @var MMSoapClient|null */
    private static $instance;
    /** @var array  */
    private $messageTypes = array();
    /** @var MMLogger */
    private $logger;
    /** @var string */
    private static $soapHeaderNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

    /**
     * @param MMConfig $config
     * @param MMLogger $logger
     * @return MMSoapClient
     * @throws SoapFault
     */
    public static function getInstance(MMConfig $config, MMLogger $logger)
    {
        if (empty(self::$instance)) {
            self::$instance = new self($config, $logger);
        }
        return self::$instance;
    }

    /**
     * @param MMConfig $config
     * @param MMLogger $logger
     * @throws SoapFault
     */
    public function __construct(MMConfig $config, MMLogger $logger)
    {
        $this->logger = $logger;
        parent::__construct($config->getFileStreamWSDLUri(), array(
            'trace' => $config->getLogLevel() !== 'off'
        ));
        $this->initHeaders($config);
        $this->loadMessageTypes();
    }

    /**
     * Build and load valid SOAP header
     *
     * @param MMConfig $config
     * @return bool
     */
    private function initHeaders(MMConfig $config)
    {
        $userName = new SoapVar(
            $config->getApiUserName(),
            XSD_STRING,
            null,
            null,
            'Username',
            self::$soapHeaderNS
        );

        $password = new SoapVar(
            $config->getApiPassword(),
            XSD_STRING,
            null,
            null,
            'Password',
            self::$soapHeaderNS
        );

        $token = new SoapVar(
            array($userName, $password),
            SOAP_ENC_OBJECT,
            null,
            null,
            'UsernameToken',
            self::$soapHeaderNS
        );

        $header = new SoapHeader(
            self::$soapHeaderNS,
            'Security',
            new SoapVar(array($token), SOAP_ENC_OBJECT),
            '1'
        );
        return $this->__setSoapHeaders($header);
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
            $response = $this->GetMessageTypes();

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
            $this->logger->dbgXml('GetMessageTypes request', $this->__getLastRequest(), __METHOD__);
            $this->logger->dbgXml('GetMessageTypes response', $this->__getLastResponse(), __METHOD__);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), __METHOD__);
        }

        return false;
    }


    /**
     * @param $action
     * @return int|null
     */
    public function getMessageTypeIdByAction($action)
    {
        if (isset($this->messageTypes[$action])) {
            return (int)$this->messageTypes[$action];
        }
        return null;
    }
}
