<?php

namespace ESolutions\XmlSimple\Validation\Xsd;

class SchemaResolver
{
    public function resolve(string $normalizedType): ?string
    {
        $schemaFile = config('esolutions_xml_simple.schemas.' . $normalizedType);

        if (!$schemaFile) {
            return null;
        }

        // Sin xsd_base en config se usan los XSD empaquetados (src/Resources/xsd).
        $base = config('esolutions_xml_simple.paths.xsd_base') ?: dirname(__DIR__, 2) . '/Resources/xsd';

        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $schemaFile;
    }
}
