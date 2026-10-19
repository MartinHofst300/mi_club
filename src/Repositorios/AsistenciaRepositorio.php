<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;

/**
 * Repositorio de acceso a datos para Asistencias a entrenamientos (Regla R9).
 * Capa: Acceso a datos.
 */
class AsistenciaRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Obtiene la nomina completa de socios del plantel para un entrenamiento,
     * incluyendo el estado de asistencia actual si ya fue registrado previamente.
     */
    public function obtenerPlanillaEntrenamiento(int $entrenamientoId, int $divisionId): array
    {
        $sql = "SELECT s.id AS socio_id, s.numero_socio, s.nombre, s.apellido, s.dni,
                       a.id AS asistencia_id, 
                       COALESCE(a.estado, 'PRESENTE') AS estado,
                       a.observaciones
                FROM inscripciones i
                JOIN socios s ON i.socio_id = s.id
                LEFT JOIN asistencias a ON (a.entrenamiento_id = :entrenamiento_id AND a.socio_id = s.id)
                WHERE i.division_id = :division_id AND i.activo = 1
                ORDER BY s.apellido ASC, s.nombre ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':entrenamiento_id', $entrenamientoId, PDO::PARAM_INT);
        $sentencia->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Registra o actualiza la asistencia de un socio a un entrenamiento de forma idempotente (Regla R9).
     * En caso de existir registro previo, actualiza el estado y observacion sin duplicar filas.
     */
    public function guardarOActualizar(int $entrenamientoId, int $socioId, string $estado, ?string $observaciones = null): bool
    {
        $sql = "INSERT INTO asistencias (entrenamiento_id, socio_id, estado, observaciones)
                VALUES (:entrenamiento_id, :socio_id, :estado, :observaciones)
                ON DUPLICATE KEY UPDATE 
                    estado = VALUES(estado), 
                    observaciones = VALUES(observaciones)";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':entrenamiento_id', $entrenamientoId, PDO::PARAM_INT);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':estado', $estado, PDO::PARAM_STR);
        $sentencia->bindValue(':observaciones', $observaciones, PDO::PARAM_STR);
        return $sentencia->execute();
    }

    /**
     * Obtiene el historial de asistencias de un socio a lo largo del tiempo.
     */
    public function listarPorSocio(int $socioId): array
    {
        $sql = "SELECT a.id, a.entrenamiento_id, a.estado, a.observaciones,
                       e.fecha, e.hora, e.lugar, d.nombre AS division_nombre
                FROM asistencias a
                JOIN entrenamientos e ON a.entrenamiento_id = e.id
                JOIN divisiones d ON e.division_id = d.id
                WHERE a.socio_id = :socio_id
                ORDER BY e.fecha DESC, e.hora DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }
}
