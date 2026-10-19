<?php
namespace Servicios;

use Repositorios\UsuarioRepositorio;
use Repositorios\SocioRepositorio;
use Modelos\Usuario;
use Excepciones\ReglaNegocioException;
use Excepciones\NoEncontradoException;
use Excepciones\ValidacionException;
use Excepciones\ConflictoException;

/**
 * Servicio de Gestion de Usuarios y Roles (Caso de uso Administrador).
 * Capa: Servicios de dominio.
 */
class UsuarioServicio
{
    private UsuarioRepositorio $usuarioRepo;
    private SocioRepositorio $socioRepo;

    public function __construct(
        ?UsuarioRepositorio $usuarioRepo = null,
        ?SocioRepositorio $socioRepo = null
    ) {
        $this->usuarioRepo = $usuarioRepo ?? new UsuarioRepositorio();
        $this->socioRepo = $socioRepo ?? new SocioRepositorio();
    }

    /**
     * Lista todos los usuarios con filtros opcionales.
     */
    public function listar(?string $termino = '', ?int $rolId = null): array
    {
        return $this->usuarioRepo->listar($termino, $rolId);
    }

    /**
     * Obtiene el detalle de un usuario por su ID.
     */
    public function obtenerPorId(int $id): array
    {
        $usuario = $this->usuarioRepo->buscarPorId($id);
        if (!$usuario) {
            throw new NoEncontradoException('Usuario no encontrado.');
        }

        $arr = $usuario->aArreglo(false);
        $socio = $this->socioRepo->buscarPorUsuarioId($id);
        $arr['socio_vinculado'] = $socio ? [
            'id' => $socio->id,
            'numero_socio' => $socio->numeroSocio,
            'nombre' => $socio->nombre . ' ' . $socio->apellido
        ] : null;

        return $arr;
    }

    /**
     * Registra un nuevo usuario en el sistema.
     */
    public function crear(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $apellido = trim($datos['apellido'] ?? '');
        $email = trim(strtolower($datos['email'] ?? ''));
        $dni = trim($datos['dni'] ?? '');
        $password = $datos['password'] ?? '';
        $rolId = (int)($datos['rol_id'] ?? 4);
        $activo = isset($datos['activo']) ? (bool)$datos['activo'] : true;

        if (empty($nombre) || empty($apellido) || empty($email) || empty($dni) || empty($password)) {
            throw new ValidacionException('Todos los campos son obligatorios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidacionException('El formato de correo electronico es invalido.');
        }

        if (strlen($password) < 6) {
            throw new ValidacionException('La contrasena debe tener al menos 6 caracteres.');
        }

        // Comprobar unicidad de email
        if ($this->usuarioRepo->buscarPorEmail($email)) {
            throw new ConflictoException('Ya existe un usuario registrado con el correo electronico ingresado.');
        }

        // Comprobar unicidad de DNI
        if ($this->usuarioRepo->buscarPorDni($dni)) {
            throw new ConflictoException('Ya existe un usuario registrado con el DNI ingresado.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $usuario = new Usuario(
            null,
            $rolId,
            $nombre,
            $apellido,
            $email,
            $dni,
            $hash,
            $activo
        );

        $id = $this->usuarioRepo->crear($usuario);

        return [
            'id' => $id,
            'mensaje' => 'Usuario creado exitosamente.'
        ];
    }

    /**
     * Actualiza la informacion y el rol de un usuario existente.
     */
    public function actualizar(int $id, array $datos, int $usuarioAutenticadoId): array
    {
        $usuario = $this->usuarioRepo->buscarPorId($id);
        if (!$usuario) {
            throw new NoEncontradoException('Usuario no encontrado.');
        }

        $nombre = trim($datos['nombre'] ?? $usuario->nombre);
        $apellido = trim($datos['apellido'] ?? $usuario->apellido);
        $email = trim(strtolower($datos['email'] ?? $usuario->email));
        $dni = trim($datos['dni'] ?? $usuario->dni);
        $rolId = isset($datos['rol_id']) ? (int)$datos['rol_id'] : $usuario->rolId;
        $activo = isset($datos['activo']) ? (bool)$datos['activo'] : $usuario->activo;

        // Regla: Un administrador no puede desactivarse ni quitarse el rol a si mismo
        if ($id === $usuarioAutenticadoId) {
            if (!$activo && $usuario->activo) {
                throw new ReglaNegocioException('Un administrador no puede desactivar su propia cuenta de acceso.');
            }
            if ($rolId !== 1 && $usuario->rolId === 1) {
                throw new ReglaNegocioException('Un administrador no puede removerse a si mismo el rol de administrador.');
            }
        }

        // Regla: Un usuario ligado a un socio no cambia a un rol incompatible sin avisar
        if ($rolId !== $usuario->rolId) {
            $socio = $this->socioRepo->buscarPorUsuarioId($id);
            if ($socio && $rolId !== 4) {
                // Si esta ligado a un socio y se pretende cambiar a un rol administrativo o de entrenador
                if (empty($datos['confirmar_cambio_rol_socio'])) {
                    throw new ReglaNegocioException(
                        "El usuario esta vinculado al legajo del socio {$socio->numeroSocio} ({$socio->nombre} {$socio->apellido}). " .
                        "Cambiar su rol a una funcion distinta de SOCIO puede causar incompatibilidad con el portal. Confirme la accion explicitamente."
                    );
                }
            }
        }

        // Validar unicidad de email
        $existenteEmail = $this->usuarioRepo->buscarPorEmail($email, $id);
        if ($existenteEmail) {
            throw new ConflictoException('El correo electronico ya se encuentra en uso por otro usuario.');
        }

        // Validar unicidad de DNI
        $existenteDni = $this->usuarioRepo->buscarPorDni($dni, $id);
        if ($existenteDni) {
            throw new ConflictoException('El DNI ya se encuentra en uso por otro usuario.');
        }

        $this->usuarioRepo->actualizar($id, $rolId, $nombre, $apellido, $email, $dni, $activo);

        return [
            'id' => $id,
            'mensaje' => 'Usuario actualizado exitosamente.'
        ];
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function cambiarEstadoActivo(int $id, bool $activo, int $usuarioAutenticadoId): array
    {
        $usuario = $this->usuarioRepo->buscarPorId($id);
        if (!$usuario) {
            throw new NoEncontradoException('Usuario no encontrado.');
        }

        if ($id === $usuarioAutenticadoId && !$activo) {
            throw new ReglaNegocioException('Un administrador no puede desactivar su propia cuenta de acceso.');
        }

        $this->usuarioRepo->actualizarEstadoActivo($id, $activo);

        $accion = $activo ? 'activado' : 'desactivado';
        return [
            'id' => $id,
            'activo' => $activo,
            'mensaje' => "Usuario {$accion} exitosamente."
        ];
    }

    /**
     * Restablece la contrasena de un usuario.
     */
    public function restablecerPassword(int $id, string $nuevaPassword, int $usuarioAutenticadoId): array
    {
        $usuario = $this->usuarioRepo->buscarPorId($id);
        if (!$usuario) {
            throw new NoEncontradoException('Usuario no encontrado.');
        }

        if (strlen($nuevaPassword) < 6) {
            throw new ValidacionException('La nueva contrasena debe tener al menos 6 caracteres.');
        }

        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
        $this->usuarioRepo->actualizarPassword($id, $hash);

        return [
            'id' => $id,
            'mensaje' => 'Contrasena restablecida exitosamente.'
        ];
    }

    /**
     * Lista todos los roles disponibles.
     */
    public function listarRoles(): array
    {
        return $this->usuarioRepo->listarRoles();
    }
}
