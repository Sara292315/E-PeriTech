<?php
// ============================================================
//  E-PeriTech — Componente de Auditoría (Logger)
// ============================================================
//  GUIA #8 - Actividad 3: Implementación del Componente de Auditoría
//  Clase responsable de registrar eventos del sistema en un
//  archivo de auditoría persistente (audit.log).
// ============================================================

namespace App\Core;

class Logger
{
    // Tipos de evento permitidos
    const INFO    = 'INFO';
    const WARNING = 'WARNING';
    const ERROR   = 'ERROR';
    const AUDIT   = 'AUDIT';
    const AUTH    = 'AUTH';

    /**
     * Ruta base del proyecto (raíz donde está /logs/).
     * Se resuelve automáticamente desde la ubicación de este archivo.
     */
    private static string $logDir = '';
    private static string $logFile = 'audit.log';

    // ─────────────────────────────────────────────────────────
    //  MÉTODO PRINCIPAL: registrar()
    //  Escribe una línea en /logs/audit.log con el formato:
    //  [FECHA_HORA] [TIPO_EVENTO] [MENSAJE_DETALLADO]
    // ─────────────────────────────────────────────────────────
    public static function registrar(string $tipoEvento, string $mensaje): void
    {
        // 1. Resolver el directorio /logs/ relativo al proyecto
        $logDir = self::resolverDirectorioLog();

        // 2. Crear el directorio si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 3. Construir la línea de log con los 3 campos obligatorios
        $fechaHora   = date('Y-m-d H:i:s');
        $tipoEvento  = strtoupper(trim($tipoEvento));
        $lineaLog    = "[{$fechaHora}] [{$tipoEvento}] [{$mensaje}]" . PHP_EOL;

        // 4. Escribir en el archivo (append)
        $rutaArchivo = $logDir . DIRECTORY_SEPARATOR . self::$logFile;
        file_put_contents($rutaArchivo, $lineaLog, FILE_APPEND | LOCK_EX);
    }

    // ─────────────────────────────────────────────────────────
    //  MÉTODOS DE CONVENIENCIA (por tipo de evento)
    // ─────────────────────────────────────────────────────────

    /** Evento informativo general */
    public static function info(string $mensaje): void
    {
        self::registrar(self::INFO, $mensaje);
    }

    /** Advertencia no crítica */
    public static function warning(string $mensaje): void
    {
        self::registrar(self::WARNING, $mensaje);
    }

    /** Error del sistema o base de datos */
    public static function error(string $mensaje): void
    {
        self::registrar(self::ERROR, $mensaje);
    }

    /** Evento de auditoría de negocio (inserción, consulta, etc.) */
    public static function audit(string $mensaje): void
    {
        self::registrar(self::AUDIT, $mensaje);
    }

    /** Evento de autenticación (login, logout, acceso denegado) */
    public static function auth(string $mensaje): void
    {
        self::registrar(self::AUTH, $mensaje);
    }

    // ─────────────────────────────────────────────────────────
    //  UTILIDADES
    // ─────────────────────────────────────────────────────────

    /**
     * Devuelve el contenido del log como array de líneas.
     * Útil para vistas de administración.
     */
    public static function leerLog(): array
    {
        $ruta = self::resolverDirectorioLog() . DIRECTORY_SEPARATOR . self::$logFile;
        if (!file_exists($ruta)) {
            return [];
        }
        return array_filter(explode(PHP_EOL, file_get_contents($ruta)));
    }

    /**
     * Limpia el archivo de log (solo para entornos de prueba).
     */
    public static function limpiarLog(): void
    {
        $ruta = self::resolverDirectorioLog() . DIRECTORY_SEPARATOR . self::$logFile;
        if (file_exists($ruta)) {
            file_put_contents($ruta, '');
        }
    }

    /**
     * Resuelve la ruta absoluta de /logs/ navegando 3 niveles
     * hacia arriba desde /app/Core/Logger.php → raíz del proyecto.
     */
    private static function resolverDirectorioLog(): string
    {
        if (self::$logDir === '') {
            // __DIR__ = /ruta/al/proyecto/app/Core
            // dirname x2 → /ruta/al/proyecto
            self::$logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';
        }
        return self::$logDir;
    }
}
