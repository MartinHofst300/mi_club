<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Categoria;

/**
 * Repositorio de acceso a datos para Categoria.
 * Capa: Acceso a datos.
 */
class CategoriaRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Busca una categoria por su ID.
     */
    public function buscarPorId(int $id): ?Categoria
    {
        $sql = "SELECT id, nombre, descripcion, edad_minima, edad_maxima, precio_cuota, activo
                FROM categorias 
                WHERE id = :id 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Categoria::desdeArreglo($fila) : null;
    }

    /**
     * Lista todas las categorias activas.
     */
    public function listarTodas(bool $soloActivas = true): array
    {
        $sql = "SELECT id, nombre, descripcion, edad_minima, edad_maxima, precio_cuota, activo
                FROM categorias";
        if ($soloActivas) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY edad_minima ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Actualiza el precio de cuota de una categoria (Caso de uso Gestionar precios).
     */
    public function actualizarPrecio(int $id, float $nuevoPrecio): bool
    {
        $sql = "UPDATE categorias SET precio_cuota = :precio WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':precio', $nuevoPrecio);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Registra una nueva categoria.
     */
    public function crear(Categoria $categoria): int
    {
        $sql = "INSERT INTO categorias (nombre, descripcion, edad_minima, edad_maxima, precio_cuota, activo)
                VALUES (:nombre, :descripcion, :edad_min, :edad_max, :precio, :activo)";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':nombre', $categoria->nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':descripcion', $categoria->descripcion, PDO::PARAM_STR);
        $sentencia->bindValue(':edad_min', $categoria->edadMinima, PDO::PARAM_INT);
        $sentencia->bindValue(':edad_max', $categoria->edadMaxima, PDO::PARAM_INT);
        $sentencia->bindValue(':precio', $categoria->precioCuota);
        $sentencia->bindValue(':activo', $categoria->activo ? 1 : 0, PDO::PARAM_INT);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Actualiza los datos de una categoria existente.
     */
    public function actualizar(Categoria $categoria): bool
    {
        $sql = "UPDATE categorias SET
                    nombre = :nombre,
                    descripcion = :descripcion,
                    edad_minima = :edad_min,
                    edad_maxima = :edad_max,
                    precio_cuota = :precio,
                    activo = :activo
                WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':nombre', $categoria->nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':descripcion', $categoria->descripcion, PDO::PARAM_STR);
        $sentencia->bindValue(':edad_min', $categoria->edadMinima, PDO::PARAM_INT);
        $sentencia->bindValue(':edad_max', $categoria->edadMaxima, PDO::PARAM_INT);
        $sentencia->bindValue(':precio', $categoria->precioCuota);
        $sentencia->bindValue(':activo', $categoria->activo ? 1 : 0, PDO::PARAM_INT);
        $sentencia->bindValue(':id', $categoria->id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Elimina fisicamente una categoria si no tiene restricciones.
     */
    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM categorias WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Cuenta la cantidad de socios asignados a una categoria.
     */
    public function contarSociosAsociados(int $id): int
    {
        $sql = "SELECT COUNT(*) AS total FROM socios WHERE categoria_id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        return (int)($fila['total'] ?? 0);
    }

    /**
     * Cuenta la cantidad de divisiones asignadas a una categoria.
     */
    public function contarDivisionesAsociadas(int $id): int
    {
        $sql = "SELECT COUNT(*) AS total FROM divisiones WHERE categoria_id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        return (int)($fila['total'] ?? 0);
    }

    /**
     * Comprueba si el rango de edad se solapa con alguna categoria activa existente.
     */
    public function buscarSuperposicionEdades(int $edadMin, int $edadMax, ?int $excluirId = null): array
    {
        $sql = "SELECT id, nombre, edad_minima, edad_maxima, precio_cuota 
                FROM categorias 
                WHERE activo = 1 
                  AND (:min <= edad_maxima AND :max >= edad_minima)";
        if ($excluirId !== null) {
            $sql .= " AND id != :excluir_id";
        }

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':min', $edadMin, PDO::PARAM_INT);
        $sentencia->bindValue(':max', $edadMax, PDO::PARAM_INT);
        if ($excluirId !== null) {
            $sentencia->bindValue(':excluir_id', $excluirId, PDO::PARAM_INT);
        }
        $sentencia->execute();

        return $sentencia->fetchAll();
    }
}
