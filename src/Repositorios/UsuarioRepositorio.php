<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Usuario;

/**
 * Repositorio de acceso a datos para la entidad Usuario.
 * Capa: Acceso a datos.
 *
 * Ejecuta unicamente consultas SQL preparadas con PDO sin contener logica de negocio.
 */
class UsuarioRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Busca un usuario por su clave primaria.
     */
    public function buscarPorId(int $id): ?Usuario
    {
        $sql = "SELECT id, rol_id, nombre, apellido, email, dni, password_hash, activo, creado_en 
                FROM usuarios 
                WHERE id = :id 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Usuario::desdeArreglo($fila) : null;
    }

    /**
     * Busca un usuario por email o DNI para la autenticacion, incluyendo el nombre de su rol.
     */
    public function buscarPorEmailODni(string $identificador): ?array
    {
        $sql = "SELECT u.id, u.rol_id, r.nombre AS rol_nombre, u.nombre, u.apellido, 
                       u.email, u.dni, u.password_hash, u.activo, u.creado_en,
                       s.id AS socio_id
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                LEFT JOIN socios s ON s.usuario_id = u.id
                WHERE (u.email = :identificador_email OR u.dni = :identificador_dni)
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':identificador_email', $identificador, PDO::PARAM_STR);
        $sentencia->bindValue(':identificador_dni', $identificador, PDO::PARAM_STR);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ?: null;
    }

    /**
     * Actualiza la contrasena de un usuario con un nuevo hash bcrypt.
     */
    public function actualizarPassword(int $id, string $nuevoHash): bool
    {
        $sql = "UPDATE usuarios SET password_hash = :hash WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':hash', $nuevoHash, PDO::PARAM_STR);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Lista usuarios con filtros opcionales de busqueda y rol.
     */
    public function listar(?string $termino = '', ?int $rolId = null): array
    {
        $sql = "SELECT u.id, u.rol_id, r.nombre AS rol_nombre, u.nombre, u.apellido, 
                       u.email, u.dni, u.activo, u.creado_en,
                       s.id AS socio_id, s.numero_socio
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                LEFT JOIN socios s ON s.usuario_id = u.id
                WHERE 1=1";

        $parametros = [];

        if (!empty($termino)) {
            $sql .= " AND (u.nombre LIKE :term_nom OR u.apellido LIKE :term_ape OR u.email LIKE :term_em OR u.dni LIKE :term_dni)";
            $busqueda = '%' . $termino . '%';
            $parametros[':term_nom'] = $busqueda;
            $parametros[':term_ape'] = $busqueda;
            $parametros[':term_em'] = $busqueda;
            $parametros[':term_dni'] = $busqueda;
        }

        if ($rolId !== null && $rolId > 0) {
            $sql .= " AND u.rol_id = :rol_id";
            $parametros[':rol_id'] = $rolId;
        }

        $sql .= " ORDER BY u.apellido ASC, u.nombre ASC";

        $sentencia = $this->conexion->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $sentencia->bindValue($clave, $valor);
        }
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Inserta un nuevo usuario en la base de datos.
     */
    public function crear(Usuario $usuario): int
    {
        $sql = "INSERT INTO usuarios (rol_id, nombre, apellido, email, dni, password_hash, activo, creado_en)
                VALUES (:rol_id, :nombre, :apellido, :email, :dni, :hash, :activo, NOW())";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':rol_id', $usuario->rolId, PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $usuario->nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':apellido', $usuario->apellido, PDO::PARAM_STR);
        $sentencia->bindValue(':email', $usuario->email, PDO::PARAM_STR);
        $sentencia->bindValue(':dni', $usuario->dni, PDO::PARAM_STR);
        $sentencia->bindValue(':hash', $usuario->passwordHash, PDO::PARAM_STR);
        $sentencia->bindValue(':activo', $usuario->activo ? 1 : 0, PDO::PARAM_INT);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Actualiza los datos generales y el rol de un usuario.
     */
    public function actualizar(int $id, int $rolId, string $nombre, string $apellido, string $email, string $dni, bool $activo): bool
    {
        $sql = "UPDATE usuarios 
                SET rol_id = :rol_id, nombre = :nombre, apellido = :apellido, email = :email, dni = :dni, activo = :activo
                WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':rol_id', $rolId, PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':apellido', $apellido, PDO::PARAM_STR);
        $sentencia->bindValue(':email', $email, PDO::PARAM_STR);
        $sentencia->bindValue(':dni', $dni, PDO::PARAM_STR);
        $sentencia->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function actualizarEstadoActivo(int $id, bool $activo): bool
    {
        $sql = "UPDATE usuarios SET activo = :activo WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Busca usuario por email excluyendo un ID para comprobaciones de unicidad.
     */
    public function buscarPorEmail(string $email, ?int $excluirId = null): ?Usuario
    {
        $sql = "SELECT id, rol_id, nombre, apellido, email, dni, password_hash, activo, creado_en 
                FROM usuarios 
                WHERE email = :email";
        if ($excluirId !== null) {
            $sql .= " AND id != :excluir_id";
        }
        $sql .= " LIMIT 1";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':email', $email, PDO::PARAM_STR);
        if ($excluirId !== null) {
            $sentencia->bindValue(':excluir_id', $excluirId, PDO::PARAM_INT);
        }
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Usuario::desdeArreglo($fila) : null;
    }

    /**
     * Busca usuario por DNI excluyendo un ID para comprobaciones de unicidad.
     */
    public function buscarPorDni(string $dni, ?int $excluirId = null): ?Usuario
    {
        $sql = "SELECT id, rol_id, nombre, apellido, email, dni, password_hash, activo, creado_en 
                FROM usuarios 
                WHERE dni = :dni";
        if ($excluirId !== null) {
            $sql .= " AND id != :excluir_id";
        }
        $sql .= " LIMIT 1";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':dni', $dni, PDO::PARAM_STR);
        if ($excluirId !== null) {
            $sentencia->bindValue(':excluir_id', $excluirId, PDO::PARAM_INT);
        }
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Usuario::desdeArreglo($fila) : null;
    }

    /**
     * Lista todos los roles disponibles en el sistema.
     */
    public function listarRoles(): array
    {
        $sql = "SELECT id, nombre, descripcion FROM roles ORDER BY id ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }
}
