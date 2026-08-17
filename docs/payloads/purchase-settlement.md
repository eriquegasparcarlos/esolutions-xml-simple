# Payload: purchase-settlement (Liquidación de compra 04)

Contrato de entrada para `XmlDocumentGeneratorContract::generate('04', $payload)`
(alias: `purchase_settlement`, `liquidacion`). Plantilla:
`src/Templates/purchase-settlement.blade.php` · Esquema:
`src/Payload/Schemas/purchase_settlement.php` · XSD: `UBL-SelfBilledInvoice-2.1`.

La liquidación de compra la emite una **empresa con RUC** que compra a un
**proveedor sin RUC** (persona natural del catálogo 06: DNI, etc.). Es un UBL
**`SelfBilledInvoice`** (auto-facturación), con las partes **invertidas** respecto
a una factura:

- `cac:AccountingCustomerParty` = la **empresa** emisora (comprador).
- `cac:AccountingSupplierParty` = el **proveedor** (vendedor sin RUC).

El payload es un array con una sola clave raíz `document`.
**Req** = requerida y no null · **Pres** = la clave debe existir (null permitido).

## Cabecera

| Clave | Tipo | Nivel | Nota |
|---|---|---|---|
| `id` | string `SERIE-NUMERO` | Req | La **serie debe empezar con `L`** (p. ej. `L001`). Otras series → rechazo 0151 |
| `date_of_issue` / `time_of_issue` | `Y-m-d` / `H:i:s` | Req | — |
| `operation_type_id` | string | Req | Catálogo 51: **`0501`** ("Compra interna"). Otro valor → rechazo 0151 |
| `document_type_id` | `'04'` | Pres | default `'04'` |
| `currency_type_id` | string ISO (`PEN`) | Req | — |
| `payment_condition_id` | `'01'` contado / otro crédito | Pres | default `'01'` |
| `signature_uri` / `signature_note` | string | Req | metadata `cac:Signature` |
| `legends[]` | `{code, value}` | Req | leyenda 1000 (monto en letras) |

## Emisor (empresa, comprador) → AccountingCustomerParty

| Clave | Tipo | Nivel |
|---|---|---|
| `company_identity_document_type_id` | `'6'` (RUC) | Req |
| `company_number` | string RUC | Req |
| `company_name` | string | Req |
| `company_trade_name` | string \| null | Pres |
| `establishment` | `{location_id, code, urbanization, province, department, district, address, country_id}` | Opc |

## Proveedor (vendedor sin RUC) → AccountingSupplierParty

| Clave | Tipo | Nivel | Nota |
|---|---|---|---|
| `supplier_identity_document_type_id` | string cat. 06 (`'1'` DNI) | Req | |
| `supplier_number` | string | Req | |
| `supplier_name` | string | Req | |
| `supplier_address` | array \| ausente | Opc | Si viene, `address_type_code` es **obligatorio** y debe ser del **catálogo 60** (p. ej. `'01'`). `'0000'` → rechazo 2457 |
| `supplier_address.*` | `location_id, address_type_code, province, department, district, address, country_id` | — | |

## Lugar de la operación → cac:DeliveryTerms

| Clave | Tipo | Nivel |
|---|---|---|
| `operation` | `{location_type_code, location_id, province, department, district, address, country_id}` \| null | Opc |

## Totales (Req, float)

`total_taxes`, `total_taxed`, `total_igv`, `total_exonerated`,
`total_unaffected`, `total_value` (LineExtensionAmount), `total_payable`
(PayableAmount). El `TaxInclusiveAmount` se deriva como `total_value + total_taxes`.

## Líneas — `items[]`

Mismo shape que una factura gravada:

| Clave | Tipo | Nivel | Nota |
|---|---|---|---|
| `unit_type_id` | string UN/ECE (`KGM`, `NIU`) | Opc | |
| `quantity` | float | Req | |
| `total_value` | float | Req | `cbc:LineExtensionAmount` |
| `unit_value` | float (sin IGV) | Req | `cac:Price/cbc:PriceAmount` |
| `price_amount_01` | float | Opc | precio con IGV (`cac:PricingReference`) |
| `price_type_id` | `'01'` | Opc | default `'01'` |
| `total_taxes`, `total_base_igv`, `total_igv`, `percentage_igv` | float | Req | |
| `affectation_igv_type_id` | string cat. 07 (`'10'` gravado) | Req | |
| `name_parts` | string[] | Req | `cbc:Description` |
| `internal_id` | string \| null | Opc | `cac:SellersItemIdentification` |
| `item_code` | UNSPSC (cat. 25, nivel clase) \| null | Opc | obligatorio desde 2026-08-01 (ERR-3496). Debe ser real (p. ej. `50192601`) o SUNAT observa 4332/4337 |

## Ejemplo

Ver `tests/fixtures/payloads/purchase_settlement/LC_01_gravada.json` (interno) y
`tests/fixtures/payloads-es/purchase-settlement/ES_LC_01_gravada.json` (español),
más el XML aceptado `docs/examples/LC_01_gravada.xml`.
