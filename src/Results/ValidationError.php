<?php

namespace ESolutions\XmlSimple\Results;

class ValidationError
{
    /** @var string */
    public $code;

    /** @var string */
    public $message;

    /** @var string|null */
    public $path;

    /** @var int|null */
    public $line;

    /** @var int|null */
    public $col;

    /** @var string|null */
    public $snippet;

    public function __construct(
        string $message,
        string $code = 'VALIDATION_ERROR',
        ?string $path = null,
        ?int $line = null,
        ?int $col = null,
        ?string $snippet = null
    ) {
        $this->message = $message;
        $this->code = $code;
        $this->path = $path;
        $this->line = $line;
        $this->col = $col;
        $this->snippet = $snippet;
    }

    public static function fromLibxml(\LibXMLError $e): self
    {
        $msg = trim((string) $e->message);
        return new self(
            $msg,
            'XSD',
            null,
            $e->line ?: null,
            $e->column ?: null
        );
    }
}
