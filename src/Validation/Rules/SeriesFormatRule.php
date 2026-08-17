<?php

namespace ESolutions\XmlSimple\Validation\Rules;

use ESolutions\XmlSimple\Results\ValidationError;

class SeriesFormatRule
{
    public function validate(array $doc, string $type): array
    {
        // La regla de serie solo aplica a comprobantes con serie F###/B###.
        // Resúmenes, bajas, guías, retenciones y percepciones tienen otra
        // nomenclatura (RC-/RA-/T###/R###/P###) y no se validan aquí.
        if (!in_array($type, ['invoice', 'boleta', 'credit_note', 'debit_note'], true)) {
            return [];
        }

        $errors = [];

        $series = $this->extractSeries($doc);
        if ($series === null || $series === '') {
            return [new ValidationError(
                'No se pudo determinar la serie del documento (document.series o document.id).',
                'BR_SERIES_MISSING'
            )];
        }

        $series = strtoupper(trim($series));

        // Prefijo esperado: para notas SIEMPRE depende del documento relacionado
        $expectedPrefix = $this->expectedPrefix($doc, $type);
        if ($expectedPrefix === null) {
            return [new ValidationError(
                'No se pudo determinar el tipo de documento relacionado (01/03) para validar el prefijo de la serie de la nota.',
                'BR_SERIES_RELATED_DOCTYPE_REQUIRED'
            )];
        }

        // 1) Longitud exacta 4
        if (strlen($series) !== 4) {
            $errors[] = new ValidationError(
                "La serie debe tener 4 caracteres. Serie recibida: '{$series}'. Ejemplo: {$expectedPrefix}001 / {$expectedPrefix}A01.",
                'BR_SERIES_LENGTH'
            );
        }

        // 2) Formato exacto: 1er char = expectedPrefix, 2do alfanumérico, últimos 2 dígitos
        $pattern = '/^' . preg_quote($expectedPrefix, '/') . '[A-Z0-9][0-9]{2}$/';
        if (!preg_match($pattern, $series)) {
            $errors[] = new ValidationError(
                "Formato de serie inválido. Se esperaba: '{$expectedPrefix}' + [A-Z0-9] + [0-9][0-9]. Serie recibida: '{$series}'.",
                'BR_SERIES_FORMAT'
            );
        }

        // 3) Prefijo (mensaje directo)
        if (strlen($series) > 0 && $series[0] !== $expectedPrefix) {
            $errors[] = new ValidationError(
                "Prefijo de serie inválido. Para este documento se espera que la serie comience con '{$expectedPrefix}'. Serie recibida: '{$series}'.",
                'BR_SERIES_PREFIX'
            );
        }

        return $errors;
    }

    protected function extractSeries(array $doc): ?string
    {
        $series = data_get($doc, 'document.series');
        if (is_string($series) && $series !== '') {
            return $series;
        }

        $id = data_get($doc, 'document.id');
        if (!is_string($id) || $id === '') {
            return null;
        }

        $id = trim($id);

        // Formato típico: "FC01-123" => "FC01"
        if (strpos($id, '-') !== false) {
            $parts = explode('-', $id, 2);
            return $parts[0] ?? null;
        }

        return substr($id, 0, 4);
    }

    /**
     * - invoice/boleta: reglas directas
     * - credit/debit: SIEMPRE usa findRelatedDocType()
     */
    protected function expectedPrefix(array $doc, string $type): ?string
    {
        $documentTypeId = (string) (data_get($doc, 'document.document_type_id') ?? '');
        // Documentos principales
        if ($documentTypeId === '01') return 'F';
        if ($documentTypeId === '03') return 'B';

        // Notas: siempre depende del doc relacionado
        if ($documentTypeId === '07' || $documentTypeId === '08') {
            $relatedDocType = $this->findRelatedDocType($doc);

            if ($relatedDocType === '01') return 'F';
            if ($relatedDocType === '03') return 'B';

            return null;
        }

        return null;
    }

    /**
     * Para notas, se asume que SIEMPRE envías docType del documento afectado (01/03).
     * Ajusta los paths según tu payload real.
     */
    protected function findRelatedDocType(array $doc): ?string
    {
        $candidates = [
            // contrato v2 (ver docs/payloads/credit-note.md)
            'document.affected_document.document_type_id',
        ];

        foreach ($candidates as $path) {
            $v = data_get($doc, $path);
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
        }

        return null;
    }
}
