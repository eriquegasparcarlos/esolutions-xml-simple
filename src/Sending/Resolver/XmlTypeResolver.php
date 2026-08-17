<?php

namespace ESolutions\XmlSimple\Sending\Resolver;

/**
 * Decide la operación SOAP inspeccionando el XML firmado (localName de la
 * raíz), sin que el consumidor tenga que indicar el tipo — patrón del
 * Facturalo legacy / Greenter.
 */
class XmlTypeResolver
{
    public const OPERATION_BILL = 'sendBill';
    public const OPERATION_SUMMARY = 'sendSummary';

    /** localName raíz => operación SOAP */
    private const MAP = [
        'Invoice' => self::OPERATION_BILL,
        'SelfBilledInvoice' => self::OPERATION_BILL, // liquidación de compra (04)
        'CreditNote' => self::OPERATION_BILL,
        'DebitNote' => self::OPERATION_BILL,
        'DespatchAdvice' => self::OPERATION_BILL,
        'Retention' => self::OPERATION_BILL,
        'Perception' => self::OPERATION_BILL,
        'SummaryDocuments' => self::OPERATION_SUMMARY,
        'VoidedDocuments' => self::OPERATION_SUMMARY,
    ];

    /**
     * @return string self::OPERATION_BILL | self::OPERATION_SUMMARY
     * @throws \RuntimeException si el XML no es un comprobante UBL reconocido
     */
    public function resolveOperation(string $signedXml): string
    {
        $root = $this->rootLocalName($signedXml);

        if (!isset(self::MAP[$root])) {
            throw new \RuntimeException("Tipo de documento UBL no reconocido para envío: raíz <{$root}>.");
        }

        return self::MAP[$root];
    }

    public function rootLocalName(string $signedXml): string
    {
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadXML($signedXml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$ok || !$doc->documentElement) {
            throw new \RuntimeException('El contenido no es un XML válido.');
        }

        return $doc->documentElement->localName;
    }
}
