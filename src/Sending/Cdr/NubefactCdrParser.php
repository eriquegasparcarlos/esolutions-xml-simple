<?php

namespace ESolutions\XmlSimple\Sending\Cdr;

use ESolutions\XmlSimple\Contracts\CdrResponseParserInterface;
use ESolutions\XmlSimple\Contracts\ErrorCodeCatalogInterface;
use ESolutions\XmlSimple\Sending\Catalog\FileErrorCodeCatalog;
use ESolutions\XmlSimple\Sending\Zip\ZipExtractor;
use ESolutions\XmlSimple\Support\XmlHelper;
use DOMXPath;

/**
 * Parser específico para respuestas CDR de Nubefact OSE.
 * Estructura y semántica igual que SunatCdrParser:
 * - success/connection/sunat_success/state_label/notes/errors/cdr/code/message
 */
class NubefactCdrParser implements CdrResponseParserInterface
{
    protected ErrorCodeCatalogInterface $catalog;

    public function __construct(?ErrorCodeCatalogInterface $catalog = null)
    {
        $this->catalog = $catalog ?? new FileErrorCodeCatalog();
    }

    /**
     * Juzga el código de respuesta y devuelve:
     * - sunat_success: true/false/null
     * - state_label: aceptado|observado|rechazado|en_proceso|pendiente|indeterminado
     * - normalized_code: string|null
     * $isTicket=true cuando proviene de getStatus (0/98/99).
     */
    private function judgeCode($code, bool $isTicket = false): array
    {
        if ($code === null || $code === '') {
            return ['sunat_success' => null, 'state_label' => 'indeterminado', 'normalized_code' => null];
        }

        $codeStr = (string)$code;

        if ($isTicket) {
            if ($codeStr === '0')  return ['sunat_success' => true,  'state_label' => 'aceptado',     'normalized_code' => '0'];
            if ($codeStr === '98') return ['sunat_success' => null,  'state_label' => 'en_proceso',   'normalized_code' => '98'];
            if ($codeStr === '99') return ['sunat_success' => false, 'state_label' => 'rechazado',    'normalized_code' => '99'];
            return ['sunat_success' => null, 'state_label' => 'indeterminado', 'normalized_code' => $codeStr];
        }

        if ($codeStr === '0') {
            return ['sunat_success' => true, 'state_label' => 'aceptado', 'normalized_code' => '0'];
        }

        $n = ctype_digit($codeStr) ? (int)$codeStr : null;
        if ($n !== null) {
            if ($n >= 4000)                 return ['sunat_success' => true,  'state_label' => 'observado',     'normalized_code' => $codeStr];
            if ($n >= 2000 && $n <= 3999)   return ['sunat_success' => false, 'state_label' => 'rechazado',     'normalized_code' => $codeStr];
            if ($n >= 100  && $n <= 1999)    return ['sunat_success' => null,  'state_label' => 'indeterminado', 'normalized_code' => $codeStr];
        }

        return ['sunat_success' => null, 'state_label' => 'indeterminado', 'normalized_code' => $codeStr];
    }

    private function baseResponse(): array
    {
        return [
            'success'        => true,
            'connection'     => true,
            'sunat_success'  => null,
            'message'        => null,
            'code'           => null,
            'ticket'         => null,
            'cdr'            => null,   // XML del CDR (no base64)
            'notes'          => null,   // array|string|null
            'errors'         => null,   // array|null
            'state_label'    => null,   // aceptado/observado/rechazado/en_proceso/pendiente/indeterminado
        ];
    }

