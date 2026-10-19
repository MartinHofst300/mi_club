<?php
namespace Core;

use PDO;
use PDOException;
use Excepciones\AppException;

/**
 * Gestor de conexion a la base de datos MariaDB / MySQL mediante PDO.
 * Capa: Core del sistema.
 *
 * Implementa patron Singleton y asegura configuracion de sentencias preparadas
 * y manejo estricto de transacciones para cumplir con ACID.
 */
class BaseDatos
{
    private static ?PDO $instancia = null;

    private function __construct()
    {
    }

    /**
     * Obtiene la conexion PDO activa o crea una nueva si no existe.
     *
     * @return PDO
     * @throws AppException En caso de falla en la conexion
     */
    public static function obtenerConexion(): PDO
    {
        if (self::$instancia === null) {
            $configuracion = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            $bd = $configuracion['base_datos'];

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $bd['host'],
                $bd['puerto'],
                $bd['nombre'],
                $bd['charset']
            );

            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instancia = new PDO($dsn, $bd['usuario'], $bd['clave'], $opciones);
            } catch (PDOException $e) {
                // No exponer credenciales ni DSN en el mensaje al usuario
                throw new AppException('Error al conectar con la base de datos: ' . $e->getMessage(), 500, 'ERROR_BASE_DATOS');
            }
        }

        return self::$instancia;
    }

    /**
     * Inicia una transaccion si no hay una activa.
     */
    public static function iniciarTransaccion(): void
    {
        $conexion = self::obtenerConexion();
        if (!$conexion->inTransaction()) {
            $conexion->beginTransaction();
        }
    }

    /**
     * Confirma la transaccion activa.
     */
    public static function confirmarTransaccion(): void
    {
        $conexion = self::obtenerConexion();
        if ($conexion->inTransaction()) {
            $conexion->commit();
        }
    }

    /**
     * Revierte la transaccion activa ante cualquier error.
     */
    public static function revertirTransaccion(): void
    {
        $conexion = self::obtenerConexion();
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
    }
}
