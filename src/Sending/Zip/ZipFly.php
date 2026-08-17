<?php

namespace ESolutions\XmlSimple\Sending\Zip;

use ESolutions\XmlSimple\Contracts\ZipCompressorInterface;

/**
 * Construye el ZIP 100% en memoria (local file header + central directory +
 * EOCD armados con pack/gzcompress), sin archivos temporales — ideal para el
 * caso SUNAT de "un solo XML por zip". Portado del Facturalo legacy.
 */
class ZipFly implements ZipCompressorInterface
{
    /** @var array<int, string> Entradas comprimidas */
    private array $datasec = [];

    /** @var array<int, string> Central directory */
    private array $ctrlDir = [];

    /** End of central directory record */
    private string $eofCtrlDir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    private int $oldOffset = 0;

    public function compress(string $filename, string $content): string
    {
        $this->addFile($content, $filename);
        $zip = $this->file();
        $this->clear();

        return $zip;
    }

    /**
     * Convierte un timestamp Unix al formato de fecha/hora DOS de 4 bytes
     * (fecha en los 2 bytes altos, hora en los bajos).
     */
    private function unix2DosTime(int $unixtime = 0): int
    {
        $t = (0 === $unixtime) ? getdate() : getdate($unixtime);
        if ($t['year'] < 1980) {
            $t = ['year' => 1980, 'mon' => 1, 'mday' => 1, 'hours' => 0, 'minutes' => 0, 'seconds' => 0];
        }

        return (($t['year'] - 1980) << 25)
            | ($t['mon'] << 21)
            | ($t['mday'] << 16)
            | ($t['hours'] << 11)
            | ($t['minutes'] << 5)
            | ($t['seconds'] >> 1);
    }

    private function addFile(string $data, string $name, int $time = 0): void
    {
        $name = str_replace('\\', '/', $name);
        $hexdtime = pack('V', $this->unix2DosTime($time));

        // "local file header" segment
        $frd = "\x50\x4b\x03\x04";
        $frd .= "\x14\x00";            // ver needed to extract
        $frd .= "\x00\x00";            // gen purpose bit flag
        $frd .= "\x08\x00";            // compression method (deflate)
        $frd .= $hexdtime;             // last mod time and date

        [$zdata, $part] = $this->getPartsFromData($data, $name);

        $frd .= $part . $name;
        $frd .= $zdata;                // "file data" segment
        $this->datasec[] = $frd;

        // central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                   // version made by
        $cdrec .= "\x14\x00";                   // version needed to extract
        $cdrec .= "\x00\x00";                   // gen purpose bit flag
        $cdrec .= "\x08\x00";                   // compression method
        $cdrec .= $hexdtime;                    // last mod time & date
        $cdrec .= $part;
        $cdrec .= str_repeat(pack('v', 0), 3);  // comment len, disk start, internal attrs
        $cdrec .= pack('V', 32);                // external file attributes ('archive' bit)
        $cdrec .= pack('V', $this->oldOffset);  // relative offset of local header
        $this->oldOffset += strlen($frd);
        $cdrec .= $name;

        $this->ctrlDir[] = $cdrec;
    }

    /**
     * @return array{0: string, 1: string} [datos deflate crudos, cabecera crc/tamaños]
     */
    private function getPartsFromData(string $data, string $name): array
    {
        // gzcompress produce zlib: se recortan los 2 bytes de header y los
        // 4 del checksum final para dejar solo el stream DEFLATE crudo.
        $zdata = gzcompress($data);
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);

        $frd = pack('V', crc32($data));         // crc32
        $frd .= pack('V', strlen($zdata));      // compressed filesize
        $frd .= pack('V', strlen($data));       // uncompressed filesize
        $frd .= pack('v', strlen($name));       // length of filename
        $frd .= pack('v', 0);                   // extra field length

        return [$zdata, $frd];
    }

    private function file(): string
    {
        $ctrldir = implode('', $this->ctrlDir);
        $header = $ctrldir
            . $this->eofCtrlDir
            . pack('v', count($this->ctrlDir))  // total entries "on this disk"
            . pack('v', count($this->ctrlDir))  // total entries overall
            . pack('V', strlen($ctrldir))       // size of central dir
            . pack('V', $this->oldOffset)       // offset to start of central dir
            . "\x00\x00";                       // .zip file comment length

        return implode('', $this->datasec) . $header;
    }

    private function clear(): void
    {
        $this->datasec = [];
        $this->ctrlDir = [];
        $this->oldOffset = 0;
    }
}
