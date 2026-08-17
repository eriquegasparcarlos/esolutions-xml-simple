<?php

namespace ESolutions\XmlSimple\Results\Sending;

/**
 * Resultado de sendSummary: respuesta asíncrona — SUNAT entrega un ticket
 * que debe consultarse luego con getStatus (TicketService).
 */
class SummaryResult extends BaseResult
{
    public function getTicket(): ?string
    {
        return $this->data['ticket'] ?? null;
    }

    public function hasTicket(): bool
    {
        return !empty($this->data['ticket']);
    }
}
