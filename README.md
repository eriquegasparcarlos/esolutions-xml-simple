# esolutions/xml-simple

Emisión de CPE SUNAT para Laravel: **construcción UBL, firma digital, envío y validación básica + CDR**.

Versión estándar del motor de emisión — cubre el ciclo completo del comprobante sin capas avanzadas (la validación de reglas SUNAT/SFS pre-firma y el análisis de rechazos son parte del servicio **xmlperu**).

## Qué incluye

- **Construcción UBL** (templates Blade + validación de payload) para los 11 tipos: factura, boleta, NC/ND, guías remitente/transportista, resumen diario, comunicación de baja, retención, percepción y liquidación de compra.
- **Firma digital** XmlDSig (PEM/PFX/P12, cert demo incluido para beta).
- **Envío**: SOAP SUNAT/OSE, GRE REST (guías), soporte Nubefact, empaquetado ZIP.
- **Validación básica**: XSD + reglas de negocio propias; **parse de CDR** (SUNAT y Nubefact) con catálogo de códigos de error.

## Requisitos

PHP ^8.2 · Laravel 11/12/13 · ext-dom, ext-libxml, ext-openssl, ext-soap, ext-zip

## Instalación

```bash
composer require esolutions/xml-simple
php artisan vendor:publish --tag=esolutions-xml-simple-config
```

## Uso

```php
use ESolutions\XmlSimple\Contracts\XmlDocumentGeneratorContract;
use ESolutions\XmlSimple\Sending\{DocumentSender, SenderConfig, FilenameBuilder};

$gen = app(XmlDocumentGeneratorContract::class)->generate('invoice', $payload, $certFile, $certPass);
// $gen->isOk(), $gen->xml (firmado), $gen->unsignedXml, $gen->getHash(), $gen->validation->errors

$config = SenderConfig::fromArray([...]);
$result = (new DocumentSender($config))->send(
    FilenameBuilder::forDocument($ruc, $docTypeId, $series, $number),
    $gen->xml
);
// $result->isAccepted(), $result->getCdrXml(), $result->toArray()
```

Config en `config/esolutions_xml_simple.php` (endpoints, firma, vistas por tipo, proveedor sunat/nubefact).

## Formatos de payload

Ver `docs/payloads/` — un `.md` por tipo de documento con el contrato de campos.
