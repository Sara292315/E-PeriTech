<!--
//GUÍA #9: Lógica de control y validación de formularios PHP
  Requerimiento de control de flujo que indica que, 
  si la validación es exitosa, el sistema debe dirigir 
  al usuario a una sección de "Éxito".
-->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>¡Éxito! - E-PeriTech</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0fdf4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 25px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h1 style="color: #10b981; font-size: 50px; margin: 0;">✅</h1>
        <h2 style="color: #333;">¡Validación Exitosa!</h2>
        <p style="color: #666; font-size: 18px;">
            Hola <strong><?php echo htmlspecialchars($_GET['usuario'] ?? 'Cliente'); ?></strong>,<br>
            Tus datos han pasado todos los filtros de seguridad del servidor.
        </p>
        <a href="contacto.php" class="btn">Volver al inicio</a>
    </div>
</body>
</html>