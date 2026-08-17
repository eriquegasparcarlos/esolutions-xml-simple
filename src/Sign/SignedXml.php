<?php

namespace ESolutions\XmlSimple\Sign;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;
use UnexpectedValueException;

/**
 * Class SignedXml
 */
class SignedXml
{
    protected string $signatureId = '';

    /** Transform */
    public const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    public const EXT_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';

    protected ?string $privateKey = null;
    protected ?string $publicKey = null;

    protected string $keyAlgorithm = XMLSecurityKey::RSA_SHA1;
    protected string $digestAlgorithm = XMLSecurityDSig::SHA1;
    protected string $canonicalMethod = XMLSecurityDSig::C14N;

    /**
     * Firma el contenido del xml y retorna el contenido firmado.
     *
     * @param array $meta  Metadata desde payload.signature, por ejemplo:
     *  - signatureId (o signature_id)
     *  - note (o facturerId)
     *  - signatureUri (o uri)  => si viene "#SIGN", se usa SIGN como id
     */
    public function signXml(string $content, array $meta = []): string
    {
        $doc = $this->getDocXml($content);

        // ✅ Actualiza metadata UBL usando payload (no config)
        $this->applyUblSignatureMetadata($doc, $meta);

        // Firma real XMLDSig dentro de ext:ExtensionContent
        $this->sign($doc);

        return $doc->saveXML();
    }

    /**
     * Verifica la firma del xml.
     */
    public function verifyXml(string $content): bool
    {
        $doc = $this->getDocXml($content);
        $this->getPublicKey($doc);

        return $this->verify($doc);
    }

    /**
     * Elimina el contenido firmado del XML.
     */
    public function removeSignedXML(string $content): string
    {
        $doc = $this->getDocXml($content);
        $extNodes = $doc->getElementsByTagNameNS(self::EXT_NS, 'ExtensionContent');

        if ($extNodes->length > 0) {
            /** @var DOMElement $node */
            $node = $extNodes->item(0);
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }
        }

