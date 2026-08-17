<?php

namespace ESolutions\XmlSimple\Contracts;

interface ZipCompressorInterface
{
    /**
     * Comprime $content como un archivo llamado $filename dentro de un ZIP
     * (plano, sin carpetas) y retorna los bytes del ZIP resultante.
     * Es el formato que exige SUNAT para el contentFile de sendBill/sendSummary.
     */
    public function compress(string $filename, string $content): string;
}
