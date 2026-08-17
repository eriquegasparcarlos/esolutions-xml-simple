<?php

namespace ESolutions\XmlSimple\Contracts;

interface ErrorCodeCatalogInterface
{
    /**
     * Traduce un código de respuesta/error SUNAT a un mensaje legible.
     * Retorna null si el código no está catalogado (el llamador decide
     * el fallback, normalmente la descripción cruda del CDR/Fault).
     */
    public function describe(string $code): ?string;
}
