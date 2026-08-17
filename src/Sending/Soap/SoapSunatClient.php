<?php

namespace ESolutions\XmlSimple\Sending\Soap;

use ESolutions\XmlSimple\Sending\Soap\WsdlProvider;
use ESolutions\XmlSimple\Sending\Soap\WsSecurityHeader;
use SoapClient;
use SoapFault;
use Throwable;

/**
 * Cliente SOAP para conexión con SUNAT u OSE.
 * Devuelve siempre estructura de respuesta robusta y estandarizada.
 */
class SoapSunatClient
{
    protected string $endpoint;
    protected string $ws;
    protected string $username;
    protected string $password;
    protected array $options;

    /**
     * @param string $endpoint URL del servicio (SUNAT/OSE)
     * @param string $username Usuario SOL (RUC + usuario)
     * @param string $password Clave SOL
     * @param string $ws WsdlProvider
     * @param array $options Opciones adicionales para SoapClient
     * Soporta:
     * - connection_timeout (int, seg)  => timeout de conexión TCP
     * - io_timeout (int, seg)          => timeout de lectura/escritura HTTP (stream)
     */
    public function __construct(string $endpoint, string $username, string $password, string $ws = '', array $options = [])
    {
        $this->ws = ($ws === '') ? WsdlProvider::getBillPath() : $ws;
        $this->username = $username;
        $this->endpoint = $endpoint;
        $this->password = $password;

        // Valores por defecto razonables para entornos SUNAT/OSE
        $defaultConnectTimeout = 5;  // conexión TCP
        $defaultIoTimeout = 15;  // lectura total de la respuesta (puede ser pesada por ZIP/CDR)
        $ioTimeout = (int)($options['io_timeout'] ?? $defaultIoTimeout);
        $connTimeout = (int)($options['connection_timeout'] ?? $defaultConnectTimeout);

        $streamContext = stream_context_create([
            'http' => [
                // Timeout total de la operación HTTP (lectura/escritura)
                'timeout' => $ioTimeout,
                'protocol_version' => 1.1,
                'header' => "Connection: close\r\n",
            ],
            // Opcionalmente, puedes endurecer SSL si usas HTTPS:
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
                // 'cafile'        => storage_path('certs/cacert.pem'), // si tienes CA personalizada
            ],
        ]);

        $this->options = array_merge([
            'trace' => 1,
            'exceptions' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'soap_version' => SOAP_1_1,
            // Timeout de conexión TCP (no cubre lectura de cuerpo)
            'connection_timeout' => $connTimeout,
            // Timeout de IO HTTP (sí cubre lectura de cuerpo)
            'stream_context'     => $streamContext,
            // Acelera si el servidor soporta compresión
            // 'compression'    => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
        ], $options);

        unset($this->options['io_timeout']);
    }

    /**
     * Ejecuta una operación SOAP con WS-Security.
     * Devuelve estructura unificada para cualquier resultado.
     *
     * @param string $method Nombre del método SOAP (sendBill, sendSummary, etc.)
     * @param array $params Parámetros de la operación
     * @return array
     */
    public function send(string $method, array $params, ?int $ioTimeoutOverride = null): array
    {
        $prevSocketTimeout = ini_get('default_socket_timeout'); // guarda el valor global
        $ioTimeout = $ioTimeoutOverride ??  (int) (stream_context_get_options($this->options['stream_context'])['http']['timeout'] ?? 60);

        try {
            // 1) Forzar default_socket_timeout para que SoapClient no se vaya a 60s
            ini_set('default_socket_timeout', (string)$ioTimeout);

            // 2) Reconstruir stream_context con http.timeout y socket.timeout
            $ctx = stream_context_get_options($this->options['stream_context']);
            $ctx['http']['timeout'] = $ioTimeout;
            // también timeout a nivel socket (algunas builds de PHP/libxml lo respetan mejor)
            $ctx['socket']['timeout'] = $ioTimeout;
            $options = $this->options;
            $options['stream_context'] = stream_context_create($ctx);

            // 3) Crear SoapClient y llamar
            $client = new SoapClient($this->ws, $this->options);
            $client->__setSoapHeaders(new WsSecurityHeader($this->username, $this->password));
            $client->__setLocation($this->endpoint);

            $response = $client->__soapCall($method, ['parameters' => $params]);
            $rawResponse = $client->__getLastResponse();

            return [
                'success' => true, // Solo false si no hay comunicación (catch Throwable)
                'connection' => true, // hubo comunicación con SUNAT/OSE
                'sunat_success' => null, // El builder/reader analizará la respuesta
                'message' => 'Respuesta recibida correctamente.',
                'code' => null,
                'ticket' => property_exists($response, 'ticket') ? $response->ticket : null,
                'cdr' => property_exists($response, 'content') ? $response->content : null,
                'errors' => null,
                'raw' => $rawResponse,
                'response' => $response,
            ];
        } catch (SoapFault $fault) {
            $rawResponse = isset($client) ? $client->__getLastResponse() : null;
            // Un SoapFault puede ser (a) de CONEXIÓN (no se llegó al WS) o (b) del
            // servidor/negocio (el WS respondió con Fault). Se distingue por el mensaje.
            $isConnError = self::isConnectionFault($fault);
            return [
                'success' => !$isConnError,     // conexión rota => no hubo respuesta útil
                'connection' => !$isConnError,  // origen del error: conexión vs SUNAT/sistema
                'sunat_success' => null,        // el parser/builder lo determinará
                'message' => ($isConnError ? 'Error de conexión: ' : 'Error SOAP: ') . $fault->getMessage(),
                'code' => $isConnError ? 'CONNECTION_ERROR' : ($fault->faultcode ?? 'SOAP_ERROR'),
                'ticket' => null,
                'cdr' => null,
                'errors' => [$fault->getMessage()],
                'raw' => $rawResponse,
                'response' => null,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false, // No hubo comunicación
                'connection' => false,
                'sunat_success' => null,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'code' => 'CONNECTION_ERROR',
                'ticket' => null,
                'cdr' => null,
                'errors' => [$e->getMessage()],
                'raw' => null,
                'response' => null,
            ];
        } finally {
            // 4) Restaurar el timeout global
            ini_set('default_socket_timeout', (string)$prevSocketTimeout);
        }
    }

    /**
     * ¿El SoapFault se debe a falta de comunicación (transporte) y no a una
     * respuesta del WS de SUNAT/OSE? Distingue el error de CONEXIÓN del de negocio.
     */
    private static function isConnectionFault(SoapFault $fault): bool
    {
        $m = strtolower($fault->getMessage());
        foreach ([
            'could not connect', 'connection timed out', 'failed to connect',
            'error fetching http headers', 'connection refused',
            'name or service not known', 'could not resolve host',
            'operation timed out', 'ssl',
        ] as $needle) {
            if (str_contains($m, $needle)) {
                return true;
            }
        }
        // faultcode "HTTP" => error de transporte, no de negocio SUNAT.
        return isset($fault->faultcode) && stripos((string) $fault->faultcode, 'HTTP') !== false;
    }
}
