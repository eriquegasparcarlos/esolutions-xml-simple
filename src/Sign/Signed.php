<?php

namespace ESolutions\XmlSimple\Sign;

use ESolutions\XmlSimple\Sign\Certificate\X509Certificate;
use ESolutions\XmlSimple\Sign\Certificate\X509ContentType;
use ESolutions\XmlSimple\Sign\Hash\XmlHash;
use ESolutions\XmlSimple\Sign\SignedXml;
use InvalidArgumentException;

class Signed
{
    private SignedXml $signer;
    private ?string $lastXml = null;

    /** defaults */
    private ?string $defaultCertificateFile;
    private ?string $defaultCertificatePassword;

    /** info */
    private ?array $lastCertificateInfo = null;
    private ?array $lastSignatureMeta = null;

    public function __construct(?string $defaultCertificateFile = null, ?string $defaultCertificatePassword = null)
    {
        $this->signer = new SignedXml();
        // El .pem demo se resuelve relativo al paquete (src/Resources/certs), nunca
        // contra la app consumidora — app_path() apuntaría al proyecto equivocado.
        $this->defaultCertificateFile = $defaultCertificateFile ?: __DIR__ . '/../Resources/certs/certificate.pem';
        $this->defaultCertificatePassword = $defaultCertificatePassword;
    }

    /**
     * Firma un XML usando:
     * - $certificateFile (pem/pfx/p12) si llega
     * - caso contrario usa el demo: app/ESolutions/Xml/Sign/Resources/certificate.pem
     *
     * Además recibe metadata de firma (desde payload.signature) para actualizar:
     * - cac:Signature/cbc:ID
     * - cac:Signature/cbc:Note
     * - cac:Signature/.../cbc:URI
     *
     * @param array|null $signatureMeta
     *   Ej:
     *   [
     *     'signatureId' => 'SIGN',      // opcional
     *     'signatureUri' => '#SIGN',    // opcional
     *     'note' => '... ',             // opcional
     *     'facturerId' => '... ',       // opcional (fallback para note)
     *   ]
     *
     * @throws InvalidArgumentException
     */
    public function xmlSigned(
        string $xml,
        ?string $certificateFile = null,
        ?string $certificatePassword = null,
        ?array $signatureMeta = null
    ): string {
        [$certPem, $info] = $this->loadCertificateAsPemAndInfo($certificateFile, $certificatePassword);

        // Alimenta el signer con PEM (debe contener PRIVATE KEY para firmar)
        $this->signer->setCertificate($certPem);

        $this->lastCertificateInfo = $info;

        // Pasar metadata del payload al signer
        $meta = is_array($signatureMeta) ? $signatureMeta : [];

        $this->lastXml = $this->signer->signXml($xml, $meta);

        // extraer hash / signature value
        $this->lastSignatureMeta = XmlHash::extract($this->lastXml);

        return $this->lastXml;
    }

    public function getLastXml(): ?string
    {
        return $this->lastXml;
    }

    public function getLastCertificateInfo(): ?array
    {
        return $this->lastCertificateInfo;
    }

    public function getLastSignatureMeta(): ?array
    {
        return $this->lastSignatureMeta;
    }