    /**
     * sendBill / sendInvoice (documentos individuales).
     */
    public function parseBill(array $result): array
    {

        $raw = $result['raw'] ?? '';
        if (empty($raw)) {
            $res = $this->baseResponse();
            $res['success']    = false;
            $res['connection'] = false;
            $res['message']    = 'No se recibió respuesta válida de OSE/Nubefact.';
            $res['errors']     = ['No se recibió respuesta SOAP'];
            return $res;
        }

        try {
            $dom   = XmlHelper::loadDom($raw);
            $xpath = new DOMXPath($dom);

            // ¿Hay CDR (applicationResponse)?
            $cdrBase64 = XmlHelper::getNodeValue($xpath, "//*[local-name()='applicationResponse']");
            if ($cdrBase64 && base64_decode($cdrBase64, true)) {
                $cdrZip = base64_decode($cdrBase64);
                $xmlCdr = (new ZipExtractor())->extractXmlFromZip($cdrZip);

                $domCdr = XmlHelper::loadDom($xmlCdr);
                $xpCdr  = new DOMXPath($domCdr);
                $xpCdr->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                $respCode = XmlHelper::getNodeValue($xpCdr, '//cbc:ResponseCode') ?? 'UNKNOWN';
                $rawMsg   = XmlHelper::getNodeValue($xpCdr, '//cbc:Description') ?? 'Sin descripción.';
                $desc     = $this->catalog->describe($respCode) ?: $rawMsg;
                $notes    = XmlHelper::getNodeValues($xpCdr, '//cbc:Note');

                $judge = $this->judgeCode($respCode, false);

                $res = $this->baseResponse();
                $res['success']        = true;
                $res['connection']     = true;
                $res['sunat_success']  = $judge['sunat_success'];
                $res['state_label']    = $judge['state_label'];
                $res['message']        = $desc;
                $res['code']           = $judge['normalized_code'];
                $res['cdr']            = $xmlCdr;
                $res['notes']          = $notes ?: null;
                return $res;
            }

            // ¿Fault?
            $faultCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultcode']");
            $faultStr  = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultstring']");
            $detailMsg = XmlHelper::getNodeValue($xpath, "//*[local-name()='message']"); // a veces Nubefact añade <message>

            if ($faultCode || $faultStr || $detailMsg) {
                // intentar extraer numérico desde faultcode (…NNNN) o desde faultstring
                $numeric = null;
                if ($faultCode && preg_match('/(\d{3,4})$/', $faultCode, $m)) {
                    $numeric = $m[1];
                } elseif ($faultStr && preg_match('/(\d{3,4})$/', $faultStr, $m)) {
                    $numeric = $m[1];
                }

                $desc  = $numeric ? ($this->catalog->describe($numeric) ?: ($detailMsg ?: $faultStr)) : ($detailMsg ?: $faultStr ?: $faultCode);
                $judge = $this->judgeCode($numeric ?? $faultCode ?? $faultStr, false);

                $res = $this->baseResponse();
                $res['success']        = true;  // hubo comunicación
                $res['connection']     = true;
                $res['sunat_success']  = $judge['sunat_success'];
                $res['state_label']    = $judge['state_label'];
                $res['message']        = $desc ?? 'Error en envío.';
                $res['code']           = $judge['normalized_code'];
                $res['errors']         = array_filter([$faultCode, $faultStr, $detailMsg]);
                return $res;
            }

            // Respuesta rara (sin CDR ni Fault), pero hubo raw
            $res = $this->baseResponse();
            $res['success']        = true;
            $res['connection']     = true;
            $res['sunat_success']  = null;
            $res['state_label']    = 'indeterminado';
            $res['message']        = 'Respuesta de OSE/Nubefact sin CDR ni Fault.';
            return $res;

        } catch (\Throwable $e) {
            $res = $this->baseResponse();
            $res['success']        = true;  // hubo comunicación; error local de parseo
            $res['connection']     = true;
            $res['sunat_success']  = null;
            $res['state_label']    = 'indeterminado';
            $res['message']        = 'Error al procesar respuesta (Nubefact Bill): ' . $e->getMessage();
            $res['code']           = 'PARSE_ERROR';
            $res['errors']         = [$e->getMessage()];
            return $res;
        }
    }

