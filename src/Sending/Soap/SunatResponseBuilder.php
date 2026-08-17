<?php

namespace ESolutions\XmlSimple\Sending\Soap;

use ESolutions\XmlSimple\Contracts\ErrorCodeCatalogInterface;
use ESolutions\XmlSimple\Sending\Cdr\CdrResponseParserFactory;

/**
 * Interpreta y unifica la respuesta de SUNAT/OSE para todos los flujos.
 */
class SunatResponseBuilder
{
    public static function fromSendBill(array $result, ?ErrorCodeCatalogInterface $catalog = null): array
    {
        $provider = $result['provider'] ?? 'sunat';
        $parser = CdrResponseParserFactory::make($provider, $catalog);

        return $parser->parseBill($result);
    }

    public static function fromSendSummary(array $result, ?ErrorCodeCatalogInterface $catalog = null): array
    {
        $provider = $result['provider'] ?? 'sunat';
        $parser = CdrResponseParserFactory::make($provider, $catalog);

        return $parser->parseSummary($result);
    }

    public static function fromGetStatus(array $result, ?ErrorCodeCatalogInterface $catalog = null): array
    {
        $provider = $result['provider'] ?? 'sunat';
        $parser = CdrResponseParserFactory::make($provider, $catalog);

        return $parser->parseGetStatus($result);
    }
}
