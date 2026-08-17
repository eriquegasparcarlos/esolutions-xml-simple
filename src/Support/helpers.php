<?php

/*
 * Helpers globales usados por las plantillas Blade del paquete. Se autoloadean
 * vía composer (autoload.files) y están guardados con function_exists para no
 * colisionar si el proyecto consumidor ya las define. Así el paquete es
 * autosuficiente: no depende de que el consumidor provea estas funciones.
 */

if (!function_exists('funcNumberFormatXml')) {
    /**
     * Formatea un número para XML UBL: punto decimal, sin separador de miles.
     */
    function funcNumberFormatXml($value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }
}
