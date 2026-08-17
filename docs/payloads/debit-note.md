# Payload: debit_note (Nota de débito 08)

Contrato de entrada para `generate('08', $payload)` (o `'debit_note'`).
Plantilla: `src/Templates/debit-note.blade.php` · Esquema: `src/Payload/Schemas/debit_note.php`.

**Base:** idéntico a [credit-note](credit-note.md) salvo:

| Diferencia | NC (07) | ND (08) |
|---|---|---|
| `note_type_id` | catálogo **09** | catálogo **10** (01 intereses por mora, 02 aumento de valor, 03 penalidades...) |
| Total monetario | `cac:LegalMonetaryTotal` | `cac:RequestedMonetaryTotal` |
| Cantidad de línea | `cbc:CreditedQuantity` | `cbc:DebitedQuantity` |
| Orden header | `PaymentTerms` antes de `AllowanceCharge` | `AllowanceCharge` antes de `PaymentTerms` (orden XSD) |

Valida contra `xsd/2.1/maindoc/UBL-DebitNote-2.1.xsd`.
