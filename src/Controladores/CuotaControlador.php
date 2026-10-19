<?php
namespace Controladores;

use Core\Peticion;
use Core\Respuesta;
use Core\Validador;
use Servicios\CuotaServicio;

/**
 * Controlador de operaciones sobre cuotas sociales.
 * Capa: Controladores.
 */
class CuotaControlador
{
    private CuotaServicio $cuotaServicio;

    public function __construct(?CuotaServicio $cuotaServicio = null)
    {
        $this->cuotaServicio = $cuotaServicio ?? new CuotaServicio();
    }

    /**
     * Genera el lote de cuotas sociales para un periodo mensual (Caso de uso Generar cuotas).
     * Ruta: POST /api/cuotas/generar
     */
    public function generar(Peticion $peticion): void
    {
        $datos = Validador::validar($peticion->getCuerpo(), [
            'periodo' => 'requerido|periodo'
        ]);

        $resultado = $this->cuotaServicio->generarCuotasParaPeriodo($datos['periodo']);
        Respuesta::exito($resultado, 201);
    }

    /**
     * Aplica el recargo por mora a las cuotas vencidas (Caso de uso Aplicar recargos).
     * Ruta: POST /api/cuotas/aplicar-recargos
     */
    public function aplicarRecargos(Peticion $peticion): void
    {
        $resultado = $this->cuotaServicio->aplicarRecargosPorMora();
        Respuesta::exito($resultado, 200);
    }

    /**
     * Anula una cuota social con justificacion obligatoria (Caso de uso Anular cuota).
     * Ruta: POST /api/cuotas/{id}/anular
     */
    public function anular(Peticion $peticion, array $parametros): void
    {
        $cuotaId = (int)($parametros['id'] ?? 0);
        $datos = Validador::validar($peticion->getCuerpo(), [
            'motivo' => 'requerido|min:5|max:255'
        ]);

        $this->cuotaServicio->anularCuota($cuotaId, $datos['motivo']);
        Respuesta::exito(['mensaje' => 'Cuota anulada exitosamente.'], 200);
    }
}
