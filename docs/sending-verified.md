# Envíos verificados en homologación (beta/OSE)

Pruebas end-to-end **en vivo** contra el homologador (SUNAT beta / Nubefact
gre-test), con el certificado demo estándar (RUC **10417844398**, usuario
`10417844398MODDATOS` / `moddatos`). Cada flujo: generar → firmar → enviar →
(ticket →) leer CDR/respuesta.

| Tipo | Canal | Resultado | Nota |
|---|---|---|---|
| Factura / Boleta (01/03) | SOAP sendBill | ✅ aceptado | F001-2 aceptada (SUNAT beta + Nubefact OSE) |
| Liquidación de compra (04) | SOAP sendBill | ✅ **aceptado** (CDR) | L001-2/3 aceptada. Serie **`L`** + `operation_type_id` **`0501`**; `AddressTypeCode` del proveedor del **catálogo 60** (`01`, no `0000`); UNSPSC real de cat. 25. Queda una nota **4312 INFO** (no rechaza) |
| Guía remitente (09) | GRE REST | ✅ **aceptado** (CDR) | ticket → status aceptado |
| Guía transportista (31) | GRE REST | ✅ send+ticket+parse | rechazo 2560: el RUC de prueba no está autorizado como transportista (dato del homologador, no del paquete) |
| Resumen diario (RC) | SOAP ticket | ✅ **aceptado** (CDR) | el `cbc:ID` del RC debe usar la fecha de generación y coincidir con el nombre de archivo |
| Comunicación de baja (RA) | SOAP ticket | ✅ **aceptado** (CDR) | — |
| Reversión (RR, baja de retención) | SOAP ticket (endpoint retenciones) | ✅ **aceptado** (CDR, `RR-20260722-1`) | mismo XML que RA; `SenderConfig document_type_id: RR` |
| Retención (20) | SOAP sendBill | ✅ **aceptado** (CDR) | requiere `documents[].payments` (número de pago) — sin él, error 2733 |
| Percepción (40) | SOAP sendBill | ✅ **aceptado** (CDR) | requiere `documents[].payments` (número de cobro) — sin él, error 2697 |

## Variantes de guía remitente (09) enviadas en vivo

| Variante | Motivo | Resultado |
|---|---|---|
| venta privado | 01 | ✅ aceptado |
| venta público (transportista) | 01 | ✅ aceptado |
| compra | 02 | ✅ aceptado |
| traslado entre establecimientos | 04 | ✅ aceptado (destinatario = emisor) |
| itinerante | 18 | ✅ aceptado |
| vehículo M1/L | 01 | ✅ aceptado (sin conductor/vehículo) |
| **2 conductores + 2 vehículos** | 01 | ✅ aceptado |
| exportación | 09 | ✅ aceptado (DAM régimen 40 + bultos + anexo llegada) |
| importación | 08 | ⚠️ estructura completa; la aceptación requiere un establecimiento aduanero real (de tercero) registrado en SUNAT |

Hallazgos que solo el envío en vivo destapó (corregidos en template/fixtures):
- **2554**: para traslado entre establecimientos (04) el destinatario debe ser el
  propio emisor.
- **3455**: con vehículo M1/L NO se declara conductor/vehículo — el template ahora
  los omite cuando `is_transport_category_m1l`.
- La guía remitente ahora soporta **múltiples conductores y vehículos**
  (`secondary_drivers` / `secondary_vehicles`).
- **Importación/exportación (08/09)** exigen: DAM/DS (catálogo 61 código 50/52) con
  `IssuerParty` (RUC), número de bultos (`packages_number` →
  `TotalTransportHandlingUnitQuantity`), formato de número DAM por régimen (import
  `NNN-YYYY-10-N…`, export `NNN-YYYY-40-N…`) y establecimiento anexo del punto de
  partida (import) / llegada (export). El template los emite todos.
  - **Exportación**: aceptada en vivo.
  - **Importación**: el punto de partida debe apuntar a un establecimiento de un RUC
    **distinto** al emisor (terminal aduanero, código 3411) y **registrado** en SUNAT
    (código 3446) — dato de comercio exterior real, no fabricable en homologación con
    un RUC demo (misma clase de límite que el 2560 del transportista).

## Hallazgos incorporados a los fixtures

Estos casos pasaban XSD + reglas cliente pero SUNAT los rechazaba; se corrigieron
en los fixtures (internos y español) para que sean válidos de producción:

1. **Retención/percepción** necesitan al menos un **pago** por documento
   (`documents[].payments` / español `documentos[].pagos`), o SUNAT devuelve
   2733/2697. El template ya lo soporta; faltaba el dato.
2. **Resumen (RC)**: el `identifier` (`cbc:ID`) debe ser `RC-{fechaGeneracion}-N`
   y coincidir con la fecha del nombre de archivo, o SUNAT devuelve 2220.

## Cómo reproducir

Con el paquete y un tenant demo, para cada fixture: `generate($type, $payload)`
→ `DocumentSender`/`GreRestClient` con `SenderConfig` demo (`{RUC}MODDATOS` /
`moddatos`) → `sendBill`/`sendSummary`/`sendDespatch` → `getStatus(ticket)`.
El nombre de archivo es `RUC-TIPO-SERIE-NUMERO` (RC/RA: `RUC-RC-YYYYMMDD-N`).
