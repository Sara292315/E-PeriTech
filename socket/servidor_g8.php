<?php
$host = "127.0.0.1"; // IP local de Windows
$puerto = 8080;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $host, $puerto);
socket_listen($socket);

echo "Servidor E-PeriTech Guia 8 escuchando en windows (127.0.0.1:$puerto)...\n";

while (true) {
    $cliente = socket_accept($socket);
    $datos_recibidos = socket_read($cliente, 2048);
    
    if ($datos_recibidos) {
        echo "\n--- XML Recibido ---\n";
        
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); 
        $doc->loadXML($datos_recibidos);
        
        // Valida con el archivo XSD
        if ($doc->schemaValidate('protocolo.xsd')) {
            echo "¡El XML es válido!\n";
            
            $xpath = new DOMXPath($doc);
            $operacion = $xpath->query('//operacion')->item(0)->nodeValue;
            $categoria = $xpath->query('//categoria')->item(0)->nodeValue;
            
            echo "Buscando periférico: $categoria...\n";
            
            $respuesta = "OK: Busqueda de $categoria exitosa.";
            socket_write($cliente, $respuesta, strlen($respuesta));
        } else {
            echo "Error: XML Invalido.\n";
            $error = "ERROR: Formato XML Invalido.";
            socket_write($cliente, $error, strlen($error));
        }
    }
    socket_close($cliente);
}
?>