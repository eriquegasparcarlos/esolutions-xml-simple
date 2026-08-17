<?php

namespace ESolutions\XmlSimple\Sign\Hash;

use DOMDocument;
use DOMXPath;

class XmlHash
{
    /**
     * Extrae valores útiles post-firma:
     * - digestValue (lo típico para documents.hash)
     * - signatureValue
     * - x509Certificate (base64)
     */
    public static function extract(string $signedXml): array
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($signedXml);

        $xp = new DOMXPath($doc);
        $xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $digest = self::nodeValue($xp, '//ds:Reference/ds:DigestValue');
        $sigVal = self::nodeValue($xp, '//ds:SignatureValue');
        $cert   = self::nodeValue($xp, '//ds:X509Certificate');

        return [
            'digest_value' => $digest,          // <- GUARDAR COMO documents.hash (si así lo manejas)
            'signature_value' => $sigVal,       // opcional
            'x509_certificate' => $cert,        // opcional
        ];
    }

    private static function nodeValue(DOMXPath $xp, string $query): ?string
    {
        $n = $xp->query($query);
        if ($n && $n->length > 0) {
            $v = trim((string)$n->item(0)->nodeValue);
            return $v !== '' ? $v : null;
        }
        return null;
    }
}
