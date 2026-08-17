# Payload: invoice (Factura 01 / Boleta 03)

Contrato de entrada para `XmlDocumentGeneratorContract::generate('invoice', $payload)`.
Plantilla: `src/Templates/invoice.blade.php` · Esquema de validación: `src/Payload/Schemas/invoice.php`.

El payload es un array con una sola clave raíz `document`. **Req** = requerida y no null ·
**Pres** = la clave debe existir (null permitido) · **Opc** = opcional (la plantilla usa `?? null`).

## Cabecera

| Clave | Tipo | Nivel | Catálogo SUNAT | Nodo UBL |
|---|---|---|---|---|
| `id` | string `SERIE-NUMERO` | Req | — | `cbc:ID` |
| `date_of_issue` | string `Y-m-d` | Req | — | `cbc:IssueDate` |
| `time_of_issue` | string `H:i:s` | Req | — | `cbc:IssueTime` |
| `date_of_due` | string `Y-m-d` \| null | Pres | — | `cbc:DueDate` |
| `operation_type_id` | string | Req | 51 | `cbc:InvoiceTypeCode@listID` |
| `operation_type_is_exportation` | bool | Req | — | condiciona TaxSubtotal |
| `document_type_id` | `'01'` \| `'03'` | Req | 01 | `cbc:InvoiceTypeCode` |
| `currency_type_id` | string ISO 4217 (`PEN`) | Req | 02 | `cbc:DocumentCurrencyCode` |
| `purchase_order` | string \| null | Pres | — | `cac:OrderReference` |
| `signature_uri` / `signature_note` | string | Req | — | `cac:Signature` |
| `payment_condition_id` | `'01'` contado \| otro crédito | Req | — | `cac:PaymentTerms` |
| `fee_total` | float \| null | Pres | — | monto total crédito |

## Emisor / Receptor

| Clave | Tipo | Nivel | Catálogo | Nodo UBL |
|---|---|---|---|---|
| `company_identity_document_type_id` | `'6'` | Req | 06 | `cac:AccountingSupplierParty` |
| `company_number` | string RUC | Req | — | `cbc:ID` |
| `company_name` | string | Req | — | `cbc:RegistrationName` |
| `company_trade_name` | string \| null | Pres | — | `cac:PartyName` |
| `establishment` | array \| ausente | Opc | — | `cac:RegistrationAddress` |
| `establishment.code` | string (`0000`) | — | — | `cbc:AddressTypeCode` |
| `establishment.location_id` | string ubigeo | — | — | `cbc:ID` |
| `establishment.urbanization/province/department/district/address/country_id` | string | — | — | dirección |
| `customer_identity_document_type_id` | string | Req | 06 | `cac:AccountingCustomerParty` |
| `customer_number` | string | Req | — | `cbc:ID` |
| `customer_name` | string | Req | — | `cbc:RegistrationName` |
| `customer_address` | array `{location_id, address, country_id}` \| ausente | Opc | — | `cac:RegistrationAddress` |

## Colecciones de cabecera (arrays, pueden ir vacías)

| Clave | Elemento | Nivel | Catálogo |
|---|---|---|---|
| `legends[]` | `{code, value}` | Req | 52 |
| `guides[]` | `{number, document_type_id}` | Req | 01 |
| `related[]` | `{number, document_type_id}` | Req | 12 |
| `prepayments[]` | `{number, document_type_id, total}` | Req | 12 |
| `charges[]` | `{charge_type_id, factor, amount, base}` | Req | 53 |
| `discounts[]` | `{discount_type_id, factor, amount, base}` | Req | 53 |
| `fee[]` | `{currency_type_id, amount, date_of_due}` (cuotas crédito) | Req | — |

## Estructuras opcionales

