<?php

namespace ESolutions\XmlSimple\Sending\Zip;

use Exception;
use ZipArchive;

class ZipExtractor
{
    /**
     * Extrae el contenido XML desde un ZIP.
     *
     * @param string $zip El archivo ZIP.
     * @return string El contenido del archivo XML extraído.
     * @throws Exception Si el archivo ZIP no se puede procesar o no contiene XML.
     */
    public function extractXmlFromZip(string $zip): string
    {
        // Crea un archivo temporal para guardar el ZIP
        $tempZipPath = tempnam(sys_get_temp_dir(), 'cdr_');
        file_put_contents($tempZipPath, $zip);

        // Abre el archivo ZIP
        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) !== true) {
            throw new Exception('No se pudo abrir el archivo ZIP.');
        }

        // Busca el primer archivo con extensión .xml dentro del ZIP
        $xmlContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            // Solo extrae el primer archivo XML encontrado
            if (str_ends_with(strtolower($entry), '.xml')) {
                $xmlContent = $zip->getFromIndex($i);
                break;
            }
        }

        // Cierra y elimina el archivo temporal
        $zip->close();
        unlink($tempZipPath);

        if (!$xmlContent) {
            throw new Exception('No se encontró ningún archivo XML dentro del ZIP.');
        }

        return $xmlContent;
    }
}

