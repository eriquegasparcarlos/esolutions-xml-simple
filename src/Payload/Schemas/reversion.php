<?php

/*
 * Contrato de entrada para la REVERSIÓN (RR): comunicación de baja de
 * comprobantes de retención (20) y percepción (40).
 * Mismo XML que la baja RA (voided.blade.php, UBL 2.0 / CustomizationID 1.0);
 * difieren el prefijo del identifier (RR-) y los tipos de documento admitidos
 * en las líneas — eso lo valida el ruleset reversion.php (2673/2308) y el
 * servidor SUNAT. Se envía al endpoint de retenciones (SenderConfig con
 * document_type_id 'RR'), no al FE estándar.
 * Documentación: docs/payloads/voided.md
 */
return [
    'required' => [
        'document',
        'document.identifier',           // RR-YYYYMMDD-###
        'document.date_of_reference',    // fecha de emisión de los CRE/CPE dados de baja
        'document.date_of_issue',        // fecha de generación de la comunicación
        'document.signature_uri',
        'document.signature_note',
        'document.company_number',
        'document.company_name',
        'document.documents',

        'document.documents.*.document_type_id',  // '20' o '40'
        'document.documents.*.series',            // R### / P###
        'document.documents.*.number',
    ],

    'present' => [
        'document.documents.*.description',  // motivo de la reversión
    ],
];