| Clave | Contenido | Nivel |
|---|---|---|
| `detraction` | `{payment_method_type_id (cat. 59), bank_account, detraction_type_id (cat. 54), percentage, amount_pen}` \| null | Pres |
| `perception` | `{code (cat. 53), percentage, amount, base}` \| null | Pres |
| `retention` | `{code (cat. 53), percentage, amount, base}` \| null | Pres |

## Totales (todos Req, float)

**`total_taxes`** debe ser la **suma de todos los subtotales de tributo** (IGV + ISC + OTROS + ICBPER) — es el `cbc:TaxAmount` global del `<cac:TaxTotal>`. Si no cuadra con los subtotales, SUNAT rechaza con código 3294. El `<cbc:TaxInclusiveAmount>` (precio de venta) se deriva en la plantilla como `total_value + total_taxes + total_prepayment`.

`total_taxes`, `total_isc`, `total_base_isc`, `total_taxed`, `total_igv`,
`total_unaffected_init`, `total_prepayment_unaffected`, `total_exonerated_init`,
`total_prepayment_exonerated`, `total_free`, `total_igv_free`, `total_exportation_init`,
`total_other_taxes`, `total_base_other_taxes`, `total_plastic_bag_taxes` (ICBPER),
`total_value` (LineExtensionAmount), `total_discount_no_base`, `total_charge`,
`total_prepayment`, `total_payable` (PayableAmount).

## Líneas — `items[]`

| Clave | Tipo | Nivel | Catálogo | Nodo UBL |
|---|---|---|---|---|
| `unit_type_id` | string UN/ECE (`NIU`, `ZZ`) | Opc | 03 | `cbc:InvoicedQuantity@unitCode` |
| `quantity` | float | Req | — | `cbc:InvoicedQuantity` |
| `total_value` | float | Req | — | `cbc:LineExtensionAmount` |
| `price_type_id` | `'01'` \| `'02'` | Req | 16 | `cbc:PriceTypeCode` |
| `price_amount_01` | float \| null (si price_type 01: precio con IGV) | Pres | — | `cac:PricingReference` |
| `price_amount_02` | float \| null (si price_type 02: valor gratuito) | Pres | — | `cac:PricingReference` |
| `unit_value` | float (sin IGV) | Req | — | `cac:Price/cbc:PriceAmount` |
| `charges[]` / `discounts[]` | como en cabecera | Req | 53 | `cac:AllowanceCharge` |
| `total_taxes` | float | Req | — | `cac:TaxTotal` |
| `total_isc`, `total_base_isc`, `percentage_isc`, `system_isc_type_id` | float/string | Req/Pres | 08 | TaxSubtotal ISC |
| `total_base_igv`, `total_igv`, `percentage_igv` | float | Req | — | TaxSubtotal IGV |
| `affectation_igv_type_id` | string | Req | 07 | `cbc:TaxExemptionReasonCode` |
| `total_other_taxes`, `total_base_other_taxes`, `percentage_other_taxes` | float | Req/Pres | — | TaxSubtotal OTROS |
| `total_plastic_bag_taxes`, `amount_plastic_bag_taxes` | float | Req/Pres | — | TaxSubtotal ICBPER (7152) |
| `name_parts` | string[] (máx. 230 chars c/u — usar `UblTextSanitizer::chunkByLen`) | Req | — | `cbc:Description` |
| `internal_id` | string \| null | Pres | — | `cac:SellersItemIdentification` |
| `item_code` | string UNSPSC \| null | Pres | — | `cac:CommodityClassification` |
| `accommodation_attributes[]` | atributos hospedaje (solo operation_type `0202`) | Opc | — | `cac:AdditionalItemProperty` |
| `attributes[]` | `{attribute_type_id, name, value, value_qualifier?, value_quantity?, period?}` | Req (puede ir vacío) | 55 | `cac:AdditionalItemProperty` |

## Ejemplo mínimo

Ver `Modules/Sale/app/Services/SaleXmlPayloadBuilder.php` en intipos13 como implementación
de referencia del mapeo desde modelos propios hacia este contrato.
