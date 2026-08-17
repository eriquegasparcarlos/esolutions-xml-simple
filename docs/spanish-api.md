# API en español (payload camelCase) — spec y roadmap

El paquete tiene **dos entradas** equivalentes:

| Entrada | Formato de payload | Tipos | Estado |
|---|---|---|---|
| `generate($type, $payload)` | contrato interno (inglés: `date_of_issue`, `total_value`…) | **todos** | estable |
| `generateFromEs($type, $payloadEs)` | **API español** (camelCase estilo Greenter: `tipoDoc`, `serie`, `client`…) | ver checklist ↓ | **en construcción** |

`generateFromEs()` traduce el payload español al contrato interno con
[`EsPayloadMapper`](../src/Payload/EsPayloadMapper.php) y luego llama a `generate()`.
Objetivo: que el API en español cubra **todos los tipos y todos los campos**.

## Convención de nombres (camelCase, estilo Greenter/tefacturo)

- Cabecera: `tipoDoc`, `serie`, `correlativo`, `fechaEmision`, `horaEmision`,
  `tipoMoneda`, `formaPago` (`Contado|Credito`), `tipoOperacion`, `fechaVencimiento`.
- Emisor: `company: { ruc, razonSocial, nombreComercial, address: { ubigeo,
  departamento, provincia, distrito, direccion, codLocal, codigoPais } }`.
- Cliente: `client: { tipoDoc, numDoc, rznSocial, address: { ubigeo, direccion } }`.
- Líneas: `details: [{ descripcion, cantidad, unidad, mtoValorUnitario, tipAfeIgv,
  porcentajeIgv, isc, porcentajeIsc, icbper, factorIcbper, codProducto,
  codProductoSunat, gratuito }]`.
- Totales (opcional; si faltan se derivan de las líneas): `totales: { gravado,
  exonerado, inafecto, exportacion, gratuito, igv, isc, icbper, descuentos,
  cargos, anticipos, total }`.
- Estructuras: `descuentos[]`, `cargos[]`, `cuotas[]`, `detraccion{}`,
  `retencion{}`, `percepcion{}`, `anticipos[]`, `leyendas[]`, `documentoAfectado{}`
  (NC/ND), `codMotivo`/`desMotivo` (NC/ND).

Aceptar **alias** donde ayude (`tipoDoc|tipoDocumento`, `client|cliente`,
`details|items`, `numDoc|numero`, etc.) para tolerar variantes del ecosistema.

## Cómo se verifica que "cubre todo" (oráculo = fixtures)

Los fixtures internos (`tests/fixtures/payloads/**`) ya pasan XSD + reglas. Por cada
uno se crea su **gemelo en español** en `tests/fixtures/payloads-es/**` y el test
`EsPayloadFixturesTest`:

1. carga el gemelo español,
2. lo pasa por `generateFromEs($type, $payloadEs)`,
3. exige que el XML resultante **pase XSD + reglas** (igual que el interno).

Si el gemelo español de un caso genera el mismo resultado válido, queda **probado**
que el mapper cubre todos los campos de ese caso. Agregar cobertura = agregar el
gemelo español + completar el mapper hasta que el test pase.

## Checklist de cobertura del mapper

Un tipo se marca cuando su gemelo español pasa XSD + reglas (`tests/fixtures/payloads-es/`).

- [x] **01 Factura** — incl. anticipos, cargos, percepción, detracción
- [x] **03 Boleta**
- [x] **07 Nota de crédito** — doc afectado + tipo de nota (cat. 09)
- [x] **08 Nota de débito** — doc afectado + tipo de nota (cat. 10)
- [x] **09 Guía remitente** — `envio{ motivoTraslado, pesoTotal, modalidadTraslado,
      fechaTraslado, puntoPartida, puntoLlegada, placa, conductor{}, transportista{},
      vehiculoM1L, trasladoTotalDam }`, `destinatario{}`
- [x] **31 Guía transportista** — `transportista{}` (emisor), `remitente{}`,
      `envio{ vehiculoPrincipal{placa,tuc}, vehiculosSecundarios[], conductorPrincipal{},
      conductoresSecundarios[], bultos }`
- [x] **RC Resumen diario** — `fechaReferencia`, `documentos[{ tipoDoc, id, cliente{},
      estado, moneda, totales{} }]`
- [x] **RA Comunicación de baja** — `documentos[{ tipoDoc, serie, numero, motivo }]`
- [x] **RR Reversión** (baja de retenciones/percepciones) — mismo payload que RA;
      `tipoDoc` de línea defaultea `20` (correlativo `RR-YYYYMMDD-#`)
- [x] **20 Retención** — `proveedor{}`, `regimen{tipo,tasa}`, `totalRetenido`,
      `totalPagado`, `documentos[{ importeTotal, importeRetenido, … }]`
- [x] **40 Percepción** — `cliente{}`, `regimen{tipo,tasa}`, `totalPercibido`,
      `totalCobrado`, `documentos[{ importeTotal, importePercibido, … }]`

**Todos los tipos cubiertos** (mapper + gemelo español verificado). Para ampliar
un tipo con más variantes de campo, agrega otro gemelo español y corre `composer test`.

## Guía para agregar/completar un tipo

1. Mira el schema interno del tipo en `src/Payload/Schemas/<tipo>.php` (required + present).
2. En `EsPayloadMapper`, agrega la rama que produce ese `document` desde camelCase.
3. Crea el gemelo español del/los fixture(s) de ese tipo en `tests/fixtures/payloads-es/`.
4. Corre `composer test` hasta 0 hallazgos.
5. Marca el tipo en el checklist de arriba.

> Nota: el mapper NO debe acoplarse a ningún proyecto (nada de `App\...`). Recibe
> arrays y devuelve arrays. Los defaults deben ser seguros (SUNAT válidos).
