<?php

namespace ESolutions\XmlSimple\Sending;

use ESolutions\XmlSimple\Contracts\ErrorCodeCatalogInterface;
use ESolutions\XmlSimple\Contracts\ZipCompressorInterface;
use ESolutions\XmlSimple\Results\Sending\BaseResult;
use ESolutions\XmlSimple\Results\Sending\BillResult;
use ESolutions\XmlSimple\Results\Sending\StatusResult;
use ESolutions\XmlSimple\Results\Sending\SummaryResult;
use ESolutions\XmlSimple\Sending\Endpoints\NubefactEndpoints;
use ESolutions\XmlSimple\Sending\Endpoints\SunatEndpoints;
use ESolutions\XmlSimple\Sending\Resolver\XmlTypeResolver;
use ESolutions\XmlSimple\Sending\Soap\SoapSunatClient;
use ESolutions\XmlSimple\Sending\Soap\SunatResponseBuilder;
use ESolutions\XmlSimple\Sending\Zip\ZipFly;

/**
 * Orquestador de envío a SUNAT/OSE. Independiente del proyecto consumidor:
 * toda la configuración entra por SenderConfig.
 *
 *   $sender = new DocumentSender(SenderConfig::fromConfig());
 *   $result = $sender->send($filename, $signedXml);   // resuelve sendBill vs sendSummary solo
 *   if ($result->isAccepted()) { ... $result->getCdrXml() ... }
 */
class DocumentSender
{
    protected SenderConfig $config;
    protected ZipCompressorInterface $zip;
    protected ?ErrorCodeCatalogInterface $catalog;
    protected XmlTypeResolver $typeResolver;

    public function __construct(
        SenderConfig $config,
        ?ZipCompressorInterface $zip = null,
        ?ErrorCodeCatalogInterface $catalog = null
    ) {
        $this->config = $config;
        $this->zip = $zip ?? new ZipFly();
        $this->catalog = $catalog;
        $this->typeResolver = new XmlTypeResolver();
    }

    /**
     * Fachada: decide la operación SOAP inspeccionando el XML firmado.
     * Comprobantes (Invoice/Note/Despatch/Retention/Perception) => sendBill;
     * SummaryDocuments/VoidedDocuments => sendSummary.
     *
     * @param string $filename Sin extensión (RUC-TIPO-SERIE-NUMERO)
     */
    public function send(string $filename, string $signedXml): BaseResult
    {
        $operation = $this->typeResolver->resolveOperation($signedXml);

        return $operation === XmlTypeResolver::OPERATION_SUMMARY
            ? $this->sendSummary($filename, $signedXml)
            : $this->sendBill($filename, $signedXml);
    }

    /**
     * Envío síncrono (sendBill): la respuesta trae el CDR.
     *
     * @param string $filename Sin extensión
     */
    public function sendBill(string $filename, string $signedXml): BillResult
    {
        $result = $this->callSoap('sendBill', $this->billParams($filename, $signedXml));

        return BillResult::fromArray(SunatResponseBuilder::fromSendBill($result, $this->catalog) + ['time' => $result['time']]);
    }

    /**
     * Envío asíncrono (sendSummary): la respuesta trae un ticket a consultar
     * luego con getStatus().
     *
     * @param string $filename Sin extensión (RUC-RC-YYYYMMDD-###)
     */
    public function sendSummary(string $filename, string $signedXml): SummaryResult
    {
        $result = $this->callSoap('sendSummary', $this->billParams($filename, $signedXml));

        return SummaryResult::fromArray(SunatResponseBuilder::fromSendSummary($result, $this->catalog) + ['time' => $result['time']]);
    }

    /**
     * Consulta el estado de un ticket de sendSummary.
     * Código 98 => aún en proceso ($result->isInProcess()).
     */
    public function getStatus(string $ticket): StatusResult
    {
        $result = $this->callSoap('getStatus', ['ticket' => $ticket]);

        return StatusResult::fromArray(SunatResponseBuilder::fromGetStatus($result, $this->catalog) + ['time' => $result['time']]);
    }

    // ---------------------------------------------------------------

    protected function billParams(string $filename, string $signedXml): array
    {
        return [
            'fileName' => $filename . '.zip',
            'contentFile' => $this->zip->compress($filename . '.xml', $signedXml),
        ];
    }

    protected function callSoap(string $method, array $params): array
    {
        $endpoint = $this->getEndpoint($method);
        $client = new SoapSunatClient($endpoint, $this->config->username, $this->config->password);

        $startTime = microtime(true);
        $result = $client->send($method, $params);
        $result['time'] = microtime(true) - $startTime;
        $result['provider'] = $this->config->provider;

        return $result;
    }

    /**
     * Resuelve el endpoint según proveedor, ambiente y tipo de documento.
     * Un endpoint explícito en SenderConfig (OSE/PSE) manda sobre todo.
     */
    protected function getEndpoint(string $operation): string
    {
        if ($this->config->endpoint) {
            return $this->config->endpoint;
        }

        if ($this->config->provider === 'nubefact') {
            return $this->config->isDemo() ? NubefactEndpoints::BETA : NubefactEndpoints::PRODUCTION;
        }

        // SUNAT directo
        $isRetention = in_array((string) $this->config->documentTypeId, ['20', '40', 'RR'], true);
        if ($isRetention) {
            return $this->config->isDemo()
                ? SunatEndpoints::RETENCION_BETA
                : SunatEndpoints::RETENCION_PRODUCCION;
        }

        return $this->config->isDemo()
            ? SunatEndpoints::FE_BETA
            : SunatEndpoints::FE_PRODUCTION;
    }
}
