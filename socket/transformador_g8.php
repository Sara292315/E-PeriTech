<?php
// ==========================================
// GUIA 8 - ACTIVIDAD 5: TRANSFORMACION XSLT
// Convierte el XML de respuesta en HTML legible
// ==========================================

// XML de ejemplo (en produccion vendria del socket, igual que cliente_g8.php)
$xml_respuesta = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mensaje>
    <tipo>response</tipo>
    <estado>OK</estado>
    <datos>
        <producto>
            <id>2</id>
            <nombre>Teclado Mecanico RGB Gaming</nombre>
            <categoria>teclados</categoria>
            <precio>225000.00</precio>
            <descripcion>Teclado mecanico con iluminacion RGB gaming</descripcion>
        </producto>
    </datos>
    <control>
        <servidor>ServidorEPeriTech</servidor>
        <timestamp>2026-04-27T10:30:01</timestamp>
    </control>
</mensaje>
XML;

// 1. Cargar el XML de respuesta
$xml = new DOMDocument();
$xml->loadXML($xml_respuesta);

// 2. Cargar el archivo XSLT
$xslt = new DOMDocument();
$xslt->load(__DIR__ . "/producto.xslt");

// 3. Crear el procesador XSLT y aplicar la transformacion
$proc = new XSLTProcessor();
$proc->importStylesheet($xslt);
$html = $proc->transformToXML($xml);

// 4. Mostrar el resultado
echo "=== TRANSFORMACION XSLT === ";
echo "XML transformado a HTML:  ";
echo $html;
echo " === FIN TRANSFORMACION === ";
?>
