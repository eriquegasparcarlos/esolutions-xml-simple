<?php

/*
 * Contrato de entrada para despatch.blade.php (Guía de remisión remitente 09).
 * UBL 2.1 DespatchAdvice. OJO: SUNAT migró el ENVÍO de guías a la API REST
 * (GRE 2022) — este template genera el XML; el canal SOAP está deprecado.
 */
return [
    'required' => [
        'document',
        'document.id',
        'document.date_of_issue',
        'document.time_of_issue',
        'document.document_type_id',     // '09'
        'document.signature_uri',
        'document.signature_note',
        'document.company_number',
        'document.company_name',
        'document.customer_identity_document_type_id',
        'document.customer_number',
        'document.customer_name',

        // Traslado
        'document.transfer_reason_type_id',   // catálogo 20
        'document.weight_unit_type_id',       // KGM
        'document.total_weight',
        'document.transport_mode_type_id',    // catálogo 18: 01 público, 02 privado
        'document.date_of_shipping',

        // Direcciones
        'document.origin_location_id',
        'document.origin_address',
        'document.delivery_location_id',
        'document.delivery_address',

        'document.items',
        'document.items.*.quantity',
        'document.items.*.name_parts',
    ],

    'present' => [
        'document.observations',
        'document.transfer_reason_description',
        'document.is_transport_category_m1l',
        'document.plate_number',
        // Transporte público (01)
        'document.carrier_identity_document_type_id',
        'document.carrier_number',
        'document.carrier_name',
        'document.carrier_mtc_number',
        // Transporte privado (02)
        'document.driver_identity_document_type_id',
        'document.driver_number',
        'document.driver_first_name',
        'document.driver_family_name',
        'document.driver_license_number',
        // Establecimientos anexos
        'document.origin_establishment_code',
        'document.origin_establishment_ruc',
        'document.delivery_establishment_code',
        'document.delivery_establishment_ruc',
        // Líneas
        'document.items.*.unit_type_id',
        'document.items.*.internal_id',
        'document.items.*.item_code',
    ],
];
