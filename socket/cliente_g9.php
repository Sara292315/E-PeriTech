<?php
// 1. DESCUBRIMIENTO UDDI: Lee el archivo JSON en la misma carpeta
$directorio = file_get_contents('uddi.json');
$datos_uddi = json_decode($directorio, true);

// Extrae la IP (127.0.0.1)
$host = $datos_uddi["servicios"]["EPeriTech_Consulta"]; 
$puerto = 8080;

echo "1. UDDI: Servicio EPeriTech encontrado en la IP: $host\n";

// 2. EMPAQUETADO SOAP: Metemos los datos en el sobre estándar
$mensaje_soap = '<?xml version="1.0"?>
<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:eperi="http://eperitech.fesc.edu">
  <soap:Header/>
  <soap:Body>
    <eperi:consulta_producto>
      <categoria>teclados</categoria>
      <marca>logitech</marca>
    </eperi:consulta_producto>
  </soap:Body>
</soap:Envelope>';

// 3. ENVÍO POR SOCKETS
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (socket_connect($socket, $host, $puerto)) {
    echo "2. Enviando Peticion estructurada en SOAP...\n";
    socket_write($socket, $mensaje_soap, strlen($mensaje_soap));
    
    // Leer lo que responde el servidor
    $respuesta = socket_read($socket, 2048);
    echo "\n3. Respuesta SOAP del Servidor:\n" . $respuesta . "\n";
    socket_close($socket);
} else {
    echo "Error: No se pudo conectar al servidor en $host:$puerto\n";
}
?>