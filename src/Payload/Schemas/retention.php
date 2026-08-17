<?php

/*
 * Contrato de entrada para retention.blade.php (Comprobante de retención 20).
 * UBL 2.0 / Retention-1. Endpoint propio (RETENCION_BETA/PRODUCCION).
 */
return [
    'required' => [
        'document',
        'document.id',                    // R###-NUMERO
        'document.date_of_issue',
        'document.time_of_issue',
        'document.signature_uri',
        'document.signature_note',
        'document.company_number',
        'document.company_name',
        'document.supplier_identity_document_type_id',
        'document.supplier_number',
        'document.supplier_name',
        'document.retention_type_id',     // catálogo 23
        'document.retention_percentage',
        'document.total_retention',
        'document.total_paid',
        'document.documents',

        'document.documents.*.document_type_id',
        'document.documents.*.id',
        'document.documents.*.date_of_issue',
        'document.documents.*.currency_type_id',
        'document.documents.*.total',
        'document.documents.*.retention_amount',
        'document.documents.*.retention_date',
        'document.documents.*.net_total_paid',
    ],

    'present' => [
        'document.company_trade_name',
        'document.observations',
        'document.documents.*.payments',
        'document.documents.*.exchange_rate',
    ],
];
