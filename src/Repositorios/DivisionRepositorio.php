<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Division;

/**
 * Repositorio de acceso a datos para Divisiones e Inscripciones.
 * Capa: Acceso a datos.
 */
class DivisionRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Busca una division por su ID.
     */
    public function buscarPorId(int $id): ?Division
    {
        $sql = "SELECT id, categoria_id, entrenador_id, nombre, cupo_maximo, activo
                FROM divisiones 
                WHERE id = :id 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Division::desdeArreglo($fila) : null;
    }

    /**
     * Comprueba si un usuario es el entrenador asignado a una division.
     */
    public function esEntrenadorDeDivision(int $divisionId, int $entrenadorId): bool
    {
        $division = $this->buscarPorId($divisionId);
        return $division !== null && (int)$division->entrenadorId === $entrenadorId;
    }

    /**
     * Lista las divisiones asignadas a un entrenador particular.
     */
    public function listarPorEntrenador(int $entrenadorId): array
    {
        $sql = "SELECT d.id, d.categoria_id, d.entrenador_id, d.nombre, d.cupo_maximo, d.activo,
                       c.nombre AS categoria_nombre, c.edad_minima, c.edad_maxima,
                       (SELECT COUNT(*) FROM inscripciones i WHERE i.division_id = d.id AND i.activo = 1) AS inscriptos_actuales
                FROM divisiones d
                JOIN categorias c ON d.categoria_id = c.id
                WHERE d.entrenador_id = :entrenador_id AND d.activo = 1
                ORDER BY d.nombre ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':entrenador_id', $entrenadorId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Obtiene el listado de socios inscriptos activos en el plantel de una division (Regla R9).
     */
    public function obtenerPlantel(int $divisionId): array
    {
        $sql = "SELECT s.id AS socio_id, s.numero_socio, s.nombre, s.apellido, s.dni, s.fecha_nacimiento, s.estado,
                       i.id AS inscripcion_id, i.fecha_inscripcion
                FROM inscripciones i
                JOIN socios s ON i.socio_id = s.id
                WHERE i.division_id = :division_id AND i.activo = 1
                ORDER BY s.apellido ASC, s.nombre ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Lista todas las divisiones activas con informacion de cupo y categoria.
     */
    public function listarTodasConCupo(): array
    {
        $sql = "SELECT d.id, d.categoria_id, d.entrenador_id, d.nombre, d.cupo_maximo, d.activo,
                       c.nombre AS categoria_nombre, c.edad_minima, c.edad_maxima,
                       u.nombre AS entrenador_nombre, u.apellido AS entrenador_apellido,
                       (SELECT COUNT(*) FROM inscripciones i WHERE i.division_id = d.id AND i.activo = 1) AS inscriptos_actuales
                FROM divisiones d
                JOIN categorias c ON d.categoria_id = c.id
                JOIN usuarios u ON d.entrenador_id = u.id
                WHERE d.activo = 1
                ORDER BY c.edad_minima ASC, d.nombre ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Cuenta la cantidad de socios inscriptos activos en una division (Regla R8).
     */
    public function contarInscriptosActivos(int $divisionId): int
    {
        $sql = "SELECT COUNT(*) AS total 
                FROM inscripciones 
                WHERE division_id = :division_id AND activo = 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return (int)($fila['total'] ?? 0);
    }

    /**
     * Verifica si un socio ya se encuentra inscripto en la division.
     */
    public function estaInscripto(int $divisionId, int $socioId): bool
    {
        $sql = "SELECT COUNT(*) AS total 
                FROM inscripciones 
                WHERE division_id = :division_id AND socio_id = :socio_id AND activo = 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return ((int)($fila['total'] ?? 0)) > 0;
    }

    /**
     * Inscribe formalmente a un socio en una division (Regla R8).
     */
    public function inscribirSocio(int $divisionId, int $socioId, string $fecha): int
    {
        $sql = "INSERT INTO inscripciones (division_id, socio_id, fecha_inscripcion, activo)
                VALUES (:division_id, :socio_id, :fecha, 1)
                ON DUPLICATE KEY UPDATE activo = 1, fecha_inscripcion = VALUES(fecha_inscripcion)";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        $sentencia->bindValue(':fecha', $fecha, PDO::PARAM_STR);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Desactiva (da de baja) una inscripcion de un socio a una division.
     */
    public function desactivarInscripcion(int $divisionId, int $socioId): bool
    {
        $sql = "UPDATE inscripciones SET activo = 0 WHERE division_id = :division_id AND socio_id = :socio_id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':division_id', $divisionId, PDO::PARAM_INT);
        $sentencia->bindValue(':socio_id', $socioId, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Registra una nueva division deportiva.
     */
    public function crear(Division $division): int
    {
        $sql = "INSERT INTO divisiones (categoria_id, entrenador_id, nombre, cupo_maximo, activo)
                VALUES (:cat_id, :ent_id, :nombre, :cupo, :activo)";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':cat_id', $division->categoriaId, PDO::PARAM_INT);
        $sentencia->bindValue(':ent_id', $division->entrenadorId, PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $division->nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':cupo', $division->cupoMaximo, PDO::PARAM_INT);
        $sentencia->bindValue(':activo', $division->activo ? 1 : 0, PDO::PARAM_INT);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Actualiza los datos de una division existente.
     */
    public function actualizar(Division $division): bool
    {
        $sql = "UPDATE divisiones SET
                    categoria_id = :cat_id,
                    entrenador_id = :ent_id,
                    nombre = :nombre,
                    cupo_maximo = :cupo,
                    activo = :activo
                WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':cat_id', $division->categoriaId, PDO::PARAM_INT);
        $sentencia->bindValue(':ent_id', $division->entrenadorId, PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $division->nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':cupo', $division->cupoMaximo, PDO::PARAM_INT);
        $sentencia->bindValue(':activo', $division->activo ? 1 : 0, PDO::PARAM_INT);
        $sentencia->bindValue(':id', $division->id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Elimina fisicamente una division.
     */
    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM divisiones WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Cuenta todas las inscripciones (activas o historicas) asociadas a una division.
     */
    public function contarInscriptosTotales(int $divisionId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM inscripciones WHERE division_id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $divisionId, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        return (int)($fila['total'] ?? 0);
    }

    /**
     * Cuenta todas las sesiones de entrenamiento registradas para una division.
     */
    public function contarEntrenamientosAsociados(int $divisionId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM entrenamientos WHERE division_id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $divisionId, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        return (int)($fila['total'] ?? 0);
    }
}

