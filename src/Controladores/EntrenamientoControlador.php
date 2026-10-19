<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Sesion;
use Repositorios\EntrenamientoRepositorio;
use Servicios\AsistenciaServicio;

/**
 * Controlador para la gestion de entrenamientos y asistencia (Regla R9).
 * Capa: Controladores.
 */
class EntrenamientoControlador
{
    private EntrenamientoRepositorio $entrenamientoRepo;
    private AsistenciaServicio $asistenciaServicio;

    public function __construct(
        ?EntrenamientoRepositorio $entrenamientoRepo = null,
        ?AsistenciaServicio $asistenciaServicio = null
    ) {
        $this->entrenamientoRepo = $entrenamientoRepo ?? new EntrenamientoRepositorio();
        $this->asistenciaServicio = $asistenciaServicio ?? new AsistenciaServicio();
    }

    /**
     * Lista los entrenamientos asignados al entrenador autenticado.
     * Ruta: GET /api/entrenamientos
     */
    public function listar(Peticion $peticion): void
    {
        Sesion::iniciar();
        $usuarioId = (int)Sesion::obtener('usuario_id');
        $rol = Sesion::obtener('rol_nombre');

        if ($rol === 'ADMINISTRADOR') {
            $entrenamientos = $this->entrenamientoRepo->listarTodos();
        } else {
            $entrenamientos = $this->entrenamientoRepo->listarPorEntrenador($usuarioId);
        }
        Respuesta::exito($entrenamientos, 200);
    }

    /**
     * Obtiene la planilla de asistencia del plantel para una sesion (Caso de uso Consultar asistencia).
     * Ruta: GET /api/entrenamientos/{id}/asistencia
     */
    public function obtenerPlanilla(Peticion $peticion, array $parametros): void
    {
        $entrenamientoId = (int)($parametros['id'] ?? 0);
        Sesion::iniciar();
        $usuarioId = (int)Sesion::obtener('usuario_id');
        $rol = Sesion::obtener('rol_nombre');
        $usuarioFiltro = ($rol === 'ADMINISTRADOR') ? 0 : $usuarioId;

        $planilla = $this->asistenciaServicio->obtenerPlanilla($entrenamientoId, $usuarioFiltro);
        Respuesta::exito($planilla, 200);
    }

    /**
     * Registra o actualiza la toma de asistencia del plantel (Caso de uso Registrar asistencia).
     * Ruta: PUT /api/entrenamientos/{id}/asistencia
     */
    public function guardarAsistencia(Peticion $peticion, array $parametros): void
    {
        $entrenamientoId = (int)($parametros['id'] ?? 0);
        Sesion::iniciar();
        $usuarioId = (int)Sesion::obtener('usuario_id');

        $asistencias = $peticion->obtener('asistencias', []);
        $resultado = $this->asistenciaServicio->registrarAsistencias($entrenamientoId, $usuarioId, $asistencias);

        Respuesta::exito($resultado, 200);
    }

    /**
     * Crea una nueva sesion de entrenamiento para una division asignada (Caso de uso Crear entrenamiento).
     * Ruta: POST /api/entrenamientos
     */
    public function crear(Peticion $peticion): void
    {
        Sesion::iniciar();
        $usuarioId = (int)Sesion::obtener('usuario_id');
        $rol = Sesion::obtener('rol_nombre');

        $datos = \Core\Validador::validar($peticion->getCuerpo(), [
            'division_id' => 'requerido|entero|positivo',
            'fecha' => 'requerido|fecha',
            'hora' => 'requerido|hora',
            'lugar' => 'requerido|min:3|max:150',
            'observaciones' => 'max:500'
        ]);

        if ($datos['fecha'] < date('Y-m-d')) {
            throw new \Excepciones\ValidacionException('La fecha del entrenamiento no puede ser anterior al dia de hoy.');
        }

        $divisionRepo = new \Repositorios\DivisionRepositorio();
        $division = $divisionRepo->buscarPorId((int)$datos['division_id']);
        if (!$division || !$division->activo) {
            throw new \Excepciones\NoEncontradoException('La division deportiva no existe o se encuentra inactiva.');
        }

        if ($rol === 'ENTRENADOR' && !$divisionRepo->esEntrenadorDeDivision((int)$datos['division_id'], $usuarioId)) {
            throw new \Excepciones\ProhibidoException('No es el entrenador asignado a esta division.');
        }

        $entrenadorId = ($rol === 'ENTRENADOR') ? $usuarioId : $division->entrenadorId;

        $entrenamiento = new \Modelos\Entrenamiento(
            null,
            (int)$datos['division_id'],
            $entrenadorId,
            $datos['fecha'],
            $datos['hora'],
            trim($datos['lugar']),
            !empty($datos['observaciones']) ? trim($datos['observaciones']) : null
        );

        $id = $this->entrenamientoRepo->crear($entrenamiento);
        Respuesta::exito([
            'id' => $id,
            'mensaje' => 'Entrenamiento programado exitosamente.'
        ], 201);
    }
}

