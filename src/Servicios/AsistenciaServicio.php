<?php
namespace Servicios;

use Core\BaseDatos;
use Repositorios\EntrenamientoRepositorio;
use Repositorios\DivisionRepositorio;
use Repositorios\AsistenciaRepositorio;
use Excepciones\ReglaNegocioException;
use Excepciones\ProhibidoException;
use Excepciones\NoEncontradoException;
use Excepciones\ValidacionException;
use Throwable;

/**
 * Servicio de Gestion de Asistencias a Entrenamientos (Regla R9).
 * Capa: Servicios de dominio.
 *
 * Responsable de validar la pertenencia del entrenador a la division y
 * registrar de forma idempotente las asistencias del plantel.
 */
class AsistenciaServicio
{
    private EntrenamientoRepositorio $entrenamientoRepo;
    private DivisionRepositorio $divisionRepo;
    private AsistenciaRepositorio $asistenciaRepo;

    public function __construct(
        ?EntrenamientoRepositorio $entrenamientoRepo = null,
        ?DivisionRepositorio $divisionRepo = null,
        ?AsistenciaRepositorio $asistenciaRepo = null
    ) {
        $this->entrenamientoRepo = $entrenamientoRepo ?? new EntrenamientoRepositorio();
        $this->divisionRepo = $divisionRepo ?? new DivisionRepositorio();
        $this->asistenciaRepo = $asistenciaRepo ?? new AsistenciaRepositorio();
    }

    /**
     * Obtiene la planilla de asistencia de una sesion, comprobando que pertenezca al entrenador solicitante.
     *
     * @param int $entrenamientoId ID del entrenamiento
     * @param int $entrenadorUsuarioId ID del usuario autenticado
     * @throws ProhibidoException Si el usuario no es el entrenador asignado a la division
     * @throws NoEncontradoException Si no existe el entrenamiento
     */
    public function obtenerPlanilla(int $entrenamientoId, int $entrenadorUsuarioId): array
    {
        $entrenamiento = $this->entrenamientoRepo->buscarDetalle($entrenamientoId);
        if (!$entrenamiento) {
            throw new NoEncontradoException('El entrenamiento no existe.');
        }

        // Regla R9: Solo el entrenador asignado a esa division puede operar
        if ($entrenadorUsuarioId > 0 && !$this->divisionRepo->esEntrenadorDeDivision((int)$entrenamiento['division_id'], $entrenadorUsuarioId)) {
            throw new ProhibidoException('No es el entrenador asignado a esta division.');
        }

        $plantel = $this->asistenciaRepo->obtenerPlanillaEntrenamiento($entrenamientoId, (int)$entrenamiento['division_id']);

        return [
            'entrenamiento' => $entrenamiento,
            'plantel' => $plantel
        ];
    }

    /**
     * Guarda o actualiza la asistencia de los socios del plantel (Regla R9).
     *
     * @param int $entrenamientoId ID del entrenamiento
     * @param int $entrenadorUsuarioId ID del usuario entrenador
     * @param array $asistencias Arreglo de elementos ['socio_id' => int, 'estado' => string, 'observaciones' => ?string]
     * @return array Resumen de asistencias guardadas
     */
    public function registrarAsistencias(int $entrenamientoId, int $entrenadorUsuarioId, array $asistencias): array
    {
        $entrenamiento = $this->entrenamientoRepo->buscarPorId($entrenamientoId);
        if (!$entrenamiento) {
            throw new NoEncontradoException('El entrenamiento no existe.');
        }

        // Regla R9: Solo el entrenador asignado a esa division puede operar
        if (!$this->divisionRepo->esEntrenadorDeDivision($entrenamiento->divisionId, $entrenadorUsuarioId)) {
            throw new ProhibidoException('No tiene permisos para tomar asistencia en esta division.');
        }

        if (empty($asistencias)) {
            throw new ValidacionException('No se enviaron datos de asistencia para procesar.');
        }

        $estadosValidos = ['PRESENTE', 'AUSENTE', 'JUSTIFICADO'];
        $guardadas = 0;

        BaseDatos::iniciarTransaccion();

        try {
            foreach ($asistencias as $item) {
                $socioId = (int)($item['socio_id'] ?? 0);
                $estado = strtoupper(trim($item['estado'] ?? 'PRESENTE'));
                $obs = !empty($item['observaciones']) ? trim($item['observaciones']) : null;

                if ($socioId <= 0) {
                    continue;
                }

                if (!in_array($estado, $estadosValidos, true)) {
                    throw new ValidacionException("Estado de asistencia invalido para el socio {$socioId}: {$estado}");
                }

                // Guardar o actualizar de forma idempotente (Regla R9)
                $this->asistenciaRepo->guardarOActualizar($entrenamientoId, $socioId, $estado, $obs);
                $guardadas++;
            }

            BaseDatos::confirmarTransaccion();

            return [
                'entrenamiento_id' => $entrenamientoId,
                'asistencias_procesadas' => $guardadas
            ];
        } catch (Throwable $e) {
            BaseDatos::revertirTransaccion();
            throw new ReglaNegocioException('Error al registrar la asistencia: ' . $e->getMessage());
        }
    }
}
