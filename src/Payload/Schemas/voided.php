<?php

/*
 * Contrato de entrada para voided.blade.php (Comunicación de baja RA).
 * Documentación: docs/payloads/voided.md
 * UBL 2.0 / CustomizationID 1.0 — payload propio, NO deriva de invoice.
 */
return [
    'required' => [
        'document',
        'document.identifier',           // RA-YYYYMMDD-###
        'document.date_of_reference',    // fecha de emisión de los comprobantes dados de baja
        'document.date_of_issue',        // fecha de generación de la comunicación
        'document.signature_uri',
        'document.signature_note',
        'document.company_number',
        'document.company_name',
        'document.documents',

        'document.documents.*.document_type_id',
        'document.documents.*.series',
        'document.documents.*.number',
    ],

    'present' => [
        'document.documents.*.description',  // motivo de la baja
    ],
];
