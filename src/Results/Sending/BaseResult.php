<?php

namespace ESolutions\XmlSimple\Results\Sending;

/**
 * Envuelve el array uniforme que producen los parsers CDR
 * (success/connection/sunat_success/state_label/message/code/...) con
 * accessors tipados. toArray() devuelve el array original intacto.
 */
class BaseResult
{
    public function __construct(protected array $data = [])
    {
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }

    /** Éxito técnico: la respuesta se recibió y se pudo interpretar. */
    public function isSuccess(): bool
    {
        return (bool) ($this->data['success'] ?? false);
    }

    /** Hubo comunicación con SUNAT/OSE (aunque respondiera Fault). */
    public function hasConnection(): bool
    {
        return (bool) ($this->data['connection'] ?? false);
    }

    /** Veredicto de negocio SUNAT: true aceptado/observado, false rechazado, null sin dictamen. */
    public function sunatSuccess(): ?bool
    {
        return $this->data['sunat_success'] ?? null;
    }

    /** aceptado | observado | rechazado | en_proceso | pendiente | indeterminado */
    public function stateLabel(): ?string
    {
        return $this->data['state_label'] ?? null;
    }

    public function isAccepted(): bool
    {
        return in_array($this->stateLabel(), ['aceptado', 'observado'], true);
    }

    public function isRejected(): bool
    {
        return $this->stateLabel() === 'rechazado';
    }

    public function isPending(): bool
    {
        return in_array($this->stateLabel(), ['en_proceso', 'pendiente'], true);
    }

    /**
     * Origen del error, derivado de las 3 banderas (success/connection/sunat_success).
     * Permite al consumidor decidir qué hacer sin combinar las banderas a mano:
     *   - 'conexion' → no se pudo comunicar con SUNAT/OSE (conviene REINTENTAR).
     *   - 'sunat'    → SUNAT/OSE respondió y RECHAZÓ/observó el documento
     *                  (corregir el comprobante; reintentar igual no ayuda).
     *   - 'sistema'  → hubo comunicación pero un fallo LOCAL (parseo del CDR o
     *                  estructura) — revisar el paquete/datos.
     *   - null       → sin error (aceptado, observado o en proceso).
     */
    public function errorSource(): ?string
    {
        if ($this->isAccepted() || $this->isPending()) {
            return null;
        }
        if (!$this->hasConnection()) {
            return 'conexion';
        }
        if ($this->isRejected() || $this->sunatSuccess() === false) {
            return 'sunat';
        }
        if (!$this->isSuccess()) {
            return 'sistema';
        }
        return null;
    }

    /**
     * ¿El comprobante llegó a SUNAT/OSE? Responde la pregunta clave del emisor:
     * si NO llegó (error de conexión) puede reintentar tal cual; si SÍ llegó,
     * el veredicto (aceptado/rechazado) ya es de SUNAT.
     *
     *   - false → error de conexión: no fue recibido.
     *   - true  → aceptado, observado, en proceso, rechazado por SUNAT, o fallo
     *             de parseo local (hubo comunicación).
     */
    public function reachedSunat(): bool
    {
        return $this->errorSource() !== 'conexion';
    }

    /**
     * Acción recomendada para el consumidor, derivada del origen del error:
     *   - 'retry'  → reintentar el envío (error de conexión).
     *   - 'fix'    → corregir el comprobante (SUNAT lo rechazó).
     *   - 'review' → revisar (hubo comunicación pero falló el parseo local).
     *   - null     → sin acción (aceptado / observado / en proceso).
     */
    public function recommendedAction(): ?string
    {
        return match ($this->errorSource()) {
            'conexion' => 'retry',
            'sunat'    => 'fix',
            'sistema'  => 'review',
            default    => null,
        };
    }

    public function getCode(): ?string
    {
        $code = $this->data['code'] ?? null;

        return $code === null ? null : (string) $code;
    }

    public function getMessage(): ?string
    {
        return $this->data['message'] ?? null;
    }

    /** @return array<int, string>|null */
    public function getErrors(): ?array
    {
        return $this->data['errors'] ?? null;
    }

    /** Segundos que tomó la llamada SOAP (si el sender lo midió). */
    public function getTime(): ?float
    {
        return isset($this->data['time']) ? (float) $this->data['time'] : null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
