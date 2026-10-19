<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Entrenamiento;

/**
 * Repositorio de acceso a datos para Entrenamientos (Regla R9).
 * Capa: Acceso a datos.
 */
class EntrenamientoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Busca un entrenamiento por su ID.
     */
    public function buscarPorId(int $id): ?Entrenamiento
    {
        $sql = "SELECT id, division_id, entrenador_id, fecha, hora, lugar, observaciones, creado_en
                FROM entrenamientos 
                WHERE id = :id 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Entrenamiento::desdeArreglo($fila) : null;
    }

    /**
     * Obtiene el detalle consolidado de un entrenamiento con informacion de la division.
     */
    public function buscarDetalle(int $id): ?array
    {
        $sql = "SELECT e.id, e.division_id, e.entrenador_id, e.fecha, e.hora, e.lugar, e.observaciones, e.creado_en,
                       d.nombre AS division_nombre,
                       u.nombre AS entrenador_nombre, u.apellido AS entrenador_apellido
                FROM entrenamientos e
                JOIN divisiones d ON e.division_id = d.id
                JOIN usuarios u ON e.entrenador_id = u.id
                WHERE e.id = :id
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ?: null;
    }

    /**
     * Lista los entrenamientos pertenecientes a divisiones a cargo de un entrenador especifico (Regla R9).
     */
    public function listarPorEntrenador(int $entrenadorId): array
    {
        $sql = "SELECT e.id, e.division_id, e.entrenador_id, e.fecha, e.hora, e.lugar, e.observaciones,
                       d.nombre AS division_nombre,
                       (SELECT COUNT(*) FROM asistencias a WHERE a.entrenamiento_id = e.id) AS total_asistencias_cargadas
                FROM entrenamientos e
                JOIN divisiones d ON e.division_id = d.id
                WHERE d.entrenador_id = :entrenador_id
                ORDER BY e.fecha DESC, e.hora DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':entrenador_id', $entrenadorId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Lista todos los entrenamientos del club con datos de division y entrenador (Para ADMINISTRADOR).
     */
    public function listarTodos(): array
    {
        $sql = "SELECT e.id, e.division_id, e.entrenador_id, e.fecha, e.hora, e.lugar, e.observaciones,
                       d.nombre AS division_nombre,
                       u.nombre AS entrenador_nombre, u.apellido AS entrenador_apellido,
                       (SELECT COUNT(*) FROM asistencias a WHERE a.entrenamiento_id = e.id) AS total_asistencias_cargadas
                FROM entrenamientos e
                JOIN divisiones d ON e.division_id = d.id
                JOIN usuarios u ON e.entrenador_id = u.id
                ORDER BY e.fecha DESC, e.hora DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Registra una nueva sesion de entrenamiento.
     */
    public function crear(Entrenamiento $entrenamiento): int
    {
        $sql = "INSERT INTO entrenamientos (division_id, entrenador_id, fecha, hora, lugar, observaciones, creado_en)
                VALUES (:division_id, :entrenador_id, :fecha, :hora, :lugar, :observaciones, NOW())";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':division_id', $entrenamiento->divisionId, PDO::PARAM_INT);
        $sentencia->bindValue(':entrenador_id', $entrenamiento->entrenadorId, PDO::PARAM_INT);
        $sentencia->bindValue(':fecha', $entrenamiento->fecha, PDO::PARAM_STR);
        $sentencia->bindValue(':hora', $entrenamiento->hora, PDO::PARAM_STR);
        $sentencia->bindValue(':lugar', $entrenamiento->lugar, PDO::PARAM_STR);
        $sentencia->bindValue(':observaciones', $entrenamiento->observaciones, PDO::PARAM_STR);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Lista las sesiones de entrenamiento correspondientes a las divisiones donde el socio esta inscripto activo (Caso de uso Ver entrenamientos).
     */
    public function listarPorSocio(int $socioId): array
    {
        $sql = "SELECT e.id, e.division_id, e.entrenador_id, e.fecha, e.hora, e.lugar, e.observaciones,
                       d.nombre AS division_nombre,
                       u.nombre AS entrenador_nombre, u.apellido AS entrenador_apellido,
                       (SELECT a.estado FROM asistencias a WHERE a.entrenamiento_id = e.id AND a.socio_id = :socio_id_asist) AS mi_asistencia
                FROM entrenamientos e
                JOIN divisiones d ON e.division_id = d.id
                JOIN usuarios u ON e.entrenador_id = u.id
                JOIN inscripciones i ON (i.division_id = d.id AND i.socio_id = :socio_id_insc AND i.activo = 1)
                WHERE d.activo = 1
                ORDER BY e.fecha DESC, e.hora DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id_asist', $socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':socio_id_insc', $socioId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }
}