    /**
     * sendSummary (RC/RA) – devuelve ticket o Fault.
     */
    public function parseSummary(array $result): array
    {

        $raw = $result['raw'] ?? '';
        if (empty($raw)) {
            $res = $this->baseResponse();
            $res['success']    = false;
            $res['connection'] = false;
            $res['message']    = 'No se recibió respuesta válida de OSE/Nubefact.';
            $res['errors']     = ['No se recibió respuesta SOAP'];
            return $res;
        }

        try {
            $dom   = XmlHelper::loadDom($raw);
            $xpath = new DOMXPath($dom);

            $ticket = XmlHelper::getNodeValue($xpath, "//*[local-name()='ticket']");
            if ($ticket) {
                $res = $this->baseResponse();
                $res['success']        = true;
                $res['connection']     = true;
                $res['sunat_success']  = true;       // comunicación OK; veredicto vendrá en getStatus
                $res['state_label']    = 'pendiente';
                $res['message']        = 'Ticket recibido correctamente.';
                $res['ticket']         = $ticket;
                return $res;
            }

            // Fault (sin ticket)
            $faultCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultcode']");
            $faultStr  = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultstring']");
            $detailMsg = XmlHelper::getNodeValue($xpath, "//*[local-name()='message']");

            $numeric = null;
            if ($faultCode && preg_match('/(\d{3,4})$/', $faultCode, $m)) {
                $numeric = $m[1];
            } elseif ($faultStr && preg_match('/(\d{3,4})$/', $faultStr, $m)) {
                $numeric = $m[1];
            }

            $desc  = $numeric ? ($this->catalog->describe($numeric) ?: ($detailMsg ?: $faultStr)) : ($detailMsg ?: $faultStr ?: $faultCode);
            $judge = $this->judgeCode($numeric ?? $faultCode ?? $faultStr, false);

            $res = $this->baseResponse();
            $res['success']        = true;
            $res['connection']     = true;
            $res['sunat_success']  = $judge['sunat_success'] ?? false;
            $res['state_label']    = $judge['state_label'];
            $res['message']        = $desc ?? 'No se recibió ticket.';
            $res['code']           = $judge['normalized_code'];
            $res['errors']         = array_filter([$faultCode, $faultStr, $detailMsg]);
            return $res;

        } catch (\Throwable $e) {
            $res = $this->baseResponse();
            $res['success']        = true;
            $res['connection']     = true;
            $res['sunat_success']  = null;
            $res['state_label']    = 'indeterminado';
            $res['message']        = 'Error al procesar respuesta de sendSummary (Nubefact): ' . $e->getMessage();
            $res['code']           = 'SUMMARY_PARSE_ERROR';
            $res['errors']         = [$e->getMessage()];
            return $res;
        }
    }

