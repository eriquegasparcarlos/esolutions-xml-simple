# Payload: credit_note (Nota de crédito 07)

Contrato de entrada para `generate('07', $payload)` (o `'credit_note'`).
Plantilla: `src/Templates/credit-note.blade.php` · Esquema: `src/Payload/Schemas/credit_note.php`.

**Base:** el mismo payload de [invoice](invoice.md), con estas diferencias:

## Claves adicionales (requeridas)

| Clave | Tipo | Catálogo SUNAT | Nodo UBL |
|---|---|---|---|
| `affected_document.id` | string `SERIE-NUMERO` del comprobante afectado | — | `cac:DiscrepancyResponse/cbc:ReferenceID` y `cac:BillingReference` |
| `affected_document.document_type_id` | `'01'` \| `'03'` | 01 | `cbc:DocumentTypeCode` |
| `affected_document.date_of_issue` | string `Y-m-d` (informativo) | — | — |
| `note_type_id` | string | **09** (tipo de NC: 01 anulación, 07 devolución, 13 ajuste de plazos...) | `cac:DiscrepancyResponse/cbc:ResponseCode` |
| `note_description` | string no vacío, 2 a 500 caracteres | Sustento de la nota: SUNAT responde 2136 si falta y 2135 si no cumple la estructura. Si el emisor no captura un motivo libre, usar el nombre del tipo del catálogo 09 | `cbc:Description` |

## Claves que NO aplican (la plantilla no las emite)

`date_of_due`, `prepayments`, `detraction`, `perception`, `retention`,
`items.*.attributes`, `items.*.accommodation_attributes`.

## Reglas

- La serie debe empezar con `F` si el afectado es factura (01) o `B` si es boleta (03) — `SeriesFormatRule`.
- **NC tipo 13** (ajuste de plazos): todos los importes de cabecera y línea deben ir en **cero** (el consumidor lo resuelve antes de llamar a `generate()`; ver `SaleXmlPayloadBuilder::zeroAmounts()` en intipos13).
- Valida contra `xsd/2.1/maindoc/UBL-CreditNote-2.1.xsd`. Ojo: en `CreditNoteLine` el `cac:TaxTotal` va **antes** de `cac:AllowanceCharge` (orden inverso a `InvoiceLine`).
