<?php

namespace ESolutions\XmlSimple\Sending;

/**
 * Nomenclatura de archivo que exige SUNAT (sin extensión — el sender
 * agrega .xml/.zip según el contexto).
 */
class FilenameBuilder
{
    /**
     * Comprobantes: RUC-TIPO-SERIE-NUMERO (p.ej. 20123456789-01-F001-123).
     */
    public static function forDocument(string $ruc, string $documentTypeId, string $series, string|int $number): string
    {
        return implode('-', [$ruc, $documentTypeId, strtoupper($series), $number]);
    }

    /**
     * Resúmenes y bajas: RUC-RC-YYYYMMDD-### / RUC-RA-YYYYMMDD-###.
     *
     * @param string $type 'RC' (resumen diario) | 'RA' (comunicación de baja) | 'RR' (reversión)
     * @param \DateTimeInterface|string $date fecha de generación
     */
    public static function forSummary(string $ruc, string $type, \DateTimeInterface|string $date, string|int $correlative): string
    {
        $ymd = $date instanceof \DateTimeInterface ? $date->format('Ymd') : date('Ymd', strtotime($date));

        return implode('-', [$ruc, strtoupper($type), $ymd, $correlative]);
    }
}
