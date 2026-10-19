<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Apercibimiento;

/**
 * Repositorio de acceso a datos para la entidad Apercibimiento (Regla R10).
 * Capa: Acceso a datos.
 */
class ApercibimientoRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Registra un nuevo apercibimiento disciplinario.
     */
    public function crear(Apercibimiento $apercibimiento): int
    {
        $sql = "INSERT INTO apercibimientos (socio_id, entrenador_id, fecha, gravedad, motivo, creado_en)
                VALUES (:socio_id, :entrenador_id, :fecha, :gravedad, :motivo, NOW())";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $apercibimiento->socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':entrenador_id', $apercibimiento->entrenadorId, PDO::PARAM_INT);
        $sentencia->bindValue(':fecha', $apercibimiento->fecha, PDO::PARAM_STR);
        $sentencia->bindValue(':gravedad', $apercibimiento->gravedad, PDO::PARAM_STR);
        $sentencia->bindValue(':motivo', $apercibimiento->motivo, PDO::PARAM_STR);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Lista los apercibimientos recibidos por un socio (visible para el socio, entrenador y administrador).
     */
    public function listarPorSocio(int $socioId): array
    {
        $sql = "SELECT a.id, a.socio_id, a.entrenador_id, a.fecha, a.gravedad, a.motivo, a.creado_en,
                       u.nombre AS entrenador_nombre, u.apellido AS entrenador_apellido
                FROM apercibimientos a
                JOIN usuarios u ON a.entrenador_id = u.id
                WHERE a.socio_id = :socio_id
                ORDER BY a.fecha DESC, a.id DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Lista los apercibimientos cargados por un entrenador particular.
     */
    public function listarPorEntrenador(int $entrenadorId): array
    {
        $sql = "SELECT a.id, a.socio_id, a.entrenador_id, a.fecha, a.gravedad, a.motivo, a.creado_en,
                       s.numero_socio, s.nombre AS socio_nombre, s.apellido AS socio_apellido, s.dni AS socio_dni
                FROM apercibimientos a
                JOIN socios s ON a.socio_id = s.id
                WHERE a.entrenador_id = :entrenador_id
                ORDER BY a.fecha DESC, a.id DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':entrenador_id', $entrenadorId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Lista todos los apercibimientos institucionales registrados (Para ADMINISTRADOR).
     */
    public function listarTodos(): array
    {
        $sql = "SELECT a.id, a.socio_id, a.entrenador_id, a.fecha, a.gravedad, a.motivo, a.creado_en,
                       s.numero_socio, s.nombre AS socio_nombre, s.apellido AS socio_apellido, s.dni AS socio_dni,
                       u.nombre AS entrenador_nombre, u.apellido AS entrenador_apellido
                FROM apercibimientos a
                JOIN socios s ON a.socio_id = s.id
                JOIN usuarios u ON a.entrenador_id = u.id
                ORDER BY a.fecha DESC, a.id DESC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }
}
