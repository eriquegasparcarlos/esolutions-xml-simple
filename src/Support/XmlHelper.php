<?php

namespace ESolutions\XmlSimple\Support;

use DOMDocument;
use DOMXPath;
use Exception;

/**
 * Helper class for loading and querying XML documents.
 */
class XmlHelper
{
    /**
     * Loads an XML string into a DOMDocument and returns it.
     *
     * @param string $xml
     * @return DOMDocument
     * @throws Exception if the XML is invalid.
     */
    public static function loadDom(string $xml): DOMDocument
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();

        if (!$doc->loadXML($xml)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new Exception(self::formatErrors($errors));
        }

        libxml_clear_errors();
        return $doc;
    }

    /**
     * Formats libxml error messages into a single string.
     *
     * @param array $errors
     * @return string
     */
    protected static function formatErrors(array $errors): string
    {
        return implode("\n", array_map(function ($e) {
            return "Línea {$e->line}: {$e->message}";
        }, $errors));
    }

    /**
     * Returns a DOMXPath instance for querying the given document.
     *
     * @param DOMDocument $doc
     * @return DOMXPath
     */
    public static function getXPath(DOMDocument $doc): DOMXPath
    {
        return new DOMXPath($doc);
    }

    /**
     * Returns the value of the first node that matches the given XPath expression.
     *
     * @param DOMXPath $xpath
     * @param string $expression
     * @return string|null
     */
    public static function getNodeValue(DOMXPath $xpath, string $expression): ?string
    {
        $nodes = $xpath->query($expression);
        return ($nodes->length > 0) ? $nodes->item(0)->nodeValue : null;
    }

    /**
     * Returns an array of values of all nodes that match the given XPath expression.
     *
     * @param DOMXPath $xpath
     * @param string $expression
     * @return array
     */
    public static function getNodeValues(DOMXPath $xpath, string $expression): array
    {
        $results = [];
        $nodes = $xpath->query($expression);
        foreach ($nodes as $node) {
            $results[] = $node->nodeValue;
        }
        return $results;
    }
}
