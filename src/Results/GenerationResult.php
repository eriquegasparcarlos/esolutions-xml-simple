<?php

namespace ESolutions\XmlSimple\Results;

class GenerationResult
{
    /** @var string */
    public $type;

    /** @var array */
    public $doc;

    /** @var string */
    public $xml;

    /** @var ValidationResult */
    public $validation;

    /** @var string|null */
    public $unsignedXml;

    /** @var array|null */
    public $certificate;

    /** @var array|null  Ej: ['digest_value' => '...', 'signature_value' => '...'] */
    public $signatureMeta;

    /**
     * Observaciones SUNAT (códigos >= 4000) que NO bloquearon la generación
     * cuando el gate pre-firma corre con block_on_observations=false. El
     * comprobante se firmó igual, pero conviene mostrarlas/solventarlas.
     *
     * @var \ESolutions\XmlSimple\Results\ValidationError[]
     */
    public $warnings = [];

    public function __construct(
        string $type,
        array $doc,
        string $xml,
        ValidationResult $validation,
        ?string $unsignedXml = null,
        ?array $certificate = null,
        ?array $signatureMeta = null
    ) {
        $this->type = $type;
        $this->doc = $doc;
        $this->xml = $xml;
        $this->validation = $validation;
        $this->unsignedXml = $unsignedXml;
        $this->certificate = $certificate;
        $this->signatureMeta = $signatureMeta;
    }

    /**
     * Resultado fallido sin XML (p.ej. payload inválido antes del render).
     */
    public static function failed(string $type, ValidationResult $validation, array $doc = []): self
    {
        return new self($type, $doc, '', $validation);
    }

    public function isOk(): bool
    {
        return (bool) $this->validation->ok;
    }

    public function getHash(): ?string
    {
        return $this->signatureMeta['digest_value'] ?? null;
    }
}
