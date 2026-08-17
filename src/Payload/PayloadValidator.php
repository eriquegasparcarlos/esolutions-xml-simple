<?php

namespace ESolutions\XmlSimple\Payload;

use ESolutions\XmlSimple\Contracts\PayloadValidatorInterface;
use ESolutions\XmlSimple\Results\ValidationError;
use ESolutions\XmlSimple\Results\ValidationResult;

/**
 * Valida el contrato de entrada (array payload) contra el esquema del tipo
 * de documento definido en Payload/Schemas/{tipo}.php.
 *
 * Cada esquema retorna:
 *   'required'  => claves en dot-notation que deben existir y no ser null
 *   'present'   => claves que deben existir (null permitido — la plantilla
 *                  las lee con acceso directo, p.ej. @if($document['x']))
 *   'non_empty' => claves que además no pueden ser cadena vacía o solo
 *                  espacios (SUNAT rechaza el nodo resultante)
 *
 * Soporta comodín '*' para arrays de items: 'document.items.*.quantity'.
 */
class PayloadValidator implements PayloadValidatorInterface
{
    protected const MODE_REQUIRED = 'required';
    protected const MODE_PRESENT = 'present';
    protected const MODE_NON_EMPTY = 'non_empty';

    public function validate(string $normalizedType, array $payload): ValidationResult
    {
        $schema = $this->loadSchema($normalizedType);

        // Tipo sin esquema definido: no se bloquea la generación.
        if ($schema === null) {
            return ValidationResult::ok();
        }

        $errors = [];
        $seen = [];

        foreach ([self::MODE_REQUIRED, self::MODE_PRESENT, self::MODE_NON_EMPTY] as $mode) {
            foreach ($schema[$mode] ?? [] as $path) {
                foreach ($this->invalidPaths($payload, explode('.', $path), '', $mode) as $invalid) {
                    // Varias reglas wildcard comparten padre ('items.*.x', 'items.*.y'):
                    // si falta el padre, se reporta una sola vez.
                    if (isset($seen[$invalid])) {
                        continue;
                    }
                    $seen[$invalid] = true;
                    $errors[] = new ValidationError(
                        $this->message($mode, $invalid, $normalizedType),
                        'PAYLOAD',
                        $invalid
                    );
                }
            }
        }

        return $errors ? ValidationResult::fail($errors) : ValidationResult::ok();
    }

    protected function message(string $mode, string $path, string $normalizedType): string
    {
        $docs = "(ver docs/payloads/{$normalizedType}.md)";

        return match ($mode) {
            self::MODE_PRESENT => "La clave '{$path}' debe existir en el payload de '{$normalizedType}' aunque sea null {$docs}.",
            self::MODE_NON_EMPTY => "La clave '{$path}' del payload de '{$normalizedType}' es obligatoria y no puede estar vacía {$docs}.",
            default => "Falta la clave requerida '{$path}' en el payload de '{$normalizedType}' {$docs}.",
        };
    }

    protected function loadSchema(string $normalizedType): ?array
    {
        $file = __DIR__ . '/Schemas/' . $normalizedType . '.php';

        return is_file($file) ? require $file : null;
    }

    /**
     * Devuelve las rutas concretas que incumplen el modo (con índices
     * expandidos para '*').
     *
     * @param array<int, string> $segments
     * @return array<int, string>
     */
    protected function invalidPaths(mixed $data, array $segments, string $prefix, string $mode): array
    {
        if ($segments === []) {
            return [];
        }

        $segment = array_shift($segments);

        if ($segment === '*') {
            if (!is_array($data)) {
                return [$this->joinPath($prefix, '*')];
            }
            $invalid = [];
            foreach ($data as $index => $item) {
                $invalid = array_merge(
                    $invalid,
                    $this->invalidPaths($item, $segments, $this->joinPath($prefix, (string) $index), $mode)
                );
            }
            return $invalid;
        }

        $path = $this->joinPath($prefix, $segment);

        if (!is_array($data) || !array_key_exists($segment, $data)) {
            return [$path];
        }

        if ($segments === []) {
            return $this->leafFails($data[$segment], $mode) ? [$path] : [];
        }

        return $this->invalidPaths($data[$segment], $segments, $path, $mode);
    }

    protected function leafFails(mixed $value, string $mode): bool
    {
        return match ($mode) {
            self::MODE_PRESENT => false,
            self::MODE_NON_EMPTY => $value === null
                || (is_string($value) && trim($value) === '')
                || $value === [],
            default => $value === null,
        };
    }

    protected function joinPath(string $prefix, string $segment): string
    {
        return $prefix === '' ? $segment : "{$prefix}.{$segment}";
    }
}
