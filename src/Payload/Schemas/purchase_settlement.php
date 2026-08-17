<?php

/*
 * Contrato de entrada para purchase-settlement.blade.php
 * (Liquidación de compra 04 — UBL SelfBilledInvoice).
 * Documentación completa: docs/payloads/purchase-settlement.md
 *
 * En una liquidación de compra la EMPRESA (con RUC) compra a un PROVEEDOR sin
 * RUC (persona natural: DNI u otro doc del catálogo 06) y emite el comprobante.
 * En el UBL SelfBilledInvoice el "customer" es la empresa (quien se
 * auto-factura) y el "supplier" es el proveedor.
 *
 * 'required' = clave presente y no null.
 * 'present'  = clave presente (null permitido — la plantilla la lee directo
 *              dentro de un @if).
 */
return [
    'required' => [
        'document',

        // Cabecera
        'document.id',
        'document.date_of_issue',
        'document.time_of_issue',
        'document.operation_type_id',
        'document.currency_type_id',

        // Firma (metadata UBL)
        'document.signature_uri',
        'document.signature_note',

        // Empresa emisora (comprador con RUC) → AccountingCustomerParty
        'document.company_identity_document_type_id',
        'document.company_number',
        'document.company_name',

        // Proveedor (vendedor sin RUC) → AccountingSupplierParty
        'document.supplier_identity_document_type_id',
        'document.supplier_number',
        'document.supplier_name',

        // Colecciones (arrays, pueden ir vacías pero deben existir)
        'document.legends',
        'document.items',

        // Totales
        'document.total_taxes',
        'document.total_taxed',
        'document.total_igv',
        'document.total_exonerated',
        'document.total_unaffected',
        'document.total_value',
        'document.total_payable',

        // Líneas
        'document.items.*.quantity',
        'document.items.*.total_value',
        'document.items.*.unit_value',
        'document.items.*.total_taxes',
        'document.items.*.total_base_igv',
        'document.items.*.total_igv',
        'document.items.*.percentage_igv',
        'document.items.*.affectation_igv_type_id',
        'document.items.*.name_parts',
    ],

    'present' => [
        // Cabecera condicional (leída dentro de @if, null permitido)
        'document.company_trade_name',
    ],
];
