<?php
namespace Servicios;

use Core\Sesion;
use Core\Csrf;
use Repositorios\UsuarioRepositorio;
use Repositorios\SocioRepositorio;
use Excepciones\NoAutorizadoException;
use Excepciones\ValidacionException;

/**
 * Servicio de Autenticacion y Control de Sesion.
 * Capa: Servicios de dominio.
 *
 * Responsable de validar credenciales, estados de cuenta activa y gestionar la sesion de PHP.
 */
class AuthServicio
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
     * Inicia la sesion de un usuario verificando credenciales, estado activo y rol.
     *
     * @param string $identificador Direccion de email o DNI
     * @param string $password Contrasena en texto plano
     * @return array Datos del usuario autenticado y token CSRF
     * @throws NoAutorizadoException Si las credenciales son incorrectas o el usuario/socio no esta habilitado
     */
    public function iniciarSesion(string $identificador, string $password): array
    {
        $identificador = trim($identificador);
        if (empty($identificador) || empty($password)) {
            throw new ValidacionException('Debe ingresar sus credenciales de acceso.');
        }

        $usuario = $this->usuarioRepo->buscarPorEmailODni($identificador);
        if (!$usuario) {
            throw new NoAutorizadoException('Las credenciales ingresadas son incorrectas.');
        }

        // Verificacion segura de contrasena con bcrypt
        if (!password_verify($password, $usuario['password_hash'])) {
            throw new NoAutorizadoException('Las credenciales ingresadas son incorrectas.');
        }

        // Verificar que el usuario no este deshabilitado
        if (!(bool)$usuario['activo']) {
            throw new NoAutorizadoException('Su cuenta de usuario se encuentra inactiva. Comuniquese con el club.');
        }

        // Si el usuario es de rol SOCIO, validar que no este en estado BAJA (Regla R1)
        $socioId = null;
        if ($usuario['rol_nombre'] === 'SOCIO') {
            $socio = $this->socioRepo->buscarPorUsuarioId((int)$usuario['id']);
            if ($socio) {
                if ($socio->estado === 'BAJA') {
                    throw new NoAutorizadoException('Su cuenta de socio figura en estado BAJA. No tiene permitido el acceso.');
                }
                $socioId = $socio->id;
            }
        }

        // Iniciar sesion y regenerar ID de sesion para evitar session fixation
        Sesion::iniciar();
        Sesion::regenerar();

        Sesion::establecer('usuario_id', (int)$usuario['id']);
        Sesion::establecer('rol_id', (int)$usuario['rol_id']);
        Sesion::establecer('rol_nombre', $usuario['rol_nombre']);
        Sesion::establecer('nombre', $usuario['nombre']);
        Sesion::establecer('apellido', $usuario['apellido']);
        Sesion::establecer('email', $usuario['email']);
        Sesion::establecer('socio_id', $socioId);
        Sesion::establecer('usuario_activo', 1);
        Sesion::establecer('ultimo_acceso', time());

        $tokenCsrf = Csrf::regenerar();

        return [
            'id' => (int)$usuario['id'],
            'rol_id' => (int)$usuario['rol_id'],
            'rol' => $usuario['rol_nombre'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'email' => $usuario['email'],
            'socio_id' => $socioId,
            'token_csrf' => $tokenCsrf
        ];
    }

    /**
     * Cierra la sesion activa del usuario y destruye la cookie asociada.
     */
    public function cerrarSesion(): void
    {
        Sesion::destruir();
    }

    /**
     * Devuelve el estado de la sesion actual junto con el token CSRF.
     */
    public function obtenerSesionActual(): ?array
    {
        Sesion::iniciar();
        $usuarioId = Sesion::obtener('usuario_id');

        if (!$usuarioId) {
            return null;
        }

        $rolNombre = Sesion::obtener('rol_nombre');
        $socioId = Sesion::obtener('socio_id');

        // Si es rol SOCIO y por alguna razon socio_id no estuviese en sesion, resolverlo desde el repositorio
        if ($rolNombre === 'SOCIO' && $socioId === null) {
            $socio = $this->socioRepo->buscarPorUsuarioId((int)$usuarioId);
            if ($socio) {
                $socioId = (int)$socio->id;
                Sesion::establecer('socio_id', $socioId);
            }
        }

        return [
            'id' => (int)$usuarioId,
            'rol_id' => (int)Sesion::obtener('rol_id'),
            'rol' => $rolNombre,
            'nombre' => Sesion::obtener('nombre'),
            'apellido' => Sesion::obtener('apellido'),
            'email' => Sesion::obtener('email'),
            'socio_id' => $socioId !== null ? (int)$socioId : null,
            'token_csrf' => Csrf::obtenerToken()
        ];
    }
}
