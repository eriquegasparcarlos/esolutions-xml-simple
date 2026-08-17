# Tests y fixtures de payloads

La suite valida que el paquete **genera** correctamente cada tipo de documento y
que el XML resultante pasa el **XSD** y las **reglas SUNAT** (nivel determinista).

## Correr

```bash
composer install
vendor/bin/phpunit
```

Corre sobre [Orchestra Testbench](https://packages.tools/testbench) (arranca un
Laravel mínimo con el `XmlServiceProvider`). El firmado usa el certificado demo
del paquete, así que no requiere configuración.

## Fixtures = ejemplos de payload

Cada caso vive como un JSON en `tests/fixtures/payloads/<tipo>/<caso>.json` y
sirve **doble propósito**: es el ejemplo de payload de ese caso y es el input del
test. El envelope:

```json
{
  "type": "01",
  "description": "Factura gravada simple",
  "expect": "ok",
  "payload": { "document": { "...": "..." } }
}
```

`PayloadFixturesTest` recorre `tests/fixtures/payloads/**/*.json` con un
data-provider: **agregar un caso nuevo = agregar un `.json`**, el test lo recoge
automáticamente. Por cada fixture verifica:

1. `generate($type, $payload)` produce XML y pasa el XSD (`isOk`).
2. El motor de reglas SUNAT no arroja hallazgos.

## `expect`: nivel de verificación

- `expect: "ok"` (por defecto) → el fixture debe pasar **XSD + reglas SUNAT**.
- `expect: "xsd"` → solo se exige **XSD** (reservado para tipos que aún no tengan
  reglas fiables). Hoy **todos** los fixtures son `ok`.

## Cobertura actual (35 fixtures)

| Tipo | Casos | expect |
|---|---|---|
| Factura (01) | gravada, descuento global, detracción, crédito, exonerada, inafecta, gratuita, exportación, ICBPER, ISC, multilínea, **anticipos** | ok |
| Boleta (03) | gravada con DNI, exonerada | ok |
| Nota de crédito (07) | anulación (01), descuento (02), corrección (03), devolución (06) | ok |
| Nota de débito (08) | intereses (01), aumento de valor (02), penalidad (03) | ok |
| Guía remitente (09) | venta privado/público, traslado entre establecimientos, M1/L multilínea, compra (02), importación (08), exportación (09, traslado total DAM), itinerante (18) | ok |
| Guía transportista (31) | un vehículo, multi-vehículo/multi-conductor | ok |
| Resumen diario (RC) | boletas | ok |
| Comunicación de baja (RA) | baja de factura | ok |
| Retención (20) | tasa 3% | ok |
| Percepción (40) | tasa 2% | ok |
