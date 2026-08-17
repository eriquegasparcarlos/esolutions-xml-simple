<?php

/*
 * Contrato de entrada para summary.blade.php (Resumen diario de boletas RC).
 * Documentación: docs/payloads/summary.md
 * UBL 2.0 / CustomizationID 1.1 — payload propio, NO deriva de invoice.
 */
return [
    'required' => [
        'document',
        'document.identifier',           // RC-YYYYMMDD-###
        'document.date_of_reference',    // fecha de emisión de los comprobantes
        'document.date_of_issue',        // fecha de generación del resumen
        'document.signature_uri',
        'document.signature_note',
        'document.company_number',
        'document.company_name',
        'document.documents',

        'document.documents.*.document_type_id',   // 03 / 07 / 08
        'document.documents.*.id',                 // SERIE-NUMERO
        'document.documents.*.customer_number',
        'document.documents.*.customer_identity_document_type_id',
        'document.documents.*.status_id',          // catálogo 19: 1 adicionar, 2 modificar, 3 anular
        'document.documents.*.currency_type_id',
        'document.documents.*.total',
        'document.documents.*.total_taxed',
        'document.documents.*.total_exonerated',
        'document.documents.*.total_unaffected',
        'document.documents.*.total_exportation',
        'document.documents.*.total_free',
        'document.documents.*.total_charge',
        'document.documents.*.total_igv',
        'document.documents.*.total_isc',
        'document.documents.*.total_other_taxes',
        'document.documents.*.total_plastic_bag_taxes',
    ],

    'present' => [
        'document.documents.*.affected_document',  // requerido solo para líneas 07/08
    ],
];
