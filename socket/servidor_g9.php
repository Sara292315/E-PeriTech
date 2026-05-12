<?php
$host = "127.0.0.1";
$puerto = 8080;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $host, $puerto);
socket_listen($socket);

echo "Servidor SOAP E-PeriTech (Guia 9) escuchando en $host:$puerto...\n";

while (true) {
    $cliente = socket_accept($socket);
    $datos_recibidos = socket_read($cliente, 2048);
    
    if ($datos_recibidos) {
        echo "\n--- Sobre SOAP Recibido ---\n";
        
        // Interpretar el XML
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); 
        $doc->loadXML($datos_recibidos);
        
        // Usar XPath para extraer datos dentro de la etiqueta SOAP
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('eperi', 'http://eperitech.fesc.edu');
        
        $nodos_categoria = $xpath->query('//eperi:consulta_producto/categoria');
        
        if ($nodos_categoria->length > 0) {
            $categoria = $nodos_categoria->item(0)->nodeValue;
            echo "El cliente esta consultando la categoria: $categoria\n";
            
            // Empaquetar la respuesta también en SOAP
            $respuesta_soap = '<?xml version="1.0"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
              <soap:Body>
                <resultado>OK: Consulta de ' . $categoria . ' procesada con exito.</resultado>
              </soap:Body>
            </soap:Envelope>';
            
            socket_write($cliente, $respuesta_soap, strlen($respuesta_soap));
        } else {
            // Manejo de Error SOAP (SOAP Fault)
            $error_soap = '<?xml version="1.0"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
              <soap:Body>
                <soap:Fault>
                  <soap:Code><soap:Value>soap:Sender</soap:Value></soap:Code>
                  <soap:Reason><soap:Text xml:lang="es">Faltan parametros en la consulta</soap:Text></soap:Reason>
                </soap:Fault>
              </soap:Body>
            </soap:Envelope>';
            socket_write($cliente, $error_soap, strlen($error_soap));
        }
    }
    socket_close($cliente);
}
?>