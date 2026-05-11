<?php
// GUÍA #8 DESARROLLO BÁSICO DE APLICACIONES: Lógica PHP implementada. Formulario contacto.php envía datos por POST y procesar.php los recibe usando variables y constantes.
// GUÍA #9 - Validación y Filtros de Seguridad:
// Usamos trim() para quitar espacios vacíos y filter_var() para sanear el correo.
$nombre_usuario = trim($_POST['nombre']);
$correo_usuario = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
$telefono_usuario = trim($_POST['telefono']);
$asunto_usuario = $_POST['asunto'];
$mensaje_usuario = trim($_POST['mensaje']);

// GUÍA 9 - Implementación de lógica de control (Condicionales):
// Verificamos si los campos están vacíos o si el correo es inválido.
if (empty($nombre_usuario) || empty($correo_usuario) || empty($mensaje_usuario) || !filter_var($correo_usuario, FILTER_VALIDATE_EMAIL)) {
    
    // GUÍA 9 - Control de Flujo y Redireccionamiento (Error):
    // Si falla, lo devolvemos al formulario enviando una variable 'error' por la URL.
    header("Location: contacto.php?error=datos_invalidos");
    exit(); 

} else {
    // GUÍA 9 - Control de Flujo y Redireccionamiento (Éxito):
    // Si todo está correcto, lo enviamos a la página de éxito.
    header("Location: exito.php?usuario=" . urlencode($nombre_usuario));
    exit();
}
?>