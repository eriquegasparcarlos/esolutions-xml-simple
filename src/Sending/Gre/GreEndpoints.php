<?php

namespace ESolutions\XmlSimple\Sending\Gre;

/**
 * Endpoints de la API REST de Guías de Remisión Electrónicas (GRE 2022).
 *
 * - Producción: api-seguridad.sunat.gob.pe (token) + api-cpe.sunat.gob.pe (envío).
 * - Beta/homologación: el homologador público de Nubefact (gre-test), con
 *   client_id/secret públicos de prueba. El usuario/clave siguen siendo los del
 *   contribuyente en formato demo ("{RUC}MODDATOS" / "MODDATOS").
 *
 * `{client_id}` se sustituye en la URL del token, `{filename}` en el envío y
 * `{ticket}` en la consulta.
 */
final class GreEndpoints
{
    public const TOKEN_PRODUCTION  = 'https://api-seguridad.sunat.gob.pe/v1/clientessol/{client_id}/oauth2/token';
    public const SEND_PRODUCTION   = 'https://api-cpe.sunat.gob.pe/v1/contribuyente/gem/comprobantes/{filename}';
    public const TICKET_PRODUCTION = 'https://api-cpe.sunat.gob.pe/v1/contribuyente/gem/comprobantes/envios/{ticket}';

    public const TOKEN_BETA  = 'https://gre-test.nubefact.com/v1/clientessol/{client_id}/oauth2/token';
    public const SEND_BETA   = 'https://gre-test.nubefact.com/v1/contribuyente/gem/comprobantes/{filename}';
    public const TICKET_BETA = 'https://gre-test.nubefact.com/v1/contribuyente/gem/comprobantes/envios/{ticket}';

    /** Credenciales públicas de homologación de Nubefact (solo entorno demo). */
    public const CLIENT_ID_BETA     = 'test-85e5b0ae-255c-4891-a595-0b98c65c9854';
    public const CLIENT_SECRET_BETA = 'test-Hty/M6QshYvPgItX2P0+Kw==';

    /** Scope OAuth2 (igual en beta y producción: apunta al recurso api-cpe). */
    public const SCOPE = 'https://api-cpe.sunat.gob.pe';

    public static function token(bool $demo): string
    {
        return $demo ? self::TOKEN_BETA : self::TOKEN_PRODUCTION;
    }

    public static function send(bool $demo): string
    {
        return $demo ? self::SEND_BETA : self::SEND_PRODUCTION;
    }

    public static function ticket(bool $demo): string
    {
        return $demo ? self::TICKET_BETA : self::TICKET_PRODUCTION;
    }
}
