# Payload: summary (Resumen diario de boletas RC)

Contrato de entrada para `generate('RC', $payload)` (o `'summary'`).
Plantilla: `src/Templates/summary.blade.php` · Esquema: `src/Payload/Schemas/summary.php`.
UBL **2.0** / CustomizationID **1.1** — payload propio, no deriva de invoice.
Envío **asíncrono**: `DocumentSender::sendSummary()` → ticket → `getStatus()` (98 = en proceso).

## Cabecera (todas Req)

| Clave | Tipo | Nodo UBL |
|---|---|---|
| `identifier` | `RC-YYYYMMDD-###` | `cbc:ID` |
| `date_of_reference` | `Y-m-d` (emisión de los comprobantes) | `cbc:ReferenceDate` |
| `date_of_issue` | `Y-m-d` (generación del resumen) | `cbc:IssueDate` |
| `signature_uri` / `signature_note` | string | `cac:Signature` |
| `company_number` / `company_name` | RUC / razón social | `cac:AccountingSupplierParty` |

## Líneas — `documents[]`

| Clave | Tipo | Catálogo | Nodo UBL |
|---|---|---|---|
| `document_type_id` | `'03'` \| `'07'` \| `'08'` | 01 | `cbc:DocumentTypeCode` |
| `id` | `SERIE-NUMERO` | — | `cbc:ID` |
| `customer_number` / `customer_identity_document_type_id` | doc. del cliente | 06 | `cac:AccountingCustomerParty` |
| `affected_document` | `{id, document_type_id}` — solo líneas 07/08 (Pres) | 01 | `cac:BillingReference` |
| `status_id` | `'1'` adicionar \| `'2'` modificar \| `'3'` anular | **19** | `cac:Status/cbc:ConditionCode` |
| `currency_type_id` | ISO 4217 | 02 | atributos `currencyID` |
| `total` | float | — | `sac:TotalAmount` |
| `total_taxed/exonerated/unaffected/exportation/free` | float (BillingPayment InstructionID 01–05) | — | `sac:BillingPayment` |
| `total_charge` | float | — | `cac:AllowanceCharge` |
| `total_igv` / `total_isc` / `total_other_taxes` / `total_plastic_bag_taxes` | float | — | `cac:TaxTotal` (1000/2000/9999/7152) |

Filename SUNAT: `FilenameBuilder::forSummary($ruc, 'RC', $fecha, $correlativo)`.
Referencia de mapeo: `Modules/Summary/app/Services/SummaryXmlPayloadBuilder.php` en intipos13.
