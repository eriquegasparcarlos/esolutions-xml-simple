<?php

namespace ESolutions\XmlSimple\Sending\Catalog;

use ESolutions\XmlSimple\Contracts\ErrorCodeCatalogInterface;

/**
 * Catálogo de códigos SUNAT desde el XML empaquetado
 * (src/Resources/data/CodeErrors.xml, portado del validador oficial).
 * Se parsea una sola vez por proceso (cache estático).
 */
class FileErrorCodeCatalog implements ErrorCodeCatalogInterface
{
    /** @var array<string, string>|null */
    private static ?array $codes = null;

    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?: dirname(__DIR__, 2) . '/Resources/data/CodeErrors.xml';
    }

    public function describe(string $code): ?string
    {
        if (self::$codes === null) {
            self::$codes = $this->load();
        }

        return self::$codes[$code] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function load(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $codes = [];
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($this->file);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            return [];
        }

        foreach ($xml->error as $error) {
            $code = (string) $error['code'];
            if ($code !== '' && !isset($codes[$code])) {
                $codes[$code] = trim((string) $error);
            }
        }

        return $codes;
    }
}