        return $doc->saveXML();
    }

    /**
     * Set certificate in PEM format (debe incluir PRIVATE KEY para firmar).
     */
    public function setCertificate(string $cert): void
    {
        $this->privateKey = $cert;
        $this->publicKey = $cert;
    }

    /**
     * Set certificate from file.
     */
    public function setCertificateFromFile(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('Certificate File not found: ' . $filename);
        }

        $this->setCertificate(file_get_contents($filename));
    }

    /**
     * Retorna la clave pública, extrayéndola del documento si es necesario.
     */
    public function getPublicKey(?DOMDocument $doc = null): ?string
    {
        if ($doc) {
            $this->setPublicKeyFromNode($doc);
        }
        return $this->publicKey;
    }

    /**
     * Firma un documento DOM.
     */
    public function sign(DOMDocument $data): void
    {
        if (empty($this->privateKey)) {
            throw new RuntimeException('Missing private key. Use setCertificate to set one (PEM with private key).');
        }

        $objKey = new XMLSecurityKey(
            $this->keyAlgorithm,
            ['type' => 'private']
        );
        $objKey->loadKey($this->privateKey);

        $objXMLSecDSig = $this->createXmlSecurityDSig();
        $objXMLSecDSig->setCanonicalMethod($this->canonicalMethod);
        $objXMLSecDSig->addReference($data, $this->digestAlgorithm, [self::ENVELOPED], ['force_uri' => true]);

        // Inserta la firma dentro del nodo correcto
        $objXMLSecDSig->sign($objKey, $this->getNodeSign($data));

        // Agrega la clave pública asociada si existe
        if ($this->getPublicKey()) {
            $objXMLSecDSig->add509Cert($this->getPublicKey());
        }
    }

    /**
     * Firma el contenido de un archivo.
     */
    public function signFromFile(string $filename, array $meta = []): string
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('File to sign not found: ' . $filename);
        }

        return $this->signXml(file_get_contents($filename), $meta);
    }

    /**
     * Verifica la firma de un documento DOM.
     */
    public function verify(DOMDocument $data): bool
    {
        $objXMLSecDSig = $this->createXmlSecurityDSig();
        $objDSig = $objXMLSecDSig->locateSignature($data);
        if (!$objDSig) {
            throw new UnexpectedValueException('Signature DOM element not found.');
        }
        $objXMLSecDSig->canonicalizeSignedInfo();

        $objKey = null;
        if (!$this->getPublicKey()) {
            // intenta obtener la clave pública del certificado
            $objKey = $objXMLSecDSig->locateKey();
            if (!$objKey) {
                throw new RuntimeException('No se encuentra la clave pública o privada para la verificación.');
            }
            XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);
            $this->publicKey = $objKey->getX509Certificate();
            $this->keyAlgorithm = $objKey->getAlgorithm();
        }

        if (!$objKey) {
            $objKey = new XMLSecurityKey(
                $this->keyAlgorithm,
                ['type' => 'public']
            );
            $objKey->loadKey($this->getPublicKey());
        }

        // Verifica la firma
        if (1 !== $objXMLSecDSig->verify($objKey)) {
            return false;
        }

        // Verifica las referencias (datos)
        try {
            $objXMLSecDSig->validateReference();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Crea la instancia XMLSecurityDSig.
     */
    protected function createXmlSecurityDSig(): XMLSecurityDSig
    {
        $xmlSecurityDSig = new XMLSecurityDSig();

        // Evitar acceder a sigNode si aún no existe
        if ($this->signatureId && $xmlSecurityDSig->sigNode instanceof DOMElement) {
            $xmlSecurityDSig->sigNode->setAttribute('Id', $this->signatureId);
        }

        return $xmlSecurityDSig;
    }

    /**
     * Intenta extraer la clave pública del nodo DOM.
     *
     * @return bool true Si se extrajo correctamente, false en caso contrario
     * @throws Exception
     */
    protected function setPublicKeyFromNode(DOMDocument $doc): bool
    {
        $objXMLSecDSig = $this->createXmlSecurityDSig();
        $objDSig = $objXMLSecDSig->locateSignature($doc);
        if (!$objDSig) {
            return false;
        }

        $objKey = $objXMLSecDSig->locateKey();
        if (!$objKey) {
            return false;
        }

        XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);
        $this->publicKey = $objKey->getX509Certificate();
        $this->keyAlgorithm = $objKey->getAlgorithm();

        return true;
    }

    /**
     * Busca el nodo donde colocar la firma:
     * - primer ext:ExtensionContent
     * - limpia cualquier contenido (stub/whitespace) antes de insertar ds:Signature
     */
    private function getNodeSign(DOMDocument $data): DOMElement
    {
        $els = $data->getElementsByTagNameNS(self::EXT_NS, 'ExtensionContent');

        if ($els->length > 0) {
            /** @var DOMElement $el */
            $el = $els->item(0);

            while ($el->firstChild) {
                $el->removeChild($el->firstChild);
            }

            return $el;
        }

        return $data->documentElement;
    }

    /**
     * Actualiza metadata UBL usando payload.
     * SOLO toca el primer cac:Signature.
     */
    private function applyUblSignatureMetadata(DOMDocument $doc, array $meta): void
    {
        $nsCac = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
        $nsCbc = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cac', $nsCac);
        $xpath->registerNamespace('cbc', $nsCbc);

        // Resolver signatureId:
        // - signatureId / signature_id
        // - si viene signatureUri "#SIGN" => "SIGN"
        $signatureId = (string)($meta['signatureId'] ?? $meta['signature_id'] ?? '');
        $signatureUri = (string)($meta['signatureUri'] ?? $meta['uri'] ?? '');

        if ($signatureId === '' && $signatureUri !== '') {
            $signatureId = ltrim($signatureUri, '#');
        }
        $note = $signatureId;
//        if ($signatureId === '') {
//            // fallback razonable
//            $signatureId = 'SIGN';
//        }

        // Resolver Note:
        // - note
        // - facturerId (como lo tienes en tu payload)
//        $note = (string)($meta['note'] ?? $meta['facturerId'] ?? '');
//        if ($note === '') {
//            $note = $signatureId;
//        }

        // Buscar primer cac:Signature
//        $sigNode = $xpath->query('//cac:Signature[1]')->item(0);
//        if (!$sigNode instanceof DOMElement) {
//            // si no existe, no hacemos nada
//            $this->signatureId = $signatureId;
//            return;
//        }
//
//        // cbc:ID directo bajo cac:Signature
//        $idNode = $xpath->query('cbc:ID[1]', $sigNode)->item(0);
//        if ($idNode) {
//            $idNode->nodeValue = $signatureId;
//        }
//
//        // cbc:Note directo bajo cac:Signature
//        $noteNode = $xpath->query('cbc:Note[1]', $sigNode)->item(0);
//        if ($noteNode) {
//            $noteNode->nodeValue = $note;
//        }

        // URI: cac:DigitalSignatureAttachment/cac:ExternalReference/cbc:URI
//        $uriNode = $xpath->query('cac:DigitalSignatureAttachment/cac:ExternalReference/cbc:URI[1]', $sigNode)->item(0);
//        if ($uriNode) {
//            $uriNode->nodeValue = '#' . $signatureId;
//        }

        $this->signatureId = $signatureUri;
    }

    /**
     * Devuelve un DOMDocument del contenido XML.
     */
    private function getDocXml(string $content): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->loadXML($content);

        return $doc;
    }
}
