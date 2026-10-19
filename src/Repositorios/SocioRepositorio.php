<?php
namespace Repositorios;

use PDO;
use Core\BaseDatos;
use Modelos\Socio;

/**
 * Repositorio de acceso a datos para la entidad Socio.
 * Capa: Acceso a datos.
 *
 * Todas las operaciones utilizan sentencias preparadas con enlace de parametros para evitar inyeccion SQL.
 */
class SocioRepositorio
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = BaseDatos::obtenerConexion();
    }

    /**
     * Busca un socio por su ID primario.
     */
    public function buscarPorId(int $id): ?Socio
    {
        $sql = "SELECT id, usuario_id, grupo_familiar_id, categoria_id, numero_socio,
                       nombre, apellido, dni, email, telefono, domicilio,
                       fecha_nacimiento, fecha_alta, estado, creado_en
                FROM socios 
                WHERE id = :id 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Socio::desdeArreglo($fila) : null;
    }

    /**
     * Busca un socio por el ID del usuario web vinculado.
     */
    public function buscarPorUsuarioId(int $usuarioId): ?Socio
    {
        $sql = "SELECT id, usuario_id, grupo_familiar_id, categoria_id, numero_socio,
                       nombre, apellido, dni, email, telefono, domicilio,
                       fecha_nacimiento, fecha_alta, estado, creado_en
                FROM socios 
                WHERE usuario_id = :usuario_id 
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return $fila ? Socio::desdeArreglo($fila) : null;
    }

    /**
     * Obtiene el listado de socios que deben recibir generacion de cuota (estados ACTIVO o MOROSO segun R6).
     *
     * @return array Arreglo asociativo con datos de socio, categoria y grupo familiar
     */
    public function buscarCandidatosGeneracionCuota(): array
    {
        $sql = "SELECT s.id, s.grupo_familiar_id, s.categoria_id, s.estado, s.nombre, s.apellido, s.numero_socio,
                       c.precio_cuota AS categoria_precio
                FROM socios s
                JOIN categorias c ON s.categoria_id = c.id
                WHERE s.estado IN ('ACTIVO', 'MOROSO') AND c.activo = 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Cuenta cuantos integrantes activos o morosos pertenecen a un grupo familiar determinado (Regla R5).
     */
    public function contarIntegrantesActivosGrupoFamiliar(int $grupoFamiliarId): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM socios
                WHERE grupo_familiar_id = :grupo_id 
                  AND estado IN ('ACTIVO', 'MOROSO')";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':grupo_id', $grupoFamiliarId, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return (int)($fila['total'] ?? 0);
    }

    /**
     * Actualiza el estado de un socio (Regla R1).
     */
    public function actualizarEstado(int $socioId, string $nuevoEstado): bool
    {
        $sql = "UPDATE socios SET estado = :estado WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':estado', $nuevoEstado, PDO::PARAM_STR);
        $sentencia->bindValue(':id', $socioId, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Busca socios por texto (DNI, apellido, nombre o numero de socio) y filtro de estado opcional.
     */
    public function buscar(string $termino = '', ?string $estado = null): array
    {
        $sql = "SELECT s.id, s.numero_socio, s.nombre, s.apellido, s.dni, s.email, s.telefono, 
                       s.domicilio, s.fecha_nacimiento, s.fecha_alta, s.estado,
                       c.nombre AS categoria_nombre, c.precio_cuota AS categoria_precio,
                       g.nombre AS grupo_familiar_nombre
                FROM socios s
                JOIN categorias c ON s.categoria_id = c.id
                LEFT JOIN grupos_familiares g ON s.grupo_familiar_id = g.id
                WHERE 1=1";

        $parametros = [];

        if (!empty($termino)) {
            $sql .= " AND (s.dni LIKE :termino_dni OR s.apellido LIKE :termino_ape OR s.nombre LIKE :termino_nom OR s.numero_socio LIKE :termino_num)";
            $busqueda = '%' . $termino . '%';
            $parametros[':termino_dni'] = $busqueda;
            $parametros[':termino_ape'] = $busqueda;
            $parametros[':termino_nom'] = $busqueda;
            $parametros[':termino_num'] = $busqueda;
        }

        if (!empty($estado)) {
            $sql .= " AND s.estado = :estado";
            $parametros[':estado'] = $estado;
        }

        $sql .= " ORDER BY s.apellido ASC, s.nombre ASC LIMIT 100";

        $sentencia = $this->conexion->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $sentencia->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        $sentencia->execute();

        return $sentencia->fetchAll();
    }

    /**
     * Obtiene el detalle consolidado de un socio para carnet o consulta general.
     */
    public function obtenerDetalleCompleto(int $id): ?array
    {
        $sql = "SELECT s.id, s.usuario_id, s.grupo_familiar_id, s.categoria_id, s.numero_socio,
                       s.nombre, s.apellido, s.dni, s.email, s.telefono, s.domicilio,
                       s.fecha_nacimiento, s.fecha_alta, s.estado, s.creado_en,
                       c.nombre AS categoria_nombre, c.precio_cuota,
                       g.nombre AS grupo_familiar_nombre
                FROM socios s
                JOIN categorias c ON s.categoria_id = c.id
                LEFT JOIN grupos_familiares g ON s.grupo_familiar_id = g.id
                WHERE s.id = :id
                LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        if (!$fila) {
            return null;
        }

        // Obtener divisiones en las que se encuentra inscripto
        $sqlDiv = "SELECT d.id, d.nombre, d.entrenador_id, u.nombre AS entrenador_nombre, u.apellido AS entrenador_apellido
                   FROM inscripciones i
                   JOIN divisiones d ON i.division_id = d.id
                   JOIN usuarios u ON d.entrenador_id = u.id
                   WHERE i.socio_id = :socio_id AND i.activo = 1 AND d.activo = 1";
        $sentenciaDiv = $this->conexion->prepare($sqlDiv);
        $sentenciaDiv->bindValue(':socio_id', $id, PDO::PARAM_INT);
        $sentenciaDiv->execute();

        $fila['divisiones'] = $sentenciaDiv->fetchAll();
        return $fila;
    }

    /**
     * Actualiza datos de contacto editables por el socio (Caso de uso Editar mis datos).
     */
    public function actualizarDatosContacto(int $socioId, string $email, ?string $telefono, ?string $domicilio): bool
    {
        $sql = "UPDATE socios 
                SET email = :email, telefono = :telefono, domicilio = :domicilio 
                WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':email', $email, PDO::PARAM_STR);
        $sentencia->bindValue(':telefono', $telefono, PDO::PARAM_STR);
        $sentencia->bindValue(':domicilio', $domicilio, PDO::PARAM_STR);
        $sentencia->bindValue(':id', $socioId, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Obtiene el siguiente numero de socio correlativo institucional (formato SOC-XXXX).
     */
    public function obtenerSiguienteNumeroSocio(): string
    {
        $sql = "SELECT numero_socio FROM socios WHERE numero_socio LIKE 'SOC-%' ORDER BY id DESC LIMIT 1";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $fila = $sentencia->fetch();

        if (!$fila || empty($fila['numero_socio'])) {
            return 'SOC-0001';
        }

        $numeroActual = (int)str_replace('SOC-', '', $fila['numero_socio']);
        $siguiente = $numeroActual + 1;
        return sprintf('SOC-%04d', $siguiente);
    }

    /**
     * Registra un nuevo socio en el sistema.
     */
    public function crear(Socio $socio): int
    {
        $sql = "INSERT INTO socios (
                    usuario_id, grupo_familiar_id, categoria_id, numero_socio,
                    nombre, apellido, dni, email, telefono, domicilio,
                    fecha_nacimiento, fecha_alta, estado, creado_en
                ) VALUES (
                    :usuario_id, :grupo_id, :categoria_id, :numero_socio,
                    :nombre, :apellido, :dni, :email, :telefono, :domicilio,
                    :fecha_nac, :fecha_alta, :estado, NOW()
                )";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':usuario_id', $socio->usuarioId, $socio->usuarioId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sentencia->bindValue(':grupo_id', $socio->grupoFamiliarId, $socio->grupoFamiliarId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sentencia->bindValue(':categoria_id', $socio->categoriaId, PDO::PARAM_INT);
        $sentencia->bindValue(':numero_socio', $socio->numeroSocio, PDO::PARAM_STR);
        $sentencia->bindValue(':nombre', $socio->nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':apellido', $socio->apellido, PDO::PARAM_STR);
        $sentencia->bindValue(':dni', $socio->dni, PDO::PARAM_STR);
        $sentencia->bindValue(':email', $socio->email, PDO::PARAM_STR);
        $sentencia->bindValue(':telefono', $socio->telefono, PDO::PARAM_STR);
        $sentencia->bindValue(':domicilio', $socio->domicilio, PDO::PARAM_STR);
        $sentencia->bindValue(':fecha_nac', $socio->fechaNacimiento, PDO::PARAM_STR);
        $sentencia->bindValue(':fecha_alta', $socio->fechaAlta ?: date('Y-m-d'), PDO::PARAM_STR);
        $sentencia->bindValue(':estado', $socio->estado, PDO::PARAM_STR);
        $sentencia->execute();

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Actualiza la ficha completa de un socio por Administracion.
     */
    public function actualizarAdministrativo(Socio $socio): bool
    {
        $sql = "UPDATE socios SET 
                    usuario_id = :usuario_id,
                    grupo_familiar_id = :grupo_id,
                    categoria_id = :categoria_id,
                    nombre = :nombre,
                    apellido = :apellido,
                    dni = :dni,
                    email = :email,
                    telefono = :telefono,
                    domicilio = :domicilio,
                    fecha_nacimiento = :fecha_nac,
                    estado = :estado
                WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':usuario_id', $socio->usuarioId, $socio->usuarioId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sentencia->bindValue(':grupo_id', $socio->grupoFamiliarId, $socio->grupoFamiliarId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sentencia->bindValue(':categoria_id', $socio->categoriaId, PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $socio->nombre, PDO::PARAM_STR);
        $sentencia->bindValue(':apellido', $socio->apellido, PDO::PARAM_STR);
        $sentencia->bindValue(':dni', $socio->dni, PDO::PARAM_STR);
        $sentencia->bindValue(':email', $socio->email, PDO::PARAM_STR);
        $sentencia->bindValue(':telefono', $socio->telefono, PDO::PARAM_STR);
        $sentencia->bindValue(':domicilio', $socio->domicilio, PDO::PARAM_STR);
        $sentencia->bindValue(':fecha_nac', $socio->fechaNacimiento, PDO::PARAM_STR);
        $sentencia->bindValue(':estado', $socio->estado, PDO::PARAM_STR);
        $sentencia->bindValue(':id', $socio->id, PDO::PARAM_INT);
        return $sentencia->execute();
    }

    /**
     * Busca un socio por DNI excluyendo un ID para control de duplicados.
     */
    public function buscarPorDni(string $dni, ?int $excluirId = null): ?Socio
    {
        $sql = "SELECT id, usuario_id, grupo_familiar_id, categoria_id, numero_socio,
                       nombre, apellido, dni, email, telefono, domicilio,
                       fecha_nacimiento, fecha_alta, estado, creado_en
                FROM socios WHERE dni = :dni";
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
        return $fila ? Socio::desdeArreglo($fila) : null;
    }

    /**
     * Busca un socio por email excluyendo un ID para control de duplicados.
     */
    public function buscarPorEmail(string $email, ?int $excluirId = null): ?Socio
    {
        $sql = "SELECT id, usuario_id, grupo_familiar_id, categoria_id, numero_socio,
                       nombre, apellido, dni, email, telefono, domicilio,
                       fecha_nacimiento, fecha_alta, estado, creado_en
                FROM socios WHERE email = :email";
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
        return $fila ? Socio::desdeArreglo($fila) : null;
    }

    /**
     * Lista todos los grupos familiares con el conteo de integrantes.
     */
    public function listarGruposFamiliares(): array
    {
        $sql = "SELECT g.id, g.nombre, g.creado_en,
                       COUNT(s.id) AS total_integrantes
                FROM grupos_familiares g
                LEFT JOIN socios s ON s.grupo_familiar_id = g.id
                GROUP BY g.id, g.nombre, g.creado_en
                ORDER BY g.nombre ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Crea un nuevo grupo familiar institucional.
     */
    public function crearGrupoFamiliar(string $nombre): int
    {
        $sql = "INSERT INTO grupos_familiares (nombre, creado_en) VALUES (:nombre, NOW())";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $sentencia->execute();
        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Lista los integrantes de un grupo familiar especifico.
     */
    public function obtenerIntegrantesGrupoFamiliar(int $grupoId): array
    {
        $sql = "SELECT s.id, s.numero_socio, s.nombre, s.apellido, s.dni, s.estado, c.nombre AS categoria_nombre
                FROM socios s
                JOIN categorias c ON s.categoria_id = c.id
                WHERE s.grupo_familiar_id = :grupo_id
                ORDER BY s.apellido ASC, s.nombre ASC";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':grupo_id', $grupoId, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->fetchAll();
    }

    /**
     * Asigna o desvincula un socio de un grupo familiar.
     */
    public function asignarGrupoFamiliar(int $socioId, ?int $grupoId): bool
    {
        $sql = "UPDATE socios SET grupo_familiar_id = :grupo_id WHERE id = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':grupo_id', $grupoId, $grupoId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sentencia->bindValue(':id', $socioId, PDO::PARAM_INT);
        return $sentencia->execute();
    }
}