    /**
     * Carga certificado desde archivo y lo convierte a PEM “utilizable para firmar”.
     * Soporta:
     * - .pem (ideal: cert + private key)
     * - .pfx / .p12 (requiere password)
     *
     * @return array{0:string,1:array} [pem, info]
     */
    private function loadCertificateAsPemAndInfo(?string $certificateFile, ?string $certificatePassword): array
    {
        $file = $certificateFile ?: $this->defaultCertificateFile;

        if (!$file || !is_file($file)) {
            throw new InvalidArgumentException('Certificate file not found: ' . ($file ?: '(null)'));
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $password = $certificatePassword ?? $this->defaultCertificatePassword;

        // --- PFX/P12 ---
        if (in_array($ext, ['pfx', 'p12'], true)) {
            if ($password === null || $password === '') {
                throw new InvalidArgumentException("Certificate password required for .$ext file.");
            }

            $x509 = X509Certificate::createFromFile($file, $password);

            // export PEM (cert + private key)
            $pem = (string) $x509->export(X509ContentType::PEM);

            $info = $this->extractCertificateInfoFromPem($x509->getPublicKey() ?: $pem, $file);
            $info['source_type'] = $ext;
            $info['has_private_key'] = !empty($x509->getPrivateKey());

            return [$pem, $info];
        }

        // --- PEM ---
        $pem = (string) file_get_contents($file);

        // Validación mínima: debe tener PRIVATE KEY para firmar
        $hasPrivateKey = (strpos($pem, 'BEGIN PRIVATE KEY') !== false) || (strpos($pem, 'BEGIN RSA PRIVATE KEY') !== false);

        $info = $this->extractCertificateInfoFromPem($pem, $file);
        $info['source_type'] = 'pem';
        $info['has_private_key'] = $hasPrivateKey;

        if (!$hasPrivateKey) {
            // Ojo: la firma requiere clave privada
            throw new InvalidArgumentException(
                "PEM does not include a PRIVATE KEY. Use a PEM with private key, or a PFX/P12 with password. File: {$file}"
            );
        }

        return [$pem, $info];
    }

    /**
     * Extrae info del certificado (subject/issuer/fechas/serial/fingerprints).
     * Recibe PEM que incluya certificado (aunque tenga private key también).
     */
    private function extractCertificateInfoFromPem(string $pem, string $path): array
    {
        // Extraer el bloque CERTIFICATE (si el PEM tiene private key + cert)
        $certBlock = $this->extractPemCertificateBlock($pem) ?: $pem;

        $parsed = @openssl_x509_parse($certBlock) ?: [];

        $sha1 = null;
        $sha256 = null;

        if (function_exists('openssl_x509_fingerprint')) {
            $sha1 = @openssl_x509_fingerprint($certBlock, 'sha1', false) ?: null;
            $sha256 = @openssl_x509_fingerprint($certBlock, 'sha256', false) ?: null;
        }

        $validFrom = isset($parsed['validFrom_time_t']) ? (int)$parsed['validFrom_time_t'] : null;
        $validTo = isset($parsed['validTo_time_t']) ? (int)$parsed['validTo_time_t'] : null;

        $now = time();
        $isExpired = ($validTo !== null) ? ($now > $validTo) : null;
        $daysLeft = ($validTo !== null) ? (int) floor(($validTo - $now) / 86400) : null;

        // Extra opcional útil
        $keyUsage = $parsed['extensions']['keyUsage'] ?? null;
        $eku = $parsed['extensions']['extendedKeyUsage'] ?? null;

        return [
            'path' => $path,

            'subject' => $parsed['subject'] ?? null,
            'issuer' => $parsed['issuer'] ?? null,

            'common_name' => $parsed['subject']['CN'] ?? null,
            'organization' => $parsed['subject']['O'] ?? null,

            'serial_number' => $parsed['serialNumber'] ?? null,
            'serial_number_hex' => $parsed['serialNumberHex'] ?? null,

            'valid_from_time_t' => $validFrom,
            'valid_to_time_t' => $validTo,
            'is_expired' => $isExpired,
            'days_left' => $daysLeft,

            'fingerprint_sha1' => $sha1,
            'fingerprint_sha256' => $sha256,

            // extra
            'key_usage' => $keyUsage,
            'extended_key_usage' => $eku,
        ];
    }

    private function extractPemCertificateBlock(string $pem): ?string
    {
        if (preg_match('/-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----/s', $pem, $m)) {
            return "-----BEGIN CERTIFICATE-----" . $m[1] . "-----END CERTIFICATE-----";
        }
        return null;
    }
}
