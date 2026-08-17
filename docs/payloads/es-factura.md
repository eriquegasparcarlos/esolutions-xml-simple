# Payload en español (estilo Greenter) — factura/boleta/NC/ND

El paquete acepta un payload con **campos en español (camelCase, estilo Greenter)**
para usarse como API. Se traduce internamente con `EsPayloadMapper` y se genera
igual que el contrato interno (plantillas/validador/reglas no cambian).

```php
$generator = app(\ESolutions\Xml\Contracts\XmlDocumentGeneratorContract::class);
$result = $generator->generateFromEs('01', $payloadEs);   // 01 factura, 03 boleta, 07 NC, 08 ND
```

## Estructura (factura)

```jsonc
{
  "tipoDoc": "01",              // catálogo 01
  "serie": "F001",
  "correlativo": "123",
  "fechaEmision": "2026-07-15",
  "horaEmision": "10:30:00",
  "fechaVencimiento": null,
  "tipoMoneda": "PEN",
  "tipoOperacion": "0101",      // catálogo 51
  "formaPago": "Contado",       // "Contado" | "Credito"
  "ordenCompra": null,

  "company": {
    "ruc": "20123456789",
    "razonSocial": "MI EMPRESA SAC",
    "nombreComercial": null,
    "address": {
      "ubigeo": "150101", "codLocal": "0000",
      "departamento": "LIMA", "provincia": "Lima", "distrito": "Lima",
      "direccion": "AV. LIMA 123", "codigoPais": "PE"
    }
  },

  "client": {
    "tipoDoc": "6",             // catálogo 06: 6 RUC, 1 DNI, 0 sin doc…
    "numDoc": "20100070970",
    "rznSocial": "CLIENTE SAC",
    "address": { "ubigeo": null, "direccion": null }   // opcional
  },

  "details": [
    {
      "codProducto": "P001",            // opcional
      "descripcion": "Servicio de consultoría",
      "cantidad": 2,
      "unidad": "NIU",                  // catálogo 03
      "mtoValorUnitario": 100,          // valor sin IGV
      "tipAfeIgv": "10",                // catálogo 07: 10 gravado, 20 exonerado, 30 inafecto, 21 gratuito, 40 exportación
      "porcentajeIgv": 18,
      "icbper": 0,                      // opcional (bolsa plástica)
      "isc": 0                          // opcional
    }
  ],

  "legends": [ { "code": "1000", "value": "SON DOSCIENTOS…" } ],

  "descuentos": [ { "codTipo": "02", "factor": 0.1, "monto": 20, "montoBase": 200 } ],   // opcional
  "cuotas":     [ { "monto": 118, "fechaPago": "2026-08-15" } ],                          // si formaPago=Credito
  "detraccion": { "codBienDetraccion": "037", "porcentaje": 12, "monto": 141.6, "cuentaBanco": "00-099-025344" }, // opcional
  "retencion":  null,

  // Totales: opcionales — si no se envían, se derivan de las líneas.
  "totales": { "gravado": 200, "exonerado": 0, "igv": 36, "total": 236 }
}
```

## Notas de crédito / débito (07 / 08)

Se agregan:

```jsonc
{
  "tipoDoc": "07",
  "documentoAfectado": { "id": "F001-101", "tipoDoc": "01", "fechaEmision": "2026-07-14" },
  "codMotivo": "01",              // catálogo 09 (NC) / 10 (ND)
  "desMotivo": "Anulación de la operación"
}
```

## Notas de mapeo

- El mapper es tolerante: acepta la raíz plana o envuelta en `comprobante`/`documento`,
  y algunos alias (`emisor`/`company`, `cliente`/`client`, `items`/`details`).
- Los totales por afectación (`gravado`, `exonerado`, `inafecto`, `exportacion`) se
  calculan de las líneas si no vienen en `totales`.
- Es un adaptador: internamente todo pasa por el mismo generador, firma y
  validación que el contrato interno documentado en `invoice.md`.
```
