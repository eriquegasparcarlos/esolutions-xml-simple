<?php

/*
 * Contrato de entrada para perception.blade.php (Comprobante de percepción 40).
 * UBL 2.0 / Perception-1. Endpoint propio (RETENCION_BETA/PRODUCCION).
 */
return [
    'required' => [
        'document',
        'document.id',                    // P###-NUMERO
        'document.date_of_issue',
        'document.time_of_issue',
        'document.signature_uri',
        'document.signature_note',
        'document.company_number',
        'document.company_name',
        'document.customer_identity_document_type_id',
        'document.customer_number',
        'document.customer_name',
        'document.perception_type_id',    // catálogo 22
        'document.perception_percentage',
        'document.total_perception',
        'document.total_cashed',
        'document.documents',

        'document.documents.*.document_type_id',
        'document.documents.*.id',
        'document.documents.*.date_of_issue',
        'document.documents.*.currency_type_id',
        'document.documents.*.total',
        'document.documents.*.perception_amount',
        'document.documents.*.perception_date',
        'document.documents.*.net_total_cashed',
    ],

    'present' => [
        'document.company_trade_name',
        'document.observations',
        'document.documents.*.payments',
        'document.documents.*.exchange_rate',
    ],
];
