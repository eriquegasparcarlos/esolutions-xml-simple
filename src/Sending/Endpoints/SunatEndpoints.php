<?php

namespace ESolutions\XmlSimple\Sending\Endpoints;

/**
 * Class SunatEndpoints.
 */
final class SunatEndpoints
{
    /**
     *  FACTURACION SERVICES.
     */
    const string FE_BETA = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService';
    const string FE_PRODUCTION= 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService';
    const string FE_CONSULTA_CDR = 'https://e-factura.sunat.gob.pe/ol-it-wsconscpegem/billConsultService';

    /**
     * RETENCION Y PERCEPCION SERVICES.
     */
    const RETENCION_BETA = 'https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService';
    const RETENCION_PRODUCCION = 'https://e-factura.sunat.gob.pe/ol-ti-itemision-otroscpe-gem/billService';

    /**
     * WSDL Uri.
     */
    const WSDL_ENDPOINT = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService?wsdl';
}
