<?php

/*
 * Contrato de entrada para despatch-carrier.blade.php (Guía de remisión
 * TRANSPORTISTA, tipo 31). UBL 2.1 DespatchAdvice, GRE 2022.
 *
 * A diferencia de la remitente (09): el EMISOR es el transportista, aparecen
 * remitente (subcontrata de origen) y destinatario, vehículo(s) y conductor(es)
 * principal + secundarios. El motivo de traslado NO lo declara el transportista.
 */
return [
    'required' => [
        'document',
        'document.id',
        'document.date_of_issue',
        'document.time_of_issue',
        'document.document_type_id',     // '31'
        'document.signature_uri',
        'document.signature_note',

        // Emisor = transportista
        'document.carrier_number',       // RUC del transportista
        'document.carrier_name',

        // Remitente (quien envía la mercadería)
        'document.sender_identity_document_type_id',
        'document.sender_number',
        'document.sender_name',

        // Destinatario
        'document.customer_identity_document_type_id',
        'document.customer_number',
        'document.customer_name',

        // Traslado
        'document.weight_unit_type_id',       // KGM
        'document.total_weight',
        'document.transport_mode_type_id',    // catálogo 18: 01 público
        'document.date_of_shipping',

        // Vehículo principal + TUC
        'document.main_plate_number',
        'document.main_vehicle_tuc',    // Nº TUC / Constancia de Inscripción Vehicular

        // Conductor principal
        'document.driver_identity_document_type_id',
        'document.driver_number',
        'document.driver_first_name',
        'document.driver_family_name',
        'document.driver_license_number',

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
        'document.packages_number',           // total de bultos
        'document.carrier_mtc_number',         // registro MTC del transportista
        'document.container_number',           // contenedor (importación)
        'document.port_code',                  // puerto (importación/exportación)
        // Vehículos secundarios: [{plate_number, tuce?}]
        'document.secondary_vehicles',
        // Conductores secundarios: [{identity_document_type_id, number, first_name, family_name, license_number}]
        'document.secondary_drivers',
        // Líneas
        'document.items.*.unit_type_id',
        'document.items.*.internal_id',
        'document.items.*.item_code',
    ],
];
