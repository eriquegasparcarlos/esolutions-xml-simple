<?php

namespace ESolutions\XmlSimple\Sending\Catalog;

use ESolutions\XmlSimple\Contracts\ErrorCodeCatalogInterface;

/**
 * Catálogo vacío: siempre delega al fallback del llamador
 * (la descripción cruda que trae el CDR o el SoapFault).
 */
class NullErrorCodeCatalog implements ErrorCodeCatalogInterface
{
    public function describe(string $code): ?string
    {
        return null;
    }
}
