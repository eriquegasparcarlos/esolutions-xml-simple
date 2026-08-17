# Cambios SUNAT con vigencia 2026-08-01 → **postergada a 2027-01-01**

> ⚠️ **POSTERGACIÓN (Excel del 24.07.2026, Control de Cambios línea 562):** SUNAT
> postergó del **01/08/2026 al 01/01/2027** la vigencia de las líneas 516-545,
> que incluyen **TODOS** los cambios que este paquete había fechado al 2026-08-01:
> `ERR-3496` código de producto obligatorio (líneas 527/530), `ERR-3507` ND tipo
> 13 solo inafectas (538) y todo el **resumen diario 2.0** (gratuitas desagregadas,
> tasa IGV, IGV gratuitas, adquirente — 541-545). Por eso `config('esolutions_xml.rule_dates.*')`
> quedó en **2027-01-01**. Un documento emitido entre ago-2026 y dic-2026 sigue
> usando las reglas VIEJAS; si SUNAT vuelve a mover la fecha, se ajusta en el config.

Fuente: "Reglas de validación" de SUNAT (publicado **2026-04-24**, hoja *Control de
Cambios*). Los cambios listados abajo estaban previstos para el **2026-08-01** y
ahora aplican desde el **2027-01-01** (ver postergación arriba).

## ⚠️ Principio: SUNAT valida por FECHA DE EMISIÓN del documento

Un comprobante emitido **antes** del 2026-08-01 se valida con las reglas **viejas**
(aunque se envíe después); uno emitido **el 1-ago o después**, con las **nuevas**.
Por eso los cambios estructurales ("Agrega campos/tags") deben aplicarse
**condicionados por `fechaEmision >= 2026-08-01`**, no en bloque — o SUNAT rechaza
el campo nuevo en documentos con fecha anterior. Los cambios OBS→ERROR o de
validación más estricta son seguros de cumplir antes.

## Cambios que AFECTAN al paquete (factura/boleta/NC/ND/resumen)

### 🔴 Crítico — impacta a todos los comprobantes

- **#12 Código de producto SUNAT pasa de OBS a ERROR** (FAC/BOL/NC/ND, `ERR-3496`,
  reemplaza `OBS-4332`/`OBS-4337`). Hoy nuestros fixtures llevan `item_code = null`;
  desde el 1-ago sería **rechazo**. Acción: exigir `item_code` (código producto
  SUNAT) en las líneas para documentos emitidos ≥ 2026-08-01.

### Notas de crédito / débito (NC/ND) — tipo "13" y correlativos

- **#4 / #24 Nota de débito tipo "13" (Penalidades)** — nuevo tipo de nota (catálogo 10).
- **#23 (`ERR-3507`)** La ND tipo 13 solo por operaciones **inafectas**.
- **#21 (`ERR-2524`)** valida ahora el tipo "13" en vez del "03".
- **#22 (`ERR-3194`)** se generaliza a todo tipo de ND.
- **#16 (`ERR-3261`)** solo se permite modificar un documento.
- **#17/#18/#19 (`OBS-4028`→`ERR-3286`/`ERR-3503`)** montos de NC no deben superar
  el documento referenciado; OBS→ERROR para modificación de boletas.
- **#20/#25** incorpora documentos 28 y 56 en las validaciones de correlativo.

### Resumen diario (RC) — desagregación de gratuitas + nuevos campos

- **#26 Operaciones gratuitas desagregadas** en Gravadas/Exoneradas/Inafectas/
  Exportación (reemplaza "código tipo valor de venta" 05 por 06/07/08/09).
- **#27 (`ERR-2992/3504/3102`)** nuevo campo **"Tasa del IGV"** para gravadas.
- **#28** nuevos campos + validaciones para **IGV de gratuitas**.
- **#29 / #30 (`ERR-2022`)** nuevo campo **apellidos/nombres o razón social del
  adquirente** (OBS hasta 31/07/2026, luego según regla).

### Catálogos (afectan a varios tipos)

- **#1 Catálogo 01** (tipo de documento): agrega código "25", modifica "56".
- **#2 Catálogo 11**: agrega 06/07/08/09; **desactiva "05-Gratuitas"** (liga con #26).
- **#3 Catálogo 09** (tipo NC): descripción código "03".
- **#4 Catálogo 10** (tipo ND): crea código "13".
- **#5 Catálogo 51** (tipo operación): 0101 nueva denominación "Venta interna no
  sujeta a Detracción, Retención o Percepción".
- **#6 Catálogo 25**: link + subcatálogos 25.1/25.2/25.3.
- **#14 (`ERR-0151/0159`)** nombre de archivo: agrega tipos 25, 28, 56.

## Cambios que NO aplican (fuera del alcance actual)

- **#7/#8** Comunicación de baja de DAE de contingencia (docs 25/28/56).
- **#9/#10/#11** DAE-OP / DAE-AD (Documento Autorizado Electrónico) — no soportado.
- **#13** Contrato de Colaboración Empresarial (campos nuevos FAC/BOL/NC/ND) — nicho.
- **#15** Liquidación de compra (LC) — no soportado.
- **#31** Resumen de reversiones: plazo 7→5 días para LC.
- **#32** CDR-OSE (recepción por OSE).
- **#33** Nuevas hojas DAE-SEAE / DOAE-Atribución (RS 000048-2026).

## Plan de implementación (date-gated)

1. **Primitivas de fecha en el motor** (tarea #94): permitir reglas condicionadas a
   `fechaEmision >= vigencia`.
2. **#12 código producto SUNAT**: hacerlo requerido en líneas ≥ 2026-08-01.
3. **Resumen diario**: template + schema con gratuitas desagregadas, tasa IGV, IGV
   gratuitas y adquirente — emitidos ≥ 2026-08-01.
4. **NC/ND tipo 13** + validaciones de correlativo.
5. **Catálogos**: actualizar cat_01/09/10/11/51/25 desde la hoja *Catálogos* del Excel.
6. **ErrorCatalog**: sincronizar mensajes desde la hoja *CódigosRetorno* (2079 códigos).

> Mantener este archivo al día cuando SUNAT publique un nuevo Excel de reglas
> (re-extraer la hoja *Control de Cambios* y comparar por fecha de vigencia).
