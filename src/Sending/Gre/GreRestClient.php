<?php

namespace ESolutions\XmlSimple\Sending\Gre;

use ESolutions\XmlSimple\Results\Sending\StatusResult;
use ESolutions\XmlSimple\Results\Sending\SummaryResult;
use ESolutions\XmlSimple\Sending\SenderConfig;
use ESolutions\XmlSimple\Sending\Zip\ZipExtractor;
use ESolutions\XmlSimple\Sending\Zip\ZipFly;
use Illuminate\Support\Facades\Http;

/**
 * Cliente REST para Guías de Remisión Electrónicas (GRE 2022). SUNAT deprecó el
 * canal SOAP para guías; el flujo es asíncrono por ticket:
 *
 *   1) getToken(): OAuth2 grant_type=password contra api-seguridad (o el
 *      homologador de Nubefact en beta). El client_id va en el PATH y en el body.
 *   2) sendDespatch($filename, $signedXml): comprime el XML firmado a ZIP y hace
 *      POST { archivo: { nomArchivo, arcGreZip (b64), hashZip (sha256 del ZIP) } }.
 *      Respuesta: numTicket → SummaryResult con getTicket().
 *   3) getStatus($ticket): GET del ticket. codRespuesta 0=aceptado (arcCdr b64
 *      con el CDR), 98=en proceso, 99=rechazado.
 *
 * Genérico: las credenciales llegan desde afuera (fromSenderConfig / constructor).
 * El XML DespatchAdvice se genera aparte con el generador del paquete
 * (generate('09', $payload), plantilla despatch.blade.php) y se firma antes de
 * enviarlo aquí.
 */
