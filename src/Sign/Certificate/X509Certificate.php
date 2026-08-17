<?php

namespace ESolutions\XmlSimple\Sign\Certificate;

use DateTime;
use Exception;
use InvalidArgumentException;

class X509Certificate
{
    private ?string $pfx = null;
    private ?string $password = null;
    private array $certs = [];
    private ?array $subject = null;

    /**
     * @param string|null $pfx
     * @param string|null $password
     * @throws Exception
     */
    public function __construct(?string $pfx = null, ?string $password = null)
    {
        if ($pfx !== null && $password !== null) {
            $this->pfx = $pfx;
            $this->password = $password;
            $this->parsePfx($pfx, $password);
        }
    }

    /**
     * Crea una instancia desde un archivo PFX.
     */
    public static function createFromFile(string $filename, string $password): self
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('Certificate file not found: ' . $filename);
        }
        $content = file_get_contents($filename);

        return new self($content, $password);
    }

    public function getName(): ?string
    {
        return $this->getSubjectValue('name');
    }

    public function getSubject(): ?array
    {
        return $this->subject ?? null;
    }

    public function getIssuer(): ?array
    {
        return $this->getSubjectValue('issuer');
    }

    public function getValidFrom(): ?DateTime
    {
        $value = $this->getSubjectValue('validFrom_time_t');
        return $value ? (new DateTime())->setTimestamp($value) : null;
    }

    public function getExpiration(): ?DateTime
    {
        $value = $this->getSubjectValue('validTo_time_t');
        return $value ? (new DateTime())->setTimestamp($value) : null;
    }

    public function getPurposes(): ?array
    {
        return $this->getSubjectValue('purposes');
    }

    public function getExtensions(): ?array
    {
        return $this->getSubjectValue('extensions');
    }

    public function getPublicKey(): ?string
    {
        return $this->certs['cert'] ?? null;
    }

    public function getPrivateKey(): ?string
    {
        return $this->certs['pkey'] ?? null;
    }

    public function getRaw(): ?string
    {
        return $this->pfx;
    }

    /**
     * Exporta el certificado según el tipo (PEM, CER, etc.)
     */
    public function export(int $type): ?string
    {
        return match ($type) {
            X509ContentType::PEM => $this->getPublicKey() . $this->getPrivateKey(),
            X509ContentType::CER => $this->getPublicKey(),
            default => null,
        };
    }

    /**
     * Parsea el PFX y extrae los certificados.
     * @throws Exception
     */
    private function parsePfx(string $pfx, string $password): void
    {
        $certs = [];
        $result = openssl_pkcs12_read($pfx, $certs, $password);

        if ($result === false) {
            throw new Exception(openssl_error_string() ?: 'Error parsing PFX.');
        }

        $this->certs = $certs;
        $this->subject = null; // Reset, será lazy-loaded
    }

    /**
     * Carga un certificado PEM.
     */
    public function loadPem(string $pem): void
    {
        $this->certs['cert'] = $pem;
        $this->subject = null; // Reset
    }

    /**
     * Retorna todos los certificados.
     */
    public function getCerts(): array
    {
        return $this->certs;
    }

    /**
     * Lazy-load del subject del certificado.
     */
    private function loadSubject(): void
    {
        if ($this->subject !== null) {
            return;
        }

        $publicKey = $this->getPublicKey();
        $this->subject = $publicKey ? openssl_x509_parse($publicKey) ?: [] : [];
    }

    /**
     * Devuelve el valor de una clave específica del subject.
     */
    private function getSubjectValue(string $key): mixed
    {
        $this->loadSubject();
        return $this->subject[$key] ?? null;
    }
}
