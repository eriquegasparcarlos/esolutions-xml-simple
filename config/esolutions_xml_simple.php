<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firma XMLDSig
    |--------------------------------------------------------------------------
    |
    | default_certificate_file: ruta absoluta a un .pem/.pfx/.p12 propio.
    | null => usa el certificado demo del paquete (solo válido en beta SUNAT).
    |
    */
    'signing' => [
        'default_certificate_file' => env('SUNAT_CERTIFICATE_FILE'),
        'default_certificate_password' => env('SUNAT_CERTIFICATE_PASSWORD'),
        'signature_uri' => 'SIGN',
        'signature_note' => 'SIGN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Vistas Blade
    |--------------------------------------------------------------------------
    |
    | Nombre de vista por tipo normalizado (sin el prefijo 'esxml::' —
    | XmlTemplateRenderer lo antepone). Deben coincidir con el archivo real
    | en src/Templates/ (guiones, no guion bajo).
    |
    */
    'views' => [
        'invoice' => 'invoice',
        'purchase_settlement' => 'purchase-settlement',
        'credit_note' => 'credit-note',
        'debit_note' => 'debit-note',
        'summary' => 'summary',
        'voided' => 'voided',
        // La reversión (RR) usa el mismo XML VoidedDocuments que el RA; solo
        // cambian el prefijo del identifier y los tipos de doc en las líneas.
        'reversion' => 'voided',
        'despatch' => 'despatch',
        'despatch_carrier' => 'despatch-carrier',
        'retention' => 'retention',
        'perception' => 'perception',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rutas
    |--------------------------------------------------------------------------
    |
    | null => defaults internos del paquete (src/Templates y src/Resources/xsd).
    | Solo definir para sobreescribir con plantillas/esquemas propios.
    |
    */
    'paths' => [
        'templates_path' => null,
        'xsd_base' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Esquemas XSD por tipo normalizado (relativos a xsd_base)
    |--------------------------------------------------------------------------
    */
    'schemas' => [
        'invoice' => '2.1/maindoc/UBL-Invoice-2.1.xsd',
        'purchase_settlement' => '2.1/maindoc/UBL-SelfBilledInvoice-2.1.xsd',
        'credit_note' => '2.1/maindoc/UBL-CreditNote-2.1.xsd',
        'debit_note' => '2.1/maindoc/UBL-DebitNote-2.1.xsd',
        'despatch' => '2.1/maindoc/UBL-DespatchAdvice-2.1.xsd',
        'despatch_carrier' => '2.1/maindoc/UBL-DespatchAdvice-2.1.xsd',
        'summary' => '2.0/maindoc/UBL-SummaryDocuments-2.0.xsd',
        'voided' => '2.0/maindoc/UBL-VoidedDocuments-2.0.xsd',
        'reversion' => '2.0/maindoc/UBL-VoidedDocuments-2.0.xsd',
        'retention' => '2.0/maindoc/UBL-Retention-2.0.xsd',
        'perception' => '2.0/maindoc/UBL-Perception-2.0.xsd',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validación de reglas cliente SUNAT (SFS) — capa pre-envío
    |--------------------------------------------------------------------------
    |
    | expressions: incluir reglas de reconciliación aritmética (isTrueExpresion).
    |   Frágiles de replicar (sumas/redondeos) → por defecto off; las clave
    |   (3294/3305) se cubren con reglas propias en el generador.
    | sunat_suppress: códigos suprimidos por ser el XSLT cliente más estricto
    |   que el servidor para estructuras que SUNAT sí acepta.
    |
    */
    'validation' => [
        'expressions' => false,
        'sunat_suppress' => ['3033', '3035', '3128'],

        /*
        | Gate de reglas SUNAT ANTES de firmar (opt-in). Si está activo, se corre
        | SunatRulesValidator sobre el XML sin firmar y, si hay errores, NO se
        | firma: se devuelve un GenerationResult fallido con el detalle (errores +
        | observaciones) para solventar. Las OBSERVACIONES (códigos >= 4000)
        | bloquean o pasan según block_on_observations; si pasan, van como
        | `warnings` en el resultado. Por defecto off (no cambia el flujo actual).
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Fechas de vigencia de reglas nuevas de SUNAT (date-gating)
    |--------------------------------------------------------------------------
    |
    | Cada regla/campo nuevo se aplica solo a documentos con fecha de emisión >=
    | su vigencia (SUNAT valida por fecha de emisión). Configurables por si SUNAT
    | posterga una vigencia: se cambia la fecha aquí, sin re-desplegar el paquete.
    | Ver docs/sunat-changes-2026-08.md.
    |
    | POSTERGACIÓN: el Excel "Reglas de validación" del 24.07.2026 (Control de
    | Cambios, línea 562) postergó del 01/08/2026 al 01/01/2027 la vigencia de
    | las líneas 516-545 — que incluyen ERR-3496 (código producto, 527/530),
    | ERR-3507 (ND 13, 538) y todo el resumen diario 2.0 (gratuitas/tasa IGV/
    | adquirente, 541-545). Por eso las tres fechas quedan en 2027-01-01.
    |
    */
    'rule_dates' => [
        'product_code'  => env('SUNAT_RULE_PRODUCT_CODE', '2027-01-01'),  // #12 ERR-3496 (postergado 24.07.2026)
        'nd13_inafecta' => env('SUNAT_RULE_ND13', '2027-01-01'),          // #23 ERR-3507 (postergado 24.07.2026)
        'summary_2026'  => env('SUNAT_RULE_SUMMARY_2026', '2027-01-01'),  // #26/#27/#29 resumen diario (postergado 24.07.2026)
    ],

    /*
    |--------------------------------------------------------------------------
    | Envío SUNAT / OSE
    |--------------------------------------------------------------------------
    |
    | Defaults globales; en apps multi-tenant construir SenderConfig::fromArray()
    | con las credenciales SOL de cada empresa.
    |
    */
    'sending' => [
        'provider' => env('SUNAT_PROVIDER', 'sunat'), // 'sunat' | 'nubefact'
        'environment' => env('SUNAT_ENVIRONMENT', 'demo'), // 'demo' | 'production'
        'username' => env('SUNAT_SOL_USER'),
        'password' => env('SUNAT_SOL_PASSWORD'),
    ],
];
