<?php

namespace ESolutions\XmlSimple\Sending\Cdr;

use ESolutions\XmlSimple\Sending\Zip\ZipExtractor;
use ESolutions\XmlSimple\Support\XmlHelper;
use ESolutions\XmlSimple\Contracts\CdrResponseParserInterface;
use ESolutions\XmlSimple\Contracts\ErrorCodeCatalogInterface;
use ESolutions\XmlSimple\Sending\Catalog\FileErrorCodeCatalog;
use DOMXPath;

class SunatCdrParser implements CdrResponseParserInterface
{
    protected ErrorCodeCatalogInterface $catalog;

    public function __construct(?ErrorCodeCatalogInterface $catalog = null)
    {
        $this->catalog = $catalog ?? new FileErrorCodeCatalog();
    }

    /**
     * Juzga el código SUNAT y devuelve:
     * - sunat_success: true/false/null (veredicto)
     * - state_label: aceptado | observado | rechazado | en_proceso | indeterminado
     * - normalized_code: string|null
     * $isTicket = true cuando proviene de getStatus de resumen/GRE (0/98/99)
     */
    private function judgeCode($code, bool $isTicket = false): array
    {
        if ($code === null || $code === '') {
            return ['sunat_success' => null, 'state_label' => 'indeterminado', 'normalized_code' => null];
        }

        $codeStr = (string)$code;

        if ($isTicket) {
            if ($codeStr === '0') return ['sunat_success' => true, 'state_label' => 'aceptado', 'normalized_code' => '0'];
            if ($codeStr === '98') return ['sunat_success' => null, 'state_label' => 'en_proceso', 'normalized_code' => '98'];
            if ($codeStr === '99') return ['sunat_success' => false, 'state_label' => 'rechazado', 'normalized_code' => '99'];
            return ['sunat_success' => null, 'state_label' => 'indeterminado', 'normalized_code' => $codeStr];
        }

        if ($codeStr === '0') {
            return ['sunat_success' => true, 'state_label' => 'aceptado', 'normalized_code' => '0'];
        }

        // Normaliza a entero si aplica
        $n = ctype_digit($codeStr) ? (int)$codeStr : null;
        if ($n !== null) {
            if ($n >= 4000) return ['sunat_success' => true, 'state_label' => 'observado', 'normalized_code' => $codeStr];
            if ($n >= 2000 && $n <= 3999) return ['sunat_success' => false, 'state_label' => 'rechazado', 'normalized_code' => $codeStr];
           // if ($n >= 1000 && $n <= 1999) return ['sunat_success' => null, 'state_label' => 'rechazado', 'normalized_code' => $codeStr];
            if ($n >= 100 && $n <= 1999) return ['sunat_success' => null, 'state_label' => 'indeterminado', 'normalized_code' => $codeStr];
        }

        return ['sunat_success' => null, 'state_label' => 'indeterminado', 'normalized_code' => $codeStr];
    }

    private function baseResponse(): array
    {
        return [
            'success' => true,
            'connection' => true,
            'sunat_success' => null,
            'message' => null,
            'code' => null,
            'ticket' => null,
            'cdr' => null,      // XML del CDR (no base64)
            'notes' => null,      // array|string|null
            'errors' => null,      // array|null
            'state_label' => null,      // aceptado/observado/rechazado/en_proceso/indeterminado
        ];
    }

