<?php

namespace ESolutions\XmlSimple\Contracts;

interface CdrResponseParserInterface
{
    /**
     * Parsea la respuesta del CDR.
     * @param array $result
     * @return array Estructura estandarizada
     */
    public function parseBill(array $result): array;

    /**
     * Parsea la respuesta en el envío del resumen.
     * @param array $result
     * @return array Estructura estandarizada
     */
    public function parseSummary(array $result): array;

    /**
     * Parsea la respuesta en la consulta del ticket.
     * @param array $result
     * @return array Estructura estandarizada
     */
    public function parseGetStatus(array $result): array;
}
