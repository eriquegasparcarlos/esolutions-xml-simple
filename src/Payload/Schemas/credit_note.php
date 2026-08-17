<?php

/*
 * Contrato de entrada para credit-note.blade.php (Nota de crédito 07).
 * Documentación: docs/payloads/credit-note.md
 *
 * Igual que invoice, más el comprobante afectado y el motivo (catálogo 09);
 * sin date_of_due, prepayments, detraction, perception, retention ni
 * attributes de línea (la plantilla de NC no los emite).
 */
return [
    'required' => [
        'document',

        // Cabecera
        'document.id',
        'document.date_of_issue',
        'document.time_of_issue',
        'document.operation_type_is_exportation',
        'document.document_type_id',
        'document.currency_type_id',
        'document.payment_condition_id',

        // Nota: comprobante afectado + motivo
        'document.affected_document',
        'document.affected_document.id',
        'document.affected_document.document_type_id',
        'document.note_type_id',

        // Firma (metadata UBL)
        'document.signature_uri',
        'document.signature_note',

        // Emisor / receptor
        'document.company_identity_document_type_id',
        'document.company_number',
        'document.company_name',
        'document.customer_identity_document_type_id',
        'document.customer_number',
        'document.customer_name',

        // Colecciones
        'document.legends',
        'document.guides',
        'document.related',
        'document.charges',
        'document.discounts',
        'document.fee',
        'document.items',

        // Totales
        'document.total_taxes',
        'document.total_isc',
        'document.total_base_isc',
        'document.total_taxed',
        'document.total_igv',
        'document.total_unaffected_init',
        'document.total_prepayment_unaffected',
        'document.total_exonerated_init',
        'document.total_prepayment_exonerated',
        'document.total_free',
        'document.total_igv_free',
        'document.total_exportation_init',
        'document.total_other_taxes',
        'document.total_base_other_taxes',
        'document.total_plastic_bag_taxes',
        'document.total_value',
        'document.total_discount_no_base',
        'document.total_charge',
        'document.total_prepayment',
        'document.total_payable',

        // Líneas
        'document.items.*.quantity',
        'document.items.*.total_value',
        'document.items.*.price_type_id',
        'document.items.*.unit_value',
        'document.items.*.charges',
        'document.items.*.discounts',
        'document.items.*.total_taxes',
        'document.items.*.total_isc',
        'document.items.*.total_base_igv',
        'document.items.*.total_igv',
        'document.items.*.percentage_igv',
        'document.items.*.affectation_igv_type_id',
        'document.items.*.total_other_taxes',
        'document.items.*.total_plastic_bag_taxes',
        'document.items.*.name_parts',
    ],

    // SUNAT (2135/2136) exige cac:DiscrepancyResponse/cbc:Description con un
    // sustento de al menos 2 caracteres: no admite null ni cadena vacía.
    'non_empty' => [
        'document.note_description',
    ],

    'present' => [
        'document.purchase_order',
        'document.company_trade_name',
        'document.fee_total',
        'document.items.*.price_amount_01',
        'document.items.*.price_amount_02',
        'document.items.*.internal_id',
        'document.items.*.item_code',
    ],
];