    /**
     * getStatus (por ticket) – 0/98/99 + CDR opcional.
     */
    public function parseGetStatus(array $result): array
    {

        $raw = $result['raw'] ?? '';
        if (empty($raw)) {
            $res = $this->baseResponse();
            $res['success']    = false;
            $res['connection'] = false;
            $res['sunat_success'] = null;
            $res['message']    = 'No se recibió respuesta válida de SUNAT/OSE.';
            $res['errors']     = ['No se recibió respuesta SOAP'];
            return $res;
        }

        try {
            $dom   = XmlHelper::loadDom($raw);
            $xpath = new DOMXPath($dom);

            // Primero Fault
            $faultCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultcode']");
            $faultStr  = XmlHelper::getNodeValue($xpath, "//*[local-name()='faultstring']");
            if ($faultCode || $faultStr) {
                $numeric = null;
                if ($faultCode && preg_match('/(\d{3,4})$/', $faultCode, $m)) {
                    $numeric = $m[1];
                } elseif ($faultStr && preg_match('/(\d{3,4})$/', $faultStr, $m)) {
                    $numeric = $m[1];
                }
                $desc  = $numeric ? ($this->catalog->describe($numeric) ?: $faultStr) : ($faultStr ?: $faultCode);
                $judge = $this->judgeCode($numeric ?? $faultCode ?? $faultStr, true);

                $res = $this->baseResponse();
                $res['success']        = true;
                $res['connection']     = true;
                $res['sunat_success']  = $judge['sunat_success'];  // 99 => false
                $res['state_label']    = $judge['state_label'];
                $res['message']        = $desc ?? 'Error en getStatus.';
                $res['code']           = $judge['normalized_code'];
                $res['errors']         = array_filter([$faultCode, $faultStr]);
                return $res;
            }

            // Estructura normal: <status><content>...</content><statusCode>...</statusCode></status>
            $statusCode = XmlHelper::getNodeValue($xpath, "//*[local-name()='statusCode']");
            $content    = XmlHelper::getNodeValue($xpath, "//*[local-name()='content']");

            if ($statusCode !== null) {
                // 0 => viene CDR (ZIP base64); evaluamos ResponseCode del CDR
                if ($statusCode === '0') {
                    if ($content && base64_decode($content, true)) {
                        $cdrZip = base64_decode($content);
                        $xmlCdr = (new ZipExtractor())->extractXmlFromZip($cdrZip);

                        $domCdr = XmlHelper::loadDom($xmlCdr);
                        $xpCdr  = new DOMXPath($domCdr);
                        $xpCdr->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        $respCode = XmlHelper::getNodeValue($xpCdr, '//cbc:ResponseCode') ?? 'UNKNOWN';
                        $rawMsg   = XmlHelper::getNodeValue($xpCdr, '//cbc:Description') ?? 'Sin descripción.';
                        $desc     = $this->catalog->describe($respCode) ?: $rawMsg;
                        $notes    = XmlHelper::getNodeValues($xpCdr, '//cbc:Note');

                        $judge = $this->judgeCode($respCode, false);

                        $res = $this->baseResponse();
                        $res['success']        = true;
                        $res['connection']     = true;
                        $res['sunat_success']  = $judge['sunat_success'];
                        $res['state_label']    = $judge['state_label'];
                        $res['message']        = $desc;
                        $res['code']           = $judge['normalized_code'];
                        $res['cdr']            = $xmlCdr;
                        $res['notes']          = $notes ?: null;
                        return $res;
                    }

                    $res = $this->baseResponse();
                    $res['success']        = true;
                    $res['connection']     = true;
                    $res['sunat_success']  = null; // comunicación sin veredicto válido
                    $res['state_label']    = 'indeterminado';
                    $res['message']        = 'SUNAT/OSE respondió 0 pero no entregó un CDR válido.';
                    $res['code']           = 'NO_CDR';
                    $res['errors']         = ['Content no es base64 ZIP válido.'];
                    return $res;
                }

                // 98 => en proceso
                if ($statusCode === '98') {
                    $judge = $this->judgeCode('98', true);
                    $res = $this->baseResponse();
                    $res['success']        = true;
                    $res['connection']     = true;
                    $res['sunat_success']  = $judge['sunat_success'];  // null
                    $res['state_label']    = $judge['state_label'];    // en_proceso
                    $res['message']        = 'El ticket aún está en procesamiento (98).';
                    $res['code']           = '98';
                    return $res;
                }

                // 99 => Rechazado; puede traer CDR o solo texto en content
                if ($statusCode === '99') {
                    if ($content && base64_decode($content, true)) {
                        $cdrZip = base64_decode($content);
                        $xmlCdr = (new ZipExtractor())->extractXmlFromZip($cdrZip);

                        $domCdr = XmlHelper::loadDom($xmlCdr);
                        $xpCdr  = new DOMXPath($domCdr);
                        $xpCdr->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

                        $respCode = XmlHelper::getNodeValue($xpCdr, '//cbc:ResponseCode') ?? 'UNKNOWN';
                        $rawMsg   = XmlHelper::getNodeValue($xpCdr, '//cbc:Description') ?? 'Sin descripción.';
                        $desc     = $this->catalog->describe($respCode) ?: $rawMsg;
                        $notes    = XmlHelper::getNodeValues($xpCdr, '//cbc:Note');

                        $res = $this->baseResponse();
                        $res['success']        = true;
                        $res['connection']     = true;
                        $res['sunat_success']  = false;          // 99 ⇒ rechazo
                        $res['state_label']    = 'rechazado';
                        $res['message']        = $desc;
                        $res['code']           = '99';           // mantenemos 99 como código principal del ticket
                        $res['cdr']            = $xmlCdr;
                        $res['notes']          = $notes ?: null;
                        return $res;
                    }

                    $desc = $content ?: ($this->catalog->describe('99') ?: 'Envío con error (99).');

                    $res = $this->baseResponse();
                    $res['success']        = true;
                    $res['connection']     = true;
                    $res['sunat_success']  = false;
                    $res['state_label']    = 'rechazado';
                    $res['message']        = $desc;
                    $res['code']           = '99';
                    $res['errors']         = $content ? [$content] : null;
                    return $res;
                }

                // Otros códigos
                $desc  = $content ?: ($this->catalog->describe($statusCode) ?: 'Error de estado en SUNAT/OSE.');
                $judge = $this->judgeCode($statusCode, true);

                $res = $this->baseResponse();
                $res['success']        = true;
                $res['connection']     = true;
                $res['sunat_success']  = $judge['sunat_success'];
                $res['state_label']    = $judge['state_label'];
                $res['message']        = $desc;
                $res['code']           = $judge['normalized_code'];
                $res['errors']         = $content ? [$content] : null;
                return $res;
            }

            // Sin Fault y sin statusCode
            $res = $this->baseResponse();
            $res['success']        = true;
            $res['connection']     = true;
            $res['sunat_success']  = null;
            $res['state_label']    = 'indeterminado';
            $res['message']        = 'Estructura de respuesta getStatus no reconocida.';
            $res['code']           = 'UNEXPECTED_STATUS_FORMAT';
            return $res;

        } catch (\Throwable $e) {
            $res = $this->baseResponse();
            $res['success']        = true;   // hubo raw → comunicación hubo; error local de parseo
            $res['connection']     = true;
            $res['sunat_success']  = null;
            $res['state_label']    = 'indeterminado';
            $res['message']        = 'Error al procesar respuesta de getStatus (Nubefact): ' . $e->getMessage();
            $res['code']           = 'GETSTATUS_PARSE_ERROR';
            $res['errors']         = [$e->getMessage()];
            return $res;
        }
    }
}
