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

    public static function formatXml($xml, $encode = false)
    {
        $xml = preg_replace('~>\s*<~', ">\n<", $xml);

        $level = 0;
        $output = array();
        $prevType = null;
        foreach (explode("\n", $xml) as $line) {
            $level = preg_match('~^</~', $line) ? $level - 1 : $level + 1;
            $indent = str_repeat(' ', $level < 0 ? 0 : $level);
            $output[] = $indent . ($encode ? htmlentities($line) : $line);
        }
        return trim(implode("\n", $output));
    }
}
