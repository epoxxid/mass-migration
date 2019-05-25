<?php

require_once 'XmlHelper.php';

class SoapClientDebugger extends SoapClient
{
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        echo '<pre>';
        echo htmlentities(XmlHelper::formatXml($request, false));
//        parent::__doRequest($request, $location, $action, $version, $one_way);
        return '';
    }
}