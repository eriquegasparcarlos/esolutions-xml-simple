# Guía de Remisión Electrónica (GRE 2022) — envío REST

SUNAT deprecó el canal SOAP para guías. El flujo es **asíncrono por ticket** sobre
la API REST:

1. **Token** — OAuth2 `grant_type=password` contra `api-seguridad.sunat.gob.pe`
   (o el homologador de Nubefact en beta). El `client_id` va en el path y en el body.
2. **Envío** — `POST .../gem/comprobantes/{filename}` con el XML firmado comprimido
   en ZIP y codificado: `{archivo: {nomArchivo, arcGreZip (b64), hashZip (sha256 del ZIP)}}`.
   Devuelve `numTicket`.
3. **Estado** — `GET .../gem/comprobantes/envios/{ticket}`: `codRespuesta` 0=aceptado
   (CDR en `arcCdr`), 98=en proceso, 99=rechazado.

## Uso

```php
use ESolutions\Xml\Sending\SenderConfig;
use ESolutions\Xml\Sending\Gre\GreRestClient;
use ESolutions\Xml\Contracts\XmlDocumentGeneratorContract;

// 1) Generar + firmar la guía (DespatchAdvice UBL 2.1)
$res = app(XmlDocumentGeneratorContract::class)
    ->generate('09', $payload, $certPath, $certPassword);   // '09' remitente, '31' transportista
$signedXml = $res->xml;
$filename = '20123456789-09-T001-1';   // RUC-tipo-serie-numero (sin extensión)

// 2) Enviar por REST
$config = SenderConfig::fromArray([
    'environment'       => 'production',      // 'demo' usa el homologador de Nubefact
    'username'          => '20123456789MODDATOS',
    'password'          => '...',
    'gre_client_id'     => '...',             // client_id OAuth2 emitido en SOL
    'gre_client_secret' => '...',             // en demo se omiten: usa los beta públicos
]);
$client = GreRestClient::fromSenderConfig($config);

$send = $client->sendDespatch($filename, $signedXml);   // SummaryResult
if ($send->hasTicket()) {
    $status = $client->getStatus($send->getTicket());   // StatusResult
    if ($status->isAccepted()) {
        $cdrXml = $status->getCdrXml();
    } elseif ($status->isInProcess()) {
        // codRespuesta 98 — reconsultar luego
    }
}
```

## Credenciales

- **Producción**: `gre_client_id` / `gre_client_secret` (emitidos en el portal SOL) +
  usuario/clave SOL secundario. Se pasan desde el consumidor vía `SenderConfig` — el
  paquete no los hardcodea.
- **Demo/homologación**: si no se pasan credenciales GRE, el cliente usa las públicas
  de homologación de Nubefact (`gre-test.nubefact.com`), con usuario `{RUC}MODDATOS` / `MODDATOS`.

## Nota sobre la firma

El XML debe firmarse **antes** de enviarlo y no modificarse después (cualquier cambio
al contenido firmado invalida la firma → rechazo 2335). El certificado es del
contribuyente (lo provee el consumidor); el paquete solo firma y envía.
