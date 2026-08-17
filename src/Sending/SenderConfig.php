<?php

namespace ESolutions\XmlSimple\Sending;

/**
 * Configuración de envío independiente del proyecto consumidor.
 * En apps multi-tenant se arma una instancia por empresa/tenant
 * (credenciales SOL propias) con fromArray(); fromConfig() lee los
 * defaults globales de config('esolutions_xml_simple.sending').
 */
final class SenderConfig
{
    public function __construct(
        public readonly string $provider = 'sunat',       // 'sunat' | 'nubefact'
        public readonly string $environment = 'demo',     // 'demo' | 'production'
        public readonly string $username = '',
        public readonly string $password = '',
        public readonly ?string $endpoint = null,         // override total (URL de OSE/PSE); null => endpoints SUNAT por defecto
        public readonly ?string $documentTypeId = null,   // '20'/'40'/'RR' usan el endpoint de retenciones
        public readonly ?string $greClientId = null,      // GRE REST (guías 09/31): client_id OAuth2 emitido en SOL
        public readonly ?string $greClientSecret = null,  // GRE REST: client_secret OAuth2
    ) {
        if (!in_array($this->environment, ['demo', 'production'], true)) {
            throw new \InvalidArgumentException("environment debe ser 'demo' o 'production', se recibió '{$this->environment}'.");
        }
    }

    /**
     * @param array{provider?: string, environment?: string, username?: string,
     *               password?: string, endpoint?: string|null, document_type_id?: string|null,
     *               gre_client_id?: string|null, gre_client_secret?: string|null} $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            strtolower((string) ($config['provider'] ?? 'sunat')),
            strtolower((string) ($config['environment'] ?? 'demo')),
            (string) ($config['username'] ?? ''),
            (string) ($config['password'] ?? ''),
            $config['endpoint'] ?? null,
            $config['document_type_id'] ?? null,
            $config['gre_client_id'] ?? null,
            $config['gre_client_secret'] ?? null,
        );
    }

    /**
     * Defaults globales desde config('esolutions_xml_simple.sending').
     */
    public static function fromConfig(array $overrides = []): self
    {
        return self::fromArray(array_merge((array) config('esolutions_xml_simple.sending', []), $overrides));
    }

    public function isDemo(): bool
    {
        return $this->environment === 'demo';
    }

    public function withDocumentType(?string $documentTypeId): self
    {
        return new self(
            $this->provider,
            $this->environment,
            $this->username,
            $this->password,
            $this->endpoint,
            $documentTypeId,
            $this->greClientId,
            $this->greClientSecret,
        );
    }
}
