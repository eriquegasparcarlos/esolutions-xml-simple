<?php

namespace ESolutions\XmlSimple\Tests;

use ESolutions\XmlSimple\Results\Sending\BillResult;
use PHPUnit\Framework\TestCase as PlainTestCase;

/**
 * La clasificación de resultado (errorSource / reachedSunat / recommendedAction)
 * es pura lógica sobre las banderas del CDR; no necesita bootear Laravel.
 */
class ResultClassificationTest extends PlainTestCase
{
    public function test_aceptado_no_tiene_error_y_llego_a_sunat(): void
    {
        $r = BillResult::fromArray([
            'success' => true, 'connection' => true, 'sunat_success' => true, 'state_label' => 'aceptado',
        ]);

        $this->assertNull($r->errorSource());
        $this->assertTrue($r->reachedSunat());
        $this->assertNull($r->recommendedAction());
    }

    public function test_rechazado_es_origen_sunat_llego_y_debe_corregir(): void
    {
        $r = BillResult::fromArray([
            'success' => true, 'connection' => true, 'sunat_success' => false, 'state_label' => 'rechazado',
        ]);

        $this->assertSame('sunat', $r->errorSource());
        $this->assertTrue($r->reachedSunat());
        $this->assertSame('fix', $r->recommendedAction());
    }

    public function test_sin_conexion_no_llego_y_debe_reintentar(): void
    {
        $r = BillResult::fromArray([
            'success' => false, 'connection' => false, 'sunat_success' => null, 'state_label' => null,
        ]);

        $this->assertSame('conexion', $r->errorSource());
        $this->assertFalse($r->reachedSunat());
        $this->assertSame('retry', $r->recommendedAction());
    }

    public function test_error_local_es_sistema_llego_y_debe_revisar(): void
    {
        $r = BillResult::fromArray([
            'success' => false, 'connection' => true, 'sunat_success' => null, 'state_label' => 'indeterminado',
        ]);

        $this->assertSame('sistema', $r->errorSource());
        $this->assertTrue($r->reachedSunat());
        $this->assertSame('review', $r->recommendedAction());
    }

    public function test_en_proceso_no_tiene_error(): void
    {
        $r = BillResult::fromArray([
            'success' => true, 'connection' => true, 'sunat_success' => null, 'state_label' => 'en_proceso',
        ]);

        $this->assertNull($r->errorSource());
        $this->assertTrue($r->reachedSunat());
        $this->assertNull($r->recommendedAction());
    }
}
