<?php

namespace ESolutions\XmlSimple\Generator;

use ESolutions\XmlSimple\Contracts\PayloadValidatorInterface;
use ESolutions\XmlSimple\Contracts\XmlDocumentGeneratorContract;
use ESolutions\XmlSimple\Rendering\XmlTemplateRenderer;
use ESolutions\XmlSimple\Results\GenerationResult;
use ESolutions\XmlSimple\Results\ValidationResult;
use ESolutions\XmlSimple\Sign\Signed;
use ESolutions\XmlSimple\Support\DocTypeNormalizer;
use ESolutions\XmlSimple\Support\XmlFormatter;
use ESolutions\XmlSimple\Validation\XmlValidationPipeline;

class XmlDocumentGenerator implements XmlDocumentGeneratorContract
{
    /** @var XmlTemplateRenderer */
    protected $renderer;

    /** @var XmlValidationPipeline */
    protected $validation;

    /** @var Signed */
    protected $signer;

    /** @var PayloadValidatorInterface */
    protected $payloadValidator;

    public function __construct(
        XmlTemplateRenderer $renderer,
        XmlValidationPipeline $validation,
        Signed $signer,
        PayloadValidatorInterface $payloadValidator
    ) {
        $this->renderer = $renderer;
        $this->validation = $validation;
        $this->signer = $signer;
        $this->payloadValidator = $payloadValidator;
    }

    /**
     * Genera XML UBL según tipo, firma, y valida (XSD + reglas de negocio).
     *
     * Flujo:
     *  0) Validar contrato del payload (claves requeridas por tipo)
     *  1) Render (Blade)
     *  2) Formatear (solo para legibilidad y line numbers estables)
     *  3) Firmar (inserta ds:Signature dentro de ext:ExtensionContent)
     *  4) Validar sobre el XML FINAL (firmado)
     */
    public function generate(
        string  $type,
        array   $payload,
        ?string $certificateFile = null,
        ?string $certificatePassword = null
    ): GenerationResult
    {
        // Normaliza type (invoice, credit_note, etc.)
        $normalizedType = DocTypeNormalizer::normalize($type);

        // 0) Contrato de entrada: si el payload no cumple el esquema del tipo,
        //    se retorna un resultado fallido con errores claros en lugar de
        //    reventar con "Undefined array key" dentro de la plantilla.
        $payloadValidation = $this->payloadValidator->validate($normalizedType, $payload);
        if (!$payloadValidation->ok) {
            $doc = (isset($payload['document']) && is_array($payload['document'])) ? $payload['document'] : [];
            return GenerationResult::failed($normalizedType, $payloadValidation, $doc);
        }

        // Vista blade a usar (configurable)
        $view = config('esolutions_xml_simple.views.' . $normalizedType) ?: 'invoice';
        // 1) Render del XML (esto aún NO está firmado)
        $xml = $this->renderer->render($view, $payload);
        // 2) Formateo (solo ANTES de firmar)
        //    Importante: luego de firmar NO se debe reformatear porque cambia el XML firmado.
        if (class_exists(XmlFormatter::class)) {
            $xml = XmlFormatter::format($xml, true, true);
        }
        // Guardamos el unsigned para debugging/compare
        $unsigned = $xml;

        // Nota: la validación de reglas SUNAT (SFS) pre-firma no forma parte de
        // este paquete; esa capa avanzada es del servicio xmlperu.
        $warnings = [];

        // 3) Firmado — metadata para actualizar cac:Signature (cbc:ID, cbc:Note, cbc:URI)
        $signatureMeta = [
            'signatureId' => $payload['document']['signature_note'] ?? config('esolutions_xml_simple.signing.signature_note'),
            'signatureUri' => $payload['document']['signature_uri'] ?? config('esolutions_xml_simple.signing.signature_uri'),
        ];

        // Si el template no trae ExtensionContent, la firma no puede insertarse
        // donde SUNAT la exige — error claro en vez de "Signature DOM element not found".
        if (strpos($xml, 'ext:ExtensionContent') === false) {
            throw new \RuntimeException(
                'El XML no contiene ext:ExtensionContent. Revisa que el template incluya UBLExtensions/UBLExtension/ExtensionContent.'
            );
        }

        // Firma con cert (si null => usa demo) + meta del payload
        $xml = $this->signer->xmlSigned($xml, $certificateFile, $certificatePassword, $signatureMeta);

        // 4) Validación XSD + reglas de negocio sobre el XML FINAL (firmado)
        $validation = $this->validation->validate($normalizedType, $payload, $xml);

        $doc = (isset($payload['document']) && is_array($payload['document'])) ? $payload['document'] : $payload;

        $result = new GenerationResult(
            $normalizedType,
            $doc,
            $xml,
            $validation,
            $unsigned,
            $this->signer->getLastCertificateInfo(),
            $this->signer->getLastSignatureMeta()
        );
        $result->warnings = $warnings;

        return $result;
    }
}
