<?php

namespace ESolutions\XmlSimple\Results;

class ValidationResult
{
    /** @var bool */
    public $ok;

    /** @var ValidationError[] */
    public $errors = [];

    public function __construct(bool $ok, array $errors = [])
    {
        $this->ok = $ok;
        $this->errors = $errors;
    }

    public static function ok(): self
    {
        return new self(true, []);
    }

    public static function fail(array $errors): self
    {
        return new self(false, $errors);
    }

    public function merge(self $other): self
    {
        return new self(
            $this->ok && $other->ok,
            array_merge($this->errors, $other->errors)
        );
    }

    public function firstMessage(): ?string
    {
        return isset($this->errors[0]) ? ($this->errors[0]->message ?? null) : null;
    }

    public function errorsByCode(): array
    {
        $out = [];
        foreach ($this->errors as $e) {
            $code = $e->code ?? 'VALIDATION_ERROR';
            $out[$code][] = $e;
        }
        return $out;
    }

    public function toArray(): array
    {
        return [
            'ok' => (bool) $this->ok,
            'errors' => array_map(function ($e) {
                return [
                    'code' => $e->code ?? 'VALIDATION_ERROR',
                    'message' => $e->message ?? '',
                    'path' => $e->path ?? null,
                    'line' => $e->line ?? null,
                    'col' => $e->col ?? null,
                    'snippet' => $e->snippet ?? null,
                ];
            }, $this->errors),
        ];
    }
}