class GreRestClient
{
    private ?string $cachedToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $username,
        protected string $password,
        protected bool $demo = true,
        protected bool $verifySsl = false,
    ) {
    }

    /**
     * Arma el cliente desde un SenderConfig. En demo, si no se pasan credenciales
     * GRE, usa las públicas de homologación de Nubefact.
     */
    public static function fromSenderConfig(SenderConfig $config): self
    {
        $demo = $config->isDemo();

        $clientId = $config->greClientId ?: ($demo ? GreEndpoints::CLIENT_ID_BETA : '');
        $clientSecret = $config->greClientSecret ?: ($demo ? GreEndpoints::CLIENT_SECRET_BETA : '');

        return new self(
            $clientId,
            $clientSecret,
            $config->username,
            $config->password,
            $demo,
        );
    }

    /**
     * Obtiene (y cachea en memoria) el access_token OAuth2.
     */
    public function getToken(): string
    {
        if ($this->cachedToken !== null && $this->tokenExpiresAt > time()) {
            return $this->cachedToken;
        }

        $url = str_replace('{client_id}', $this->clientId, GreEndpoints::token($this->demo));

        $response = Http::withOptions(['verify' => $this->verifySsl])
            ->asForm()
            ->connectTimeout(5)
            ->timeout(15)
            ->post($url, [
                'grant_type'    => 'password',
                'scope'         => GreEndpoints::SCOPE,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username'      => $this->username,
                'password'      => $this->password,
            ]);

        $json = $response->json() ?? [];
        $token = $json['access_token'] ?? null;

        if (!$token) {
            throw new \RuntimeException(
                'GRE: no se obtuvo access_token (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        $this->cachedToken = (string) $token;
        // margen de 60s para no usar un token a punto de expirar
        $this->tokenExpiresAt = time() + max(60, (int) ($json['expires_in'] ?? 3300) - 60);

        return $this->cachedToken;
    }

    /**
     * Envía la guía firmada. Devuelve SummaryResult (flujo por ticket).
     *
     * @param string $filename  RUC-tipo-serie-numero (sin extensión)
     * @param string $signedXml XML DespatchAdvice firmado
     */
    public function sendDespatch(string $filename, string $signedXml): SummaryResult
    {
        try {
            $token = $this->getToken();
            $zip = (new ZipFly())->compress($filename . '.xml', $signedXml);

            $url = str_replace('{filename}', $filename, GreEndpoints::send($this->demo));

            $response = Http::withOptions(['verify' => $this->verifySsl])
                ->withToken($token)
                ->connectTimeout(5)
                ->timeout(30)
                ->post($url, [
                    'archivo' => [
                        'nomArchivo' => $filename . '.zip',
                        'arcGreZip'  => base64_encode($zip),
                        'hashZip'    => hash('sha256', $zip),
                    ],
                ]);

            return SummaryResult::fromArray($this->parseSend($response));
        } catch (\Throwable $e) {
            return SummaryResult::fromArray($this->fault($e->getMessage()));
        }
    }

    /**
     * Consulta el estado del ticket. Devuelve StatusResult (puede traer CDR).
     */
    public function getStatus(string $ticket): StatusResult
    {
        try {
            $token = $this->getToken();
            $url = str_replace('{ticket}', $ticket, GreEndpoints::ticket($this->demo));

            $response = Http::withOptions(['verify' => $this->verifySsl])
                ->withToken($token)
                ->connectTimeout(5)
                ->timeout(30)
                ->get($url);

            return StatusResult::fromArray($this->parseStatus($response));
        } catch (\Throwable $e) {
            return StatusResult::fromArray($this->fault($e->getMessage()));
        }
    }

    /**
     * @param \Illuminate\Http\Client\Response $response
     * @return array<string,mixed>
     */
    private function parseSend($response): array
    {
        $json = $response->json() ?? [];
        $ticket = $json['numTicket'] ?? null;

        if ($ticket) {
            return [
                'success'        => true,
                'connection'     => true,
                'sunat_success'  => true,
                'state_label'    => 'pendiente',
                'ticket'         => (string) $ticket,
                'date_reception' => $json['fecRecepcion'] ?? null,
                'code'           => '0',
                'message'        => 'Guía recibida por SUNAT. Ticket ' . $ticket,
            ];
        }

        // Error de recepción (sin ticket): SUNAT devuelve un objeto error.
        $err = $json['error'] ?? [];

        return [
            'success'       => false,
            'connection'    => true,
            'sunat_success' => false,
            'state_label'   => 'rechazado',
            'code'          => (string) ($err['numError'] ?? $json['cod'] ?? $response->status()),
            'message'       => $err['desError'] ?? $json['msg'] ?? ($response->body() ?: 'Envío GRE rechazado'),
        ];
    }

    /**
     * @param \Illuminate\Http\Client\Response $response
     * @return array<string,mixed>
     */
    private function parseStatus($response): array
    {
        $json = $response->json() ?? [];
        $cod = (string) ($json['codRespuesta'] ?? '');

        if ($cod === '0') {
            return [
                'success'       => true,
                'connection'    => true,
                'sunat_success' => true,
                'state_label'   => 'aceptado',
                'code'          => '0',
                'message'       => 'La Guía de Remisión fue aceptada por SUNAT',
                'cdr'           => $this->extractCdr($json),
            ];
        }

        if ($cod === '98') {
            return [
                'success'       => true,
                'connection'    => true,
                'sunat_success' => null,
                'state_label'   => 'en_proceso',
                'code'          => '98',
                'message'       => 'El comprobante está en proceso',
            ];
        }

        if ($cod === '99') {
            $err = $json['error'] ?? [];

            return [
                'success'       => true,
                'connection'    => true,
                'sunat_success' => false,
                'state_label'   => 'rechazado',
                'code'          => (string) ($err['numError'] ?? '99'),
                'message'       => $err['desError'] ?? 'La Guía de Remisión fue rechazada',
                'cdr'           => ($json['indCdrGenerado'] ?? '') === '1' ? $this->extractCdr($json) : null,
            ];
        }

        // Código no esperado.
        return [
            'success'       => false,
            'connection'    => true,
            'sunat_success' => null,
            'state_label'   => 'indeterminado',
            'code'          => $cod !== '' ? $cod : (string) $response->status(),
            'message'       => $json['msg'] ?? ($response->body() ?: 'Respuesta GRE no reconocida'),
        ];
    }

    /** Decodifica el CDR (ZIP base64) y extrae el XML del ApplicationResponse. */
    private function extractCdr(array $json): ?string
    {
        if (empty($json['arcCdr'])) {
            return null;
        }
        try {
            return (new ZipExtractor())->extractXmlFromZip(base64_decode($json['arcCdr']));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return array<string,mixed> */
    private function fault(string $message): array
    {
        return [
            'success'       => false,
            'connection'    => false,
            'sunat_success' => null,
            'state_label'   => 'indeterminado',
            'code'          => null,
            'message'       => $message,
        ];
    }
}
