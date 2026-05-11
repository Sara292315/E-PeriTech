<?php
// GUÍA #8 DESARROLLO BÁSICO DE APLICACIONES: Lógica PHP implementada. Formulario contacto.php envía datos por POST y procesar.php los recibe usando variables y constantes.
// 1. CONSTANTES: Se pide usar define() para datos que no cambian.
define("NOMBRE_EMPRESA", "E-PeriTech");

// 2. RECEPCIÓN DE DATOS: Se pide usar $_POST y variables.
// Aquí atrapamos cada pedacito de información que viajó desde contacto.php
$nombre_usuario = $_POST['nombre'];
$correo_usuario = $_POST['correo'];  
$telefono_usuario = $_POST['telefono'];
$asunto_usuario = $_POST['asunto'];
$mensaje_usuario = $_POST['mensaje'];

// 3. OPERADORES Y LÓGICA: Se pide hacer cálculos o unir textos.
// Usamos el punto (.) para concatenar (unir) variables con texto normal.
$saludo = "¡Hola, " . $nombre_usuario . "! ";
$texto_agradecimiento = "Hemos recibido tu solicitud sobre '" . $asunto_usuario . "'. ";
$despedida = "El equipo de " . NOMBRE_EMPRESA . " te contactará pronto al " . $telefono_usuario . ".";

// Unimos todo en un solo gran mensaje:
$mensaje_final = $saludo . $texto_agradecimiento . $despedida;

// 4. RESPUESTA (echo): Mostramos el resultado en pantalla
echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);'>";
echo "<h2 style='color: #667eea;'>✅ ¡Formulario procesado en el servidor!</h2>";
echo "<p style='color: #333; line-height: 1.6;'><strong>Respuesta del sistema:</strong> <br>" . $mensaje_final . "</p>";
echo "<hr style='border: 1px solid #eee; margin: 20px 0;'>";
echo "<h3 style='color: #555;'>Resumen de los datos recibidos (Superglobal \$_POST):</h3>";
echo "<ul>";
echo "<li><strong>Nombre:</strong> " . $nombre_usuario . "</li>";
echo "<li><strong>Correo:</strong> " . $correo_usuario . "</li>";
echo "<li><strong>Asunto:</strong> " . $asunto_usuario . "</li>";
echo "<li><strong>Mensaje:</strong> " . $mensaje_usuario . "</li>";
echo "</ul>";
echo "<br>";
echo '<a href="contacto.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Volver a Contacto</a>';
echo "</div>";
?>