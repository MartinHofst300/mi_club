<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Validador;
use Servicios\AuthServicio;

/**
 * Controlador para la autenticacion de usuarios y gestion de sesiones.
 * Capa: Controladores.
 *
 * Responsabilidad: validar parametros de entrada, coordinar con AuthServicio y devolver JSON.
 */
class AuthControlador
{
    private AuthServicio $authServicio;

    public function __construct(?AuthServicio $authServicio = null)
    {
        $this->authServicio = $authServicio ?? new AuthServicio();
    }

    /**
     * Inicia sesion verificando credenciales del usuario.
     * Ruta: POST /api/auth/iniciar-sesion
     */
    public function iniciarSesion(Peticion $peticion): void
    {
        $datos = Validador::validar($peticion->getCuerpo(), [
            'identificador' => 'requerido',
            'password' => 'requerido'
        ]);

        $resultado = $this->authServicio->iniciarSesion(
            $datos['identificador'],
            $datos['password']
        );

        Respuesta::exito($resultado, 200);
    }

    /**
     * Cierra la sesion activa.
     * Ruta: POST /api/auth/cerrar-sesion
     */
    public function cerrarSesion(Peticion $peticion): void
    {
        $this->authServicio->cerrarSesion();
        Respuesta::exito(['mensaje' => 'Sesion cerrada exitosamente.'], 200);
    }

    /**
     * Obtiene los datos del usuario en la sesion activa.
     * Ruta: GET /api/auth/sesion
     */
    public function sesion(Peticion $peticion): void
    {
        $usuario = $this->authServicio->obtenerSesionActual();
        Respuesta::exito($usuario, 200);
    }
}
