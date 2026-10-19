<?php
namespace Core;

use Throwable;
use Excepciones\AppException;

/**
 * Manejador global de errores y excepciones no controladas.
 * Capa: Core del sistema.
 *
 * Registra detalles tecnicos en logs/errores.log y responde al cliente
 * con estructura JSON uniforme sin exponer trazas de ejecucion ni sentencias SQL.
 */
class ManejadorErrores
{
    /**
     * Registra el manejador de errores y de excepciones en el entorno PHP.
     */
    public static function registrar(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_error_handler([self::class, 'manejarErrorPhp']);
        set_exception_handler([self::class, 'manejarExcepcion']);
    }

    /**
     * Convierte errores nativos de PHP en excepciones de la aplicacion.
     */
    public static function manejarErrorPhp(int $nivel, string $mensaje, string $archivo, int $linea): bool
    {
        if (!(error_reporting() & $nivel)) {
            return false;
        }

        self::registrarEnLog("Error PHP [$nivel]: $mensaje en $archivo linea $linea");
        return true;
    }

    /**
     * Captura cualquier excepcion no controlada y emite la respuesta JSON estandar.
     */
    public static function manejarExcepcion(Throwable $e): void
    {
        // Revertir transaccion si quedo abierta por falla
        try {
            BaseDatos::revertirTransaccion();
        } catch (Throwable $t) {
            // Ignorar falla al intentar rollback si no habia conexion
        }

        if ($e instanceof AppException) {
            self::registrarEnLog(sprintf(
                "AppException [%s]: %s (Codigo HTTP: %d) en %s:%d\nDetalles: %s",
                $e->getCodigoError(),
                $e->getMessage(),
                $e->getCodigoHttp(),
                $e->getFile(),
                $e->getLine(),
                json_encode($e->getDetalles(), JSON_UNESCAPED_UNICODE)
            ));

            Respuesta::error(
                $e->getMessage(),
                $e->getCodigoHttp(),
                $e->getCodigoError(),
                $e->getDetalles()
            );
        }

        // Error inesperado o falla interna de base de datos
        self::registrarEnLog(sprintf(
            "Excepcion no controlada [%s]: %s en %s:%d\nTraza:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        Respuesta::error(
            'Ocurrio un error interno en el servidor. El incidente fue registrado.',
            500,
            'ERROR_INTERNO'
        );
    }

    /**
     * Escribe un mensaje en el archivo de log configurado.
     */
    public static function registrarEnLog(string $mensaje): void
    {
        $configuracion = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        $rutaLog = $configuracion['aplicacion']['ruta_logs'] ?? (dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'errores.log');

        $directorio = dirname($rutaLog);
        if (!is_dir($directorio)) {
            @mkdir($directorio, 0755, true);
        }

        $marcaTiempo = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $registro = sprintf("[%s] [%s] %s%s", $marcaTiempo, $ip, $mensaje, PHP_EOL);

        @file_put_contents($rutaLog, $registro, FILE_APPEND | LOCK_EX);
    }
}