    public function parseBill(array $result): array
    {

        $raw = $result['raw'] ?? '';
        if (empty($raw)) {
            $res = $this->baseResponse();
            $res['success'] = false;
            $res['connection'] = false;
            $res['sunat_success'] = null;
            $res['message'] = 'No se recibió respuesta válida de SUNAT.';
            $res['errors'] = ['No se recibió respuesta SOAP'];
            return $res;
        }

        try {
            $dom = XmlHelper::loadDom($raw);
            $xpath = new DOMXPath($dom);

            // ¿Hay CDR (applicationResponse)?
            $cdrBase64 = XmlHelper::getNodeValue($xpath, "//*[local-name()='applicationResponse']");
            if ($cdrBase64 && base64_decode($cdrBase64, true)) {
                $cdrZip = base64_decode($cdrBase64);
                $xmlCdr = (new ZipExtractor())->extractXmlFromZip($cdrZip);

                $domCdr = XmlHelper::loadDom($xmlCdr);
                $xpCdr = new DOMXPath($domCdr);
                $xpCdr->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                $respCode = XmlHelper::getNodeValue($xpCdr, '//cbc:ResponseCode') ?? 'UNKNOWN';
                $rawMsg = XmlHelper::getNodeValue($xpCdr, '//cbc:Description') ?? 'Sin descripción.';
                $desc = $this->catalog->describe($respCode) ?: $rawMsg;
                $notes = XmlHelper::getNodeValues($xpCdr, '//cbc:Note');

                $judge = $this->judgeCode($respCode, false);

                $res = $this->baseResponse();
                $res['success'] = true;            // hubo comunicación + parseo OK
                $res['connection'] = true;
                $res['sunat_success'] = $judge['sunat_success'];
                $res['state_label'] = $judge['state_label'];
                $res['message'] = $desc;
                $res['code'] = $judge['normalized_code'];
                $res['cdr'] = $xmlCdr;
                $res['notes'] = $notes ?: null;
                return $res;
            }

            // Si no hubo CDR, revisar si hay Fault
            $faultCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultcode']");
            $faultStr = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultstring']");

            if ($faultCode || $faultStr) {
                $numeric = null;
                if ($faultCode && preg_match('/(\d{3,4})$/', $faultCode, $m)) {
                    $numeric = $m[1];
                }
                $desc = $numeric ? ($this->catalog->describe($numeric) ?: $faultStr) : ($faultStr ?: $faultCode);

                $judge = $this->judgeCode($numeric ?? $faultCode, false);

                // Hubo comunicación con SUNAT (Fault) → connection=true
                $res = $this->baseResponse();
                $res['success'] = true;              // técnico OK (hubo comunicación)
                $res['connection'] = true;
                $res['sunat_success'] = $judge['sunat_success'] ?? false;
                $res['state_label'] = $judge['state_label'];
                $res['message'] = $desc ?? 'Error en envío.';
                $res['code'] = $judge['normalized_code'];
                $res['errors'] = array_filter([$faultCode, $faultStr]);
                return $res;
            }

            // Respuesta rara: hubo raw (comunicación), pero ni CDR ni Fault
            $res = $this->baseResponse();
            $res['success'] = true;
            $res['connection'] = true;
            $res['sunat_success'] = null; // hubo comunicación pero no hay veredicto
            $res['state_label'] = 'indeterminado';
            $res['message'] = 'Respuesta de SUNAT sin CDR ni Fault.';
            return $res;

        } catch (\Throwable $e) {
            $res = $this->baseResponse();
            $res['success'] = true;  // hubo comunicación; el error es local (parseo)
            $res['connection'] = true;
            $res['sunat_success'] = null;
            $res['state_label'] = 'indeterminado';
            $res['message'] = 'Error al procesar respuesta: ' . $e->getMessage();
            $res['code'] = 'PARSE_ERROR';
            $res['errors'] = [$e->getMessage()];
            return $res;
        }
    }

    public function parseSummary(array $result): array
    {

        $raw = $result['raw'] ?? '';
        if (empty($raw)) {
            $res = $this->baseResponse();
            $res['success'] = false;
            $res['connection'] = false;
            $res['message'] = 'No se recibió respuesta válida de SUNAT.';
            $res['errors'] = ['No se recibió respuesta SOAP'];
            return $res;
        }

        try {
            $dom = XmlHelper::loadDom($raw);
            $xpath = new DOMXPath($dom);

            $ticket = XmlHelper::getNodeValue($xpath, "//*[local-name()='ticket']");
            if ($ticket) {
                // Comunicación OK, ticket recibido → aún no hay veredicto del XML
                $res = $this->baseResponse();
                $res['success'] = true;
                $res['connection'] = true;
                $res['sunat_success'] = true;  // aún no evaluado
                $res['state_label'] = 'pendiente';
                $res['message'] = 'Ticket recibido correctamente.';
                $res['ticket'] = $ticket;
                return $res;
            }

            // Sin ticket → revisar Fault
            $faultCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultcode']");
            $faultStr = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultstring']");

            $codeNumeric = null;
            if ($faultCode && preg_match('/(\d{3,4})$/', $faultCode, $m)) {
                $codeNumeric = $m[1];
            }
            $desc = $codeNumeric ? ($this->catalog->describe($codeNumeric) ?: $faultStr) : ($faultStr ?: $faultCode);
            $judge = $this->judgeCode($codeNumeric ?? $faultCode, false);

            $res = $this->baseResponse();
            $res['success'] = true;   // hubo comunicación
            $res['connection'] = true;
            $res['sunat_success'] = $judge['sunat_success']; // rechazo o indeterminado
            $res['state_label'] = $judge['state_label'];
            $res['message'] = $desc ?? 'No se recibió ticket.';
            $res['code'] = $judge['normalized_code'];
            $res['errors'] = array_filter([$faultCode, $faultStr]);
            return $res;

        } catch (\Throwable $e) {
            $res = $this->baseResponse();
            $res['success']      = true;  // hubo comunicación; el error es local (parseo)
            $res['connection']   = true;
            $res['sunat_success'] = null;
            $res['state_label']  = 'indeterminado';
            $res['message']      = 'Error al procesar respuesta de sendSummary: ' . $e->getMessage();
            $res['code']         = 'SUMMARY_PARSE_ERROR';
            $res['errors']       = [$e->getMessage()];
            return $res;
        }
    }

