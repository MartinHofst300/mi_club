<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Sesion;
use Core\Validador;
use Servicios\UsuarioServicio;

/**
 * Controlador de Gestion de Usuarios y Roles.
 * Capa: Controladores.
 */
class UsuarioControlador
{
    private UsuarioServicio $usuarioServicio;

    public function __construct(?UsuarioServicio $usuarioServicio = null)
    {
        $this->usuarioServicio = $usuarioServicio ?? new UsuarioServicio();
    }

    /**
     * Lista usuarios registrados.
     * Ruta: GET /api/usuarios
     */
    public function listar(Peticion $peticion): void
    {
        $termino = $peticion->obtener('termino', '');
        $rolId = $peticion->obtener('rol_id') ? (int)$peticion->obtener('rol_id') : null;

        $usuarios = $this->usuarioServicio->listar($termino, $rolId);
        Respuesta::exito($usuarios, 200);
    }

    /**
     * Lista roles del sistema.
     * Ruta: GET /api/roles
     */
    public function listarRoles(Peticion $peticion): void
    {
        $roles = $this->usuarioServicio->listarRoles();
        Respuesta::exito($roles, 200);
    }

    /**
     * Obtiene el detalle de un usuario.
     * Ruta: GET /api/usuarios/{id}
     */
    public function obtener(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $usuario = $this->usuarioServicio->obtenerPorId($id);
        Respuesta::exito($usuario, 200);
    }

    /**
     * Registra un nuevo usuario.
     * Ruta: POST /api/usuarios
     */
    public function crear(Peticion $peticion): void
    {
        $datos = Validador::validar($peticion->getCuerpo(), [
            'nombre' => 'requerido',
            'apellido' => 'requerido',
            'email' => 'requerido|email',
            'dni' => 'requerido',
            'password' => 'requerido|min:6',
            'rol_id' => 'requerido|entero'
        ]);

        $resultado = $this->usuarioServicio->crear($datos);
        Respuesta::exito($resultado, 201);
    }

    /**
     * Actualiza un usuario existente.
     * Ruta: PUT /api/usuarios/{id}
     */
    public function actualizar(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $usuarioAutenticadoId = (int)Sesion::obtener('usuario_id');

        $datos = Validador::validar($peticion->getCuerpo(), [
            'nombre' => 'requerido',
            'apellido' => 'requerido',
            'email' => 'requerido|email',
            'dni' => 'requerido',
            'rol_id' => 'requerido|entero'
        ]);

        $cuerpoCompleto = $peticion->getCuerpo();
        if (isset($cuerpoCompleto['confirmar_cambio_rol_socio'])) {
            $datos['confirmar_cambio_rol_socio'] = (bool)$cuerpoCompleto['confirmar_cambio_rol_socio'];
        }
        if (isset($cuerpoCompleto['activo'])) {
            $datos['activo'] = (bool)$cuerpoCompleto['activo'];
        }

        $resultado = $this->usuarioServicio->actualizar($id, $datos, $usuarioAutenticadoId);
        Respuesta::exito($resultado, 200);
    }

    /**
     * Activa un usuario.
     * Ruta: POST /api/usuarios/{id}/activar
     */
    public function activar(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $usuarioAutenticadoId = (int)Sesion::obtener('usuario_id');

        $resultado = $this->usuarioServicio->cambiarEstadoActivo($id, true, $usuarioAutenticadoId);
        Respuesta::exito($resultado, 200);
    }

    /**
     * Desactiva un usuario.
     * Ruta: POST /api/usuarios/{id}/desactivar
     */
    public function desactivar(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $usuarioAutenticadoId = (int)Sesion::obtener('usuario_id');

        $resultado = $this->usuarioServicio->cambiarEstadoActivo($id, false, $usuarioAutenticadoId);
        Respuesta::exito($resultado, 200);
    }

    /**
     * Restablece la clave de un usuario.
     * Ruta: POST /api/usuarios/{id}/restablecer-password
     */
    public function restablecerPassword(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $usuarioAutenticadoId = (int)Sesion::obtener('usuario_id');
        $cuerpo = $peticion->getCuerpo();

        $clave = $cuerpo['password'] ?? $cuerpo['nueva_clave'] ?? null;
        if (empty($clave) || strlen((string)$clave) < 6) {
            throw new \Excepciones\ValidacionException('La nueva contrasena es requerida y debe contener al menos 6 caracteres.');
        }

        $resultado = $this->usuarioServicio->restablecerPassword($id, (string)$clave, $usuarioAutenticadoId);
        Respuesta::exito($resultado, 200);
    }
}
