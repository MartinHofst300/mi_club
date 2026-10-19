<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Sesion;
use Core\Validador;
use Servicios\ApercibimientoServicio;

/**
 * Controlador de Apercibimientos disciplinarios (Regla R10).
 * Capa: Controladores.
 */
class ApercibimientoControlador
{
    private ApercibimientoServicio $apercibimientoServicio;

    public function __construct(?ApercibimientoServicio $apercibimientoServicio = null)
    {
        $this->apercibimientoServicio = $apercibimientoServicio ?? new ApercibimientoServicio();
    }

    /**
     * Carga un nuevo apercibimiento disciplinario (Caso de uso Cargar apercibimiento).
     * Ruta: POST /api/apercibimientos
     */
    public function crear(Peticion $peticion): void
    {
        Sesion::iniciar();
        $entrenadorUsuarioId = (int)Sesion::obtener('usuario_id');

        $datos = Validador::validar($peticion->getCuerpo(), [
            'socio_id' => 'requerido|entero|positivo',
            'fecha' => 'requerido|fecha',
            'gravedad' => 'requerido|enum:LEVE,GRAVE',
            'motivo' => 'requerido|min:5|max:500'
        ]);

        $resultado = $this->apercibimientoServicio->registrarApercibimiento(
            (int)$datos['socio_id'],
            $entrenadorUsuarioId,
            $datos['fecha'],
            $datos['gravedad'],
            $datos['motivo']
        );

        Respuesta::exito($resultado, 201);
    }

    /**
     * Lista los apercibimientos correspondientes al usuario autenticado.
     * Ruta: GET /api/apercibimientos
     */
    public function listar(Peticion $peticion): void
    {
        Sesion::iniciar();
        $rol = Sesion::obtener('rol_nombre');
        $usuarioId = (int)Sesion::obtener('usuario_id');

        if ($rol === 'SOCIO') {
            $socioId = (int)Sesion::obtener('socio_id');
            $lista = $this->apercibimientoServicio->listarPorSocio($socioId);
        } elseif ($rol === 'ENTRENADOR') {
            $lista = $this->apercibimientoServicio->listarPorEntrenador($usuarioId);
        } else {
            // ADMINISTRADOR
            $socioIdParam = $peticion->consulta('socio_id');
            if ($socioIdParam) {
                $lista = $this->apercibimientoServicio->listarPorSocio((int)$socioIdParam);
            } else {
                $lista = $this->apercibimientoServicio->listarTodos();
            }
        }

        Respuesta::exito($lista, 200);
    }
}
