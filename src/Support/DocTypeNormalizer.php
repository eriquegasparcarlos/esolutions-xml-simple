<?php

namespace ESolutions\XmlSimple\Support;

class DocTypeNormalizer
{
    /**
     * Normaliza el tipo de documento a la clave usada en config
     * (views/schemas) y en Payload/Schemas.
     *
     * Acepta:
     * - códigos SUNAT: 01, 03, 07, 08, 09, 20, 40, RC, RA, RR
     * - alias: invoice, boleta, factura, credit-note, credit_note, ...
     */
    public static function normalize(string $type): string
    {
        $t = trim(strtolower($type));

        // Códigos SUNAT (catálogo 01 + resúmenes)
        $codes = [
            '01' => 'invoice',
            '03' => 'invoice',   // boleta usa la misma plantilla/XSD que factura
            '04' => 'purchase_settlement', // liquidación de compra (SelfBilledInvoice)
            '07' => 'credit_note',
            '08' => 'debit_note',
            '09' => 'despatch',
            '31' => 'despatch_carrier',
            '20' => 'retention',
            '40' => 'perception',
            'rc' => 'summary',
            'ra' => 'voided',
            'rr' => 'reversion',
        ];
        if (isset($codes[$t])) {
            return $codes[$t];
        }

        // normaliza separadores
        $t = str_replace('-', '_', $t);

        $aliases = [
            'factura' => 'invoice',
            'boleta' => 'invoice',
            'invoice' => 'invoice',
            'liquidacion' => 'purchase_settlement',
            'liquidacion_compra' => 'purchase_settlement',
            'purchase_settlement' => 'purchase_settlement',
            'self_billed_invoice' => 'purchase_settlement',
            'credit_note' => 'credit_note',
            'debit_note' => 'debit_note',
            'despatch' => 'despatch',
            'dispatch' => 'despatch',
            'guia' => 'despatch',
            'despatch_carrier' => 'despatch_carrier',
            'guia_transportista' => 'despatch_carrier',
            'transportista' => 'despatch_carrier',
            'summary' => 'summary',
            'resumen' => 'summary',
            'voided' => 'voided',
            'baja' => 'voided',
            // RR: comunicación de baja de retenciones/percepciones. qpospe la
            // llama voided_retention; SUNAT "reversión".
            'reversion' => 'reversion',
            'voided_retention' => 'reversion',
            'baja_retencion' => 'reversion',
            'retention' => 'retention',
            'retencion' => 'retention',
            'perception' => 'perception',
            'percepcion' => 'perception',
        ];

        return $aliases[$t] ?? $t;
    }
}
