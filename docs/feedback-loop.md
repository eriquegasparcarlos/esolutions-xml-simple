# Bucle de mejora de validaciones (Feedback loop)

El paquete valida el XML contra las reglas extraídas de SUNAT (`SunatRulesValidator`),
pero ninguna validación local cubre el 100% de lo que rechaza el servidor de SUNAT.
Para **descubrir qué nos falta**, cada proyecto que instale el paquete puede registrar
los rechazos reales y ver cuáles NO atrapó su validador local (los *huecos*). Esos
códigos son los candidatos a agregar en `OwnRules`.

El mecanismo está partido en dos mitades:

| Mitad | Dónde vive | Qué hace |
|---|---|---|
| **Analizar** | Paquete (`Feedback\RejectionAnalyzer`) | Revalida el mismo XML localmente y calcula `caughtLocally` |
| **Persistir** | Consumidor (`RejectionSink`) | Guarda el `RejectionReport` donde quiera |

## Uso mínimo (cero infraestructura: log JSONL)

Cualquier proyecto captura huecos sin base de datos ni migraciones:

```php
use ESolutions\Xml\Feedback\RejectionAnalyzer;
use ESolutions\Xml\Feedback\JsonlSink;

// Tras un envío rechazado por SUNAT/OSE:
$report = (new RejectionAnalyzer())->analyze(
    $xmlFirmado,          // el mismo XML que se envió
    $result->getCode(),   // código del rechazo, p. ej. "3305"
    $documentTypeId,      // 01/03/07/08/RC/RA…
    [
        'message'     => $result->getMessage(),
        'series'      => 'F001',
        'number'      => '123',
        'filename'    => $filename,
        'environment' => 'production',
    ]
);

(new JsonlSink(storage_path('logs/sunat_rejections.jsonl')))->store($report);
```

### Reporte de huecos

```php
$sink = new JsonlSink(storage_path('logs/sunat_rejections.jsonl'));

foreach ($sink->gaps() as $g) {
    // $g = ['code','total','caught','missed','description']
    if ($g['missed'] > 0) {
        echo "HUECO {$g['code']} x{$g['missed']} — {$g['description']}\n";
    }
}
```

Ordenado por cantidad de huecos. Los `missed > 0` son reglas que aún no tenemos.

## Sink propio (DB, telemetría, etc.)

Implementa la interfaz y persiste como te convenga:

```php
use ESolutions\Xml\Feedback\RejectionSink;
use ESolutions\Xml\Feedback\RejectionReport;

class EloquentRejectionSink implements RejectionSink
{
    public function store(RejectionReport $report): void
    {
        MiModelo::create($report->toArray());
    }
}
```

`RejectionReport::toArray()` entrega claves snake_case listas para persistir:
`code, document_type_id, caught_locally, local_findings, description, series,
number, filename, provider, environment, notes`.

> intipos13 usa este patrón: su `SunatRejectionRecorder` llama al `RejectionAnalyzer`
> del paquete y persiste en la tabla multi-tenant `sunat_rejections` (mejor para
> consultar por tenant). Otros proyectos usan `JsonlSink` y listo.

## Cerrar el bucle

1. Los rechazos con `caught_locally = false` revelan reglas faltantes.
2. Se agrega la reconciliación correspondiente en
   `ESolutions\Xml\Validation\Sunat\OwnRules`.
3. `composer update esolutions/xml` distribuye la nueva regla a **todas** las
   instalaciones del paquete.
