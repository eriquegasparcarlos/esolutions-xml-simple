<?php

namespace ESolutions\XmlSimple\Results\Sending;

/**
 * Resultado de getStatus (ticket de resumen/baja) o getStatusCdr (consulta
 * de CDR por RUC/tipo/serie/número). Puede traer CDR si ya fue procesado.
 * Código 98 = aún en proceso (reintentar luego).
 */
class StatusResult extends BillResult
{
    public function isInProcess(): bool
    {
        return $this->getCode() === '98' || $this->stateLabel() === 'en_proceso';
    }
}
