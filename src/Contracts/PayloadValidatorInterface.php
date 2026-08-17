<?php

namespace ESolutions\XmlSimple\Contracts;

use ESolutions\XmlSimple\Results\ValidationResult;

interface PayloadValidatorInterface
{
    /**
     * Valida que el payload tenga la estructura mínima que exige la
     * plantilla del tipo de documento. No valida reglas de negocio SUNAT
     * (eso es de XmlValidationPipeline) — solo el contrato de entrada.
     */
    public function validate(string $normalizedType, array $payload): ValidationResult;
}
