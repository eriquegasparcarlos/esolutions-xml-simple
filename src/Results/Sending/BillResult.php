<?php

namespace ESolutions\XmlSimple\Results\Sending;

/**
 * Resultado de sendBill: respuesta síncrona con CDR incluido.
 */
class BillResult extends BaseResult
{
    /** XML del ApplicationResponse (CDR) ya extraído del ZIP, o null. */
    public function getCdrXml(): ?string
    {
        return $this->data['cdr'] ?? null;
    }

    public function hasCdr(): bool
    {
        return !empty($this->data['cdr']);
    }

    /** Notas cbc:Note del CDR (observaciones SUNAT). @return array<int, string>|null */
    public function getNotes(): ?array
    {
        $notes = $this->data['notes'] ?? null;

        return is_array($notes) ? $notes : ($notes !== null ? [(string) $notes] : null);
    }
}
