<?php

namespace ESolutions\XmlSimple\Rendering;

class XmlTemplateRenderer
{
    public function render(string $viewName, array $payload): string
    {
        $xml = view('esxmlsimple::' . $viewName, $payload)->render();

        $xml = preg_replace('/^\xEF\xBB\xBF/', '', $xml);
        return trim($xml);
    }
}
