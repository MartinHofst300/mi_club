<?php
namespace Servicios;

use Repositorios\SocioRepositorio;
use Repositorios\CuotaRepositorio;
use Repositorios\ConfiguracionRepositorio;
use Modelos\Socio;

/**
 * Servicio de Evaluacion y Recalculo de Estado del Socio (Regla R1).
 * Capa: Servicios de dominio.
 *
 * Logica de negocio:
 * - Evalua cuotas impagas con vencimiento pasado y saldo adeudado.
 * - Si tiene N o mas cuotas impagas (configurable), pasa a MOROSO.
 * - Si regulariza (menos de N cuotas impagas), vuelve a ACTIVO.
 * - Los estados SUSPENDIDO y BAJA fijados por administracion nunca son pisados automaticamente.
 */
class EstadoSocioServicio
{
    private SocioRepositorio $socioRepo;
    private CuotaRepositorio $cuotaRepo;
    private ConfiguracionRepositorio $configRepo;

    public function __construct(
        ?SocioRepositorio $socioRepo = null,
        ?CuotaRepositorio $cuotaRepo = null,
        ?ConfiguracionRepositorio $configRepo = null
    ) {
        $this->socioRepo = $socioRepo ?? new SocioRepositorio();
        $this->cuotaRepo = $cuotaRepo ?? new CuotaRepositorio();
        $this->configRepo = $configRepo ?? new ConfiguracionRepositorio();
    }

    /**
     * Recalcula el estado de un socio puntual respetando las protecciones de SUSPENDIDO y BAJA (Regla R1).
     *
     * @param int $socioId ID del socio a recalcular
     * @return string Estado final resultante
     */
    public function recalcularEstado(int $socioId): string
    {
        $socio = $this->socioRepo->buscarPorId($socioId);
        if (!$socio) {
            return 'DESCONOCIDO';
        }

        // Regla R1: SUSPENDIDO y BAJA solo los fija manualmente el Administrador; nunca los pisa el calculo
        if ($socio->estado === 'SUSPENDIDO' || $socio->estado === 'BAJA') {
            return $socio->estado;
        }

        $config = $this->configRepo->obtener();
        $limiteMoroso = $config->cuotasParaMoroso;
        $hoy = date('Y-m-d');

        // Contar cuotas impagas vencidas
        $cuotasImpagasVencidas = $this->cuotaRepo->contarCuotasImpagasVencidas($socioId, $hoy);

        $nuevoEstado = ($cuotasImpagasVencidas >= $limiteMoroso) ? 'MOROSO' : 'ACTIVO';

        if ($socio->estado !== $nuevoEstado) {
            $this->socioRepo->actualizarEstado($socioId, $nuevoEstado);
        }

        return $nuevoEstado;
    }
}
