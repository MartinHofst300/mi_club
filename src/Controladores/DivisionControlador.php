<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Sesion;
use Core\Validador;
use Servicios\DivisionServicio;
use Excepciones\ProhibidoException;

/**
 * Controlador para la gestion de divisiones, planteles e inscripciones.
 * Capa: Controladores.
 */
class DivisionControlador
{
    private DivisionServicio $divisionServicio;

    public function __construct(?DivisionServicio $divisionServicio = null)
    {
        $this->divisionServicio = $divisionServicio ?? new DivisionServicio();
    }

    /**
     * Lista todas las divisiones deportivas activas con disponibilidad de cupo.
     * Ruta: GET /api/divisiones
     */
    public function listar(Peticion $peticion): void
    {
        Sesion::iniciar();
        $socioId = null;
        if (Sesion::obtener('rol_nombre') === 'SOCIO') {
            $socioId = (int)Sesion::obtener('socio_id');
        }

        $divisiones = $this->divisionServicio->listarDivisionesConDisponibilidad($socioId);
        Respuesta::exito($divisiones, 200);
    }

    /**
     * Obtiene el plantel de una division deportiva (Caso de uso Gestionar plantel).
     * Ruta: GET /api/divisiones/{id}/plantel
     */
    public function plantel(Peticion $peticion, array $parametros): void
    {
        $divisionId = (int)($parametros['id'] ?? 0);
        Sesion::iniciar();
        $rol = Sesion::obtener('rol_nombre');
        $usuarioId = (int)Sesion::obtener('usuario_id');

        $entrenadorFiltro = ($rol === 'ENTRENADOR') ? $usuarioId : null;
        $plantel = $this->divisionServicio->obtenerPlantel($divisionId, $entrenadorFiltro);

        Respuesta::exito($plantel, 200);
    }

    /**
     * Inscribe a un socio en una division (Casos de uso Inscribirse a division y Gestionar plantel).
     * Ruta: POST /api/divisiones/{id}/inscripciones
     */
    public function inscribir(Peticion $peticion, array $parametros): void
    {
        $divisionId = (int)($parametros['id'] ?? 0);
        Sesion::iniciar();
        $rol = Sesion::obtener('rol_nombre');
        $usuarioId = (int)Sesion::obtener('usuario_id');

        // Si es socio, se inscribe a si mismo
        if ($rol === 'SOCIO') {
            $socioId = (int)Sesion::obtener('socio_id');
            $entrenadorUsuarioId = null;
        } else {
            // Entrenador o Administrador inscribe a un socio del club
            $datos = Validador::validar($peticion->getCuerpo(), [
                'socio_id' => 'requerido|entero|positivo'
            ]);
            $socioId = (int)$datos['socio_id'];
            $entrenadorUsuarioId = ($rol === 'ENTRENADOR') ? $usuarioId : null;
        }

        $resultado = $this->divisionServicio->inscribirSocio($divisionId, $socioId, $entrenadorUsuarioId);
        Respuesta::exito($resultado, 201);
    }

    /**
     * Da de baja a un socio del plantel o division (Gestionar plantel o autogestion del socio).
     * Ruta: DELETE /api/divisiones/{id}/inscripciones/{socioId}
     */
    public function desactivar(Peticion $peticion, array $parametros): void
    {
        $divisionId = (int)($parametros['id'] ?? 0);
        $socioId = (int)($parametros['socioId'] ?? 0);

        Sesion::iniciar();
        $rol = Sesion::obtener('rol_nombre');
        $usuarioId = (int)Sesion::obtener('usuario_id');

        if ($rol === 'SOCIO') {
            $miSocioId = (int)Sesion::obtener('socio_id');
            if ($socioId !== $miSocioId) {
                throw new ProhibidoException('No tiene autorizacion para dar de baja a otro socio.');
            }
            $entrenadorUsuarioId = null;
        } else {
            $entrenadorUsuarioId = ($rol === 'ENTRENADOR') ? $usuarioId : null;
        }

        $this->divisionServicio->darDeBajaPlantel($divisionId, $socioId, $entrenadorUsuarioId);
        Respuesta::exito(['mensaje' => 'Inscripcion dada de baja exitosamente.'], 200);
    }

    /**
     * Da de alta una nueva division deportiva (Gestionar divisiones por Administrador).
     * Ruta: POST /api/divisiones
     */
    public function crear(Peticion $peticion): void
    {
        $datos = Validador::validar($peticion->getCuerpo(), [
            'nombre' => 'requerido',
            'categoria_id' => 'requerido|entero|positivo',
            'entrenador_id' => 'requerido|entero|positivo',
            'cupo_maximo' => 'requerido|entero|positivo'
        ]);

        $cuerpo = $peticion->getCuerpo();
        if (isset($cuerpo['activo'])) {
            $datos['activo'] = (bool)$cuerpo['activo'];
        }

        $resultado = $this->divisionServicio->crear($datos);
        Respuesta::exito($resultado, 201);
    }

    /**
     * Actualiza una division existente.
     * Ruta: PUT /api/divisiones/{id}
     */
    public function actualizar(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);

        $datos = Validador::validar($peticion->getCuerpo(), [
            'nombre' => 'requerido',
            'categoria_id' => 'requerido|entero|positivo',
            'entrenador_id' => 'requerido|entero|positivo',
            'cupo_maximo' => 'requerido|entero|positivo'
        ]);

        $cuerpo = $peticion->getCuerpo();
        if (isset($cuerpo['activo'])) {
            $datos['activo'] = (bool)$cuerpo['activo'];
        }

        $resultado = $this->divisionServicio->actualizar($id, $datos);
        Respuesta::exito($resultado, 200);
    }

    /**
     * Elimina una division deportiva.
     * Ruta: DELETE /api/divisiones/{id}
     */
    public function eliminar(Peticion $peticion, array $parametros): void
    {
        $id = (int)($parametros['id'] ?? 0);
        $resultado = $this->divisionServicio->eliminar($id);
        Respuesta::exito($resultado, 200);
    }
}