    /**
     * Parsea la respuesta de getStatusCdr (billConsultService): consulta el
     * CDR de un comprobante ya enviado, por RUC/tipo/serie/número.
     * Si viene content (ZIP b64 con el CDR), el veredicto sale del
     * cbc:ResponseCode del CDR (0 / >=4000 / 2000-3999); si no, se usa
     * statusCode + statusMessage del servicio.
     */
    public function parseStatusCdr(array $result): array
    {
        $raw = $result['raw'] ?? '';
        if (empty($raw)) {
            $res = $this->baseResponse();
            $res['success'] = false;
            $res['connection'] = false;
            $res['message'] = 'No se recibió respuesta válida de SUNAT.';
            $res['errors'] = ['No se recibió respuesta SOAP'];
            return $res;
        }

        try {
            $dom = XmlHelper::loadDom($raw);
            $xpath = new DOMXPath($dom);

            // Fault primero
            $faultCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultcode']");
            $faultStr = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultstring']");
            if ($faultCode || $faultStr) {
                $numeric = null;
                if ($faultCode && preg_match('/(\d{3,4})$/', $faultCode, $m)) {
                    $numeric = $m[1];
                }
                $desc = $numeric ? ($this->catalog->describe($numeric) ?: $faultStr) : ($faultStr ?: $faultCode);
                $judge = $this->judgeCode($numeric ?? $faultCode, false);

                $res = $this->baseResponse();
                $res['success'] = true;
                $res['connection'] = true;
                $res['sunat_success'] = $judge['sunat_success'];
                $res['state_label'] = $judge['state_label'];
                $res['message'] = $desc ?? 'Error en getStatusCdr.';
                $res['code'] = $judge['normalized_code'];
                $res['errors'] = array_filter([$faultCode, $faultStr]);
                return $res;
            }

            $statusCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='statusCode']");
            $statusMessage = XmlHelper::getNodeValue($xpath, "//*[local-name()='statusMessage']");
            $content = XmlHelper::getNodeValue($xpath, "//*[local-name()='content']");

            // Con CDR: el veredicto real es el ResponseCode del ApplicationResponse
            if ($content && base64_decode($content, true)) {
                $cdrZip = base64_decode($content);
                $xmlCdr = (new ZipExtractor())->extractXmlFromZip($cdrZip);

                $domCdr = XmlHelper::loadDom($xmlCdr);
                $xpCdr = new DOMXPath($domCdr);
                $xpCdr->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                $respCode = XmlHelper::getNodeValue($xpCdr, '//cbc:ResponseCode') ?? 'UNKNOWN';
                $rawMsg = XmlHelper::getNodeValue($xpCdr, '//cbc:Description') ?? 'Sin descripción.';
                $desc = $this->catalog->describe($respCode) ?: $rawMsg;
                $notes = XmlHelper::getNodeValues($xpCdr, '//cbc:Note');

                $judge = $this->judgeCode($respCode, false);

                $res = $this->baseResponse();
                $res['success'] = true;
                $res['connection'] = true;
                $res['sunat_success'] = $judge['sunat_success'];
                $res['state_label'] = $judge['state_label'];
                $res['message'] = $desc;
                $res['code'] = $judge['normalized_code'];
                $res['cdr'] = $xmlCdr;
                $res['notes'] = $notes ?: null;
                return $res;
            }

            // Sin CDR: solo statusCode/statusMessage del servicio de consulta
            $desc = $statusMessage ?: ($statusCode !== null ? ($this->catalog->describe($statusCode) ?: "Estado {$statusCode}.") : 'Respuesta sin estado.');
            $judge = $this->judgeCode($statusCode, false);

            $res = $this->baseResponse();
            $res['success'] = true;
            $res['connection'] = true;
            $res['sunat_success'] = $judge['sunat_success'];
            $res['state_label'] = $judge['state_label'];
            $res['message'] = $desc;
            $res['code'] = $judge['normalized_code'];
            return $res;

        } catch (\Throwable $e) {
            $res = $this->baseResponse();
            $res['success'] = true;
            $res['connection'] = true;
            $res['sunat_success'] = null;
            $res['state_label'] = 'indeterminado';
            $res['message'] = 'Error al procesar respuesta de getStatusCdr: ' . $e->getMessage();
            $res['code'] = 'STATUSCDR_PARSE_ERROR';
            $res['errors'] = [$e->getMessage()];
            return $res;
        }
    }

    public function parseGetStatus(array $result): array
    {

        $raw = $result['raw'] ?? '';
        if (empty($raw)) {
            $res = $this->baseResponse();
            $res['success'] = false;
            $res['connection'] = false;
            $res['sunat_success'] = null;
            $res['message'] = 'No se recibió respuesta válida de SUNAT.';
            $res['errors'] = ['No se recibió respuesta SOAP'];
            return $res;
        }

        try {
            $dom = XmlHelper::loadDom($raw);
            $xpath = new DOMXPath($dom);

            // Fault primero
            $faultCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultcode']");
            $faultStr = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultstring']");
            if ($faultCode || $faultStr) {
                $numeric = null;
                if ($faultCode && preg_match('/(\d{3,4})$/', $faultCode, $m)) {
                    $numeric = $m[1];
                }
                $desc = $numeric ? ($this->catalog->describe($numeric) ?: $faultStr) : ($faultStr ?: $faultCode);
                $judge = $this->judgeCode($numeric ?? $faultCode, true);

                $res = $this->baseResponse();
                $res['success'] = true;  // hubo comunicación
                $res['connection'] = true;
                $res['sunat_success'] = $judge['sunat_success']; // con ticket, 99 => false
                $res['state_label'] = $judge['state_label'];
                $res['message'] = $desc ?? 'Error en getStatus.';
                $res['code'] = $judge['normalized_code'];
                $res['errors'] = array_filter([$faultCode, $faultStr]);
                return $res;
            }

            // Estructura normal: <status><content>...</content><statusCode>...</statusCode></status>
            $statusCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='statusCode']");
            $content = XmlHelper::getNodeValue($xpath, "//*[local-name()='content']");

            if ($statusCode !== null) {
                // 2.a) 0 => acepta, debería venir CDR en content (ZIP b64). Evaluamos ResponseCode del CDR.
                if ($statusCode === '0') {
                    // Debe venir CDR (ZIP base64) en content
                    if ($content && base64_decode($content, true)) {
                        $cdrZip = base64_decode($content);
                        $xmlCdr = (new ZipExtractor())->extractXmlFromZip($cdrZip);

                        $domCdr = XmlHelper::loadDom($xmlCdr);
                        $xpCdr = new DOMXPath($domCdr);
                        $xpCdr->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        $respCode = XmlHelper::getNodeValue($xpCdr, '//cbc:ResponseCode') ?? 'UNKNOWN';
                        $rawMsg = XmlHelper::getNodeValue($xpCdr, '//cbc:Description') ?? 'Sin descripción.';
                        $desc = $this->catalog->describe($respCode) ?: $rawMsg;
                        $notes = XmlHelper::getNodeValues($xpCdr, '//cbc:Note');

                        $judge = $this->judgeCode($respCode, false);

                        $res = $this->baseResponse();
                        $res['success'] = true;
                        $res['connection'] = true;
                        $res['sunat_success'] = $judge['sunat_success'];
                        $res['state_label'] = $judge['state_label'];
                        $res['message'] = $desc;
                        $res['code'] = $judge['normalized_code'];
                        $res['cdr'] = $xmlCdr;
                        $res['notes'] = $notes ?: null;
                        return $res;
                    }

                    // statusCode = 0, pero no hay CDR válido
                    $res = $this->baseResponse();
                    $res['success'] = true;
                    $res['connection'] = true;
                    $res['sunat_success'] = null;   // hubo comunicación, pero sin veredicto válido
                    $res['state_label'] = 'indeterminado';
                    $res['message'] = 'SUNAT respondió estado OK pero no entregó un CDR válido.';
                    $res['code'] = 'NO_CDR';
                    $res['errors'] = ['Content no es base64 ZIP válido.'];
                    return $res;
                }

                // 2.b) 98 => en proceso (sin veredicto)
                if ($statusCode === '98') {
                    $judge = $this->judgeCode('98', true); // sunat_success=null
                    $res = $this->baseResponse();
                    $res['success'] = true;
                    $res['connection'] = true;
                    $res['sunat_success'] = $judge['sunat_success'];  // null
                    $res['state_label'] = $judge['state_label'];   // en_proceso
                    $res['message'] = 'El ticket aún está en procesamiento (98).';
                    $res['code'] = '98';
                    return $res;
                }


                // 2.c) 99 => rechazo; puede venir CDR o solo mensaje
                if ($statusCode === '99') {
                    // ¿Vino CDR en content?
                    if ($content && base64_decode($content, true)) {
                        $cdrZip = base64_decode($content);
                        $xmlCdr = (new ZipExtractor())->extractXmlFromZip($cdrZip);

                        $domCdr = XmlHelper::loadDom($xmlCdr);
                        $xpCdr = new DOMXPath($domCdr);
                        $xpCdr->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        $respCode = XmlHelper::getNodeValue($xpCdr, '//cbc:ResponseCode') ?? 'UNKNOWN';
                        $rawMsg = XmlHelper::getNodeValue($xpCdr, '//cbc:Description') ?? 'Sin descripción.';
                        $desc = $this->catalog->describe($respCode) ?: $rawMsg;
                        $notes = XmlHelper::getNodeValues($xpCdr, '//cbc:Note');

                        // Aunque el CDR pueda traer >=4000 (observación), el statusCode=99 indica rechazo del envío:
                        // mantenemos sunat_success=false y mapeamos label a 'rechazado'.
                        $res = $this->baseResponse();
                        $res['success'] = true;
                        $res['connection'] = true;
                        $res['sunat_success'] = false;
                        $res['state_label'] = 'rechazado';
                        $res['message'] = $desc;
                        $res['code'] = '99';           // mantener 99 como código principal del ticket
                        $res['cdr'] = $xmlCdr;        // XML del CDR
                        $res['notes'] = $notes ?: null; // detalles adicionales del CDR
                        return $res;
                    }

                    // 99 sin CDR → usa content como mensaje (o mapea por catálogo si aplica)
                    $desc = $content ?: ($this->catalog->describe('99') ?: 'Envío con error (99).');

                    $res = $this->baseResponse();
                    $res['success'] = true;
                    $res['connection'] = true;
                    $res['sunat_success'] = false;
                    $res['state_label'] = 'rechazado';
                    $res['message'] = $desc;
                    $res['code'] = '99';
                    $res['errors'] = $content ? [$content] : null;
                    return $res;
                }

                // 2.d) Otros códigos → error/indeterminado; usar content como mensaje si viene
                $desc = $content ?: ($this->catalog->describe($statusCode) ?: 'Error de estado en SUNAT.');
                $judge = $this->judgeCode($statusCode, true);

                $res = $this->baseResponse();
                $res['success'] = true;
                $res['connection'] = true;
                $res['sunat_success'] = $judge['sunat_success'];
                $res['state_label'] = $judge['state_label'];
                $res['message'] = $desc;
                $res['code'] = $judge['normalized_code'];
                $res['errors'] = $content ? [$content] : null;
                return $res;
            }

            // Sin Fault y sin statusCode → formato inesperado
            $res = $this->baseResponse();
            $res['success'] = true;
            $res['connection'] = true;
            $res['sunat_success'] = null; // hubo comunicación, sin dictamen
            $res['state_label'] = 'indeterminado';
            $res['message'] = 'Estructura de respuesta getStatus no reconocida.';
            $res['code'] = 'UNEXPECTED_STATUS_FORMAT';
            return $res;

        } catch (\Throwable $e) {
            $res = $this->baseResponse();
            $res['success']      = true;  // ✅ hubo comunicación; el error es local (parseo)
            $res['connection']   = true;
            $res['sunat_success'] = null;
            $res['state_label']  = 'indeterminado';
            $res['message']      = 'Error al procesar respuesta de getStatus: ' . $e->getMessage();
            $res['code']         = 'GETSTATUS_PARSE_ERROR';
            $res['errors']       = [$e->getMessage()];
            return $res;
        }
    }
}
