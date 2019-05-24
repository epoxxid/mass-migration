<?php


class XmlHelper
{
    /**
     * @param SimpleXMLElement $simpleXmlElement
     * @return string
     */
    public static function convertToPlainXml($simpleXmlElement)
    {
        $dom = dom_import_simplexml($simpleXmlElement);
        return $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
    }

    /**
     * @param SimpleXMLElement $simpleXmlElement
     * @param string $content
     */
    public static function addCDATAChild(&$simpleXmlElement, $content)
    {
        $dataNode = dom_import_simplexml($simpleXmlElement);
        $domParentNode = $dataNode->ownerDocument;
        $CDATA = $domParentNode->createCDATASection($content);
        $dataNode->appendChild($CDATA);
    }
}
