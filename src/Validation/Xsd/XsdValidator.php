<?php

namespace ESolutions\XmlSimple\Validation\Xsd;

use ESolutions\XmlSimple\Results\ValidationError;
use ESolutions\XmlSimple\Results\ValidationResult;

class XsdValidator
{
    /** @var SchemaResolver */
    protected $resolver;

    public function __construct(SchemaResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function validate(string $normalizedType, string $xml): ValidationResult
    {
        if (!config('esolutions_xml_simple.validation.xsd_enabled', true)) {
            return ValidationResult::ok();
        }

        $xsdPath = $this->resolver->resolve($normalizedType);
        if (!$xsdPath || !is_file($xsdPath)) {
            return ValidationResult::fail([
                new ValidationError("No se encontró XSD para el tipo: {$normalizedType}", 'XSD_NOT_FOUND', $xsdPath),
            ]);
        }

        $useInternal = (bool) config('esolutions_xml_simple.validation.libxml.use_internal_errors', true);
        $options = (int) config('esolutions_xml_simple.validation.libxml.options', LIBXML_NONET);

        libxml_use_internal_errors($useInternal);
        libxml_clear_errors();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;

        // OJO: loadXML falla si el XML viene con caracteres inválidos
        $loaded = $dom->loadXML($xml, $options);

        if (!$loaded) {
            $errors = $this->buildErrorsFromLibxml($xml, 'XML_PARSE');
            return ValidationResult::fail($errors ?: [new ValidationError('XML inválido (no se pudo parsear).', 'XML_PARSE')]);
        }

        $ok = $dom->schemaValidate($xsdPath);

        if ($ok) {
            libxml_clear_errors();
            return ValidationResult::ok();
        }

        $errors = $this->buildErrorsFromLibxml($xml, 'XSD');
        return ValidationResult::fail($errors ?: [new ValidationError('No pasó validación XSD.', 'XSD')]);
    }

    /**
     * Convierte los errores libxml en ValidationError, agregando:
     * - mensaje humanizado
     * - snippet del XML cerca de la línea (si existe)
     */
    protected function buildErrorsFromLibxml(string $xml, string $fallbackCode): array
    {
        $errs = libxml_get_errors();
        $errors = [];

        foreach ($errs as $e) {
            $raw = trim($e->message);

            // Mensaje más claro
            $human = $this->humanizeXsdMessage($raw);

            // Snippet alrededor de la línea
            $line = $e->line ?: null;
            $snippet = $line ? $this->xmlSnippet($xml, $line, 2) : null;

            // Guardamos el humanizado como message principal,
            // y anexamos el raw + snippet en el mismo message para no tocar el DTO.
            $finalMessage = $human;

            if ($human !== $raw) {
                $finalMessage .= "\n" . "Detalle técnico: " . $raw;
            }

            if (!empty($snippet)) {
                $finalMessage .= "\n" . "Snippet:\n" . $snippet;
            }

            // Mantén el code XSD/parse
            $code = ($fallbackCode === 'XML_PARSE') ? 'XML_PARSE' : 'XSD';

            $errors[] = new ValidationError($finalMessage, $code, null, $line);
        }

        libxml_clear_errors();
        return $errors;
    }

    /**
     * Convierte mensajes XSD típicos a mensajes entendibles.
     * Puedes ir agregando patrones aquí conforme salgan nuevos casos.
     */
    protected function humanizeXsdMessage(string $msg): string
    {
        // 1) UBLExtensions vacío
        if (stripos($msg, 'UBLExtensions') !== false && stripos($msg, 'Missing child element') !== false) {
            return "UBLExtensions no puede ir vacío: falta al menos un <ext:UBLExtension> dentro de <ext:UBLExtensions>. "
                . "Si aún no firmas, agrega un placeholder:\n"
                . "<ext:UBLExtension><ext:ExtensionContent/></ext:UBLExtension>";
        }

        // 2) PricingReference no esperado (casi siempre: se repitió o está fuera de orden)
        if (stripos($msg, 'PricingReference') !== false && stripos($msg, 'not expected') !== false) {
            return "El nodo <cac:PricingReference> está en posición inválida o se repite más de lo permitido. "
                . "En UBL normalmente debe existir SOLO un <cac:PricingReference> por línea, "
                . "y dentro colocar múltiples <cac:AlternativeConditionPrice> (01, 02, etc.).";
        }

        // 3) Elemento no esperado genérico (mejora general)
        if (stripos($msg, 'This element is not expected') !== false) {
            return "Estructura XML inválida para el XSD: hay un elemento en una posición no permitida. "
                . "Casi siempre es por ORDEN incorrecto o por repetir un nodo que solo permite 1 ocurrencia.";
        }

        return $msg;
    }

    /**
     * Muestra líneas cercanas a la línea del error para ubicar rápido el problema.
     *
     * @param string $xml
     * @param int    $line        (1-based)
     * @param int    $radius      líneas antes/después
     */
    protected function xmlSnippet(string $xml, int $line, int $radius = 2): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $xml);
        if (!$lines || $line < 1 || $line > count($lines)) {
            return '';
        }

        $start = max(1, $line - $radius);
        $end   = min(count($lines), $line + $radius);

        $out = [];
        for ($i = $start; $i <= $end; $i++) {
            $prefix = ($i === $line) ? '>>' : '  ';
            $out[] = sprintf("%s %5d | %s", $prefix, $i, $lines[$i - 1]);
        }

        return implode("\n", $out);
    }
}
