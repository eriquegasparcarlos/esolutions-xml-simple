<?php

namespace ESolutions\XmlSimple\Support;

class XmlFormatter
{
    public static function format(string $xml, bool $formatOutput = true, bool $declaration = true): string
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = $formatOutput;

        $ok = $dom->loadXML($xml, LIBXML_NONET);

        if (!$ok) {
            // si falla, devuelve el original (pero puedes loguear errores)
            // \Log::warning('XML format failed', ['errors' => libxml_get_errors()]);
            libxml_clear_errors();
            return $xml;
        }

        libxml_clear_errors();

        return $declaration
            ? $dom->saveXML()
            : $dom->saveXML($dom->documentElement);
    }
}
