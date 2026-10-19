<?php
namespace Servicios;

use Core\BaseDatos;
use Repositorios\CuotaRepositorio;
use Repositorios\SocioRepositorio;
use Repositorios\ConfiguracionRepositorio;
use Modelos\Cuota;
use Excepciones\ReglaNegocioException;
use Excepciones\ValidacionException;
use Excepciones\NoEncontradoException;
use Throwable;

/**
 * Servicio de Gestion de Cuotas Sociales (Reglas R2, R3, R4, R5, R6).
 * Capa: Servicios de dominio.
 *
 * Responsable de la emision de cuotas mensuales, aplicacion de descuentos familiares,
 * recargos por mora no acumulativos y control de anulaciones transaccionales.
 */
class CuotaServicio
{
    private CuotaRepositorio $cuotaRepo;
    private SocioRepositorio $socioRepo;
    private ConfiguracionRepositorio $configRepo;
    private EstadoSocioServicio $estadoSocioServicio;

    public function __construct(
        ?CuotaRepositorio $cuotaRepo = null,
        ?SocioRepositorio $socioRepo = null,
        ?ConfiguracionRepositorio $configRepo = null,
        ?EstadoSocioServicio $estadoSocioServicio = null
    ) {
        $this->cuotaRepo = $cuotaRepo ?? new CuotaRepositorio();
        $this->socioRepo = $socioRepo ?? new SocioRepositorio();
        $this->configRepo = $configRepo ?? new ConfiguracionRepositorio();
        $this->estadoSocioServicio = $estadoSocioServicio ?? new EstadoSocioServicio($this->socioRepo, $this->cuotaRepo, $this->configRepo);
    }

    /**
     * Genera las cuotas sociales para un periodo mensual especifico (Regla R6).
     *
     * @param string $periodo Formato AAAA-MM (ej: '2026-11')
     * @return array Resumen del proceso con cantidades y detalles
     * @throws ValidacionException Si el formato del periodo es invalido
     */
    public function generarCuotasParaPeriodo(string $periodo): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            throw new ValidacionException('El periodo debe tener el formato AAAA-MM (ej: 2026-11).');
        }

        $config = $this->configRepo->obtener();
        $candidatos = $this->socioRepo->buscarCandidatosGeneracionCuota();

        // Calcular fecha de vencimiento segun dia configurable (ej: 2026-11-10)
        $partesPeriodo = explode('-', $periodo);
        $anio = (int)$partesPeriodo[0];
        $mes = (int)$partesPeriodo[1];
        $diaVencimiento = min($config->diasVencimiento, (int)date('t', strtotime("{$anio}-{$mes}-01")));
        $fechaVencimiento = sprintf('%04d-%02d-%02d', $anio, $mes, $diaVencimiento);

        $resumen = [
            'periodo' => $periodo,
            'fecha_vencimiento' => $fechaVencimiento,
            'total_candidatos' => count($candidatos),
            'generadas' => 0,
            'omitidas' => 0,
            'errores' => 0,
            'detalles' => []
        ];

        // Cache de descuentos por grupo familiar para no recalcular en cada socio
        $descuentoPorGrupo = [];

        BaseDatos::iniciarTransaccion();

        try {
            foreach ($candidatos as $candidato) {
                $socioId = (int)$candidato['id'];
                $nombreCompleto = $candidato['nombre'] . ' ' . $candidato['apellido'];

                // 1. Unicidad: omitir si ya existe la cuota para este periodo
                if ($this->cuotaRepo->existeCuotaSocioPeriodo($socioId, $periodo)) {
                    $resumen['omitidas']++;
                    $resumen['detalles'][] = [
                        'socio_id' => $socioId,
                        'socio' => $nombreCompleto,
                        'estado' => 'OMITIDA',
                        'motivo' => "Ya posee cuota emitida para el periodo {$periodo}."
                    ];
                    continue;
                }

                // 2. Validar precio base de la categoria (Regla R3)
                $precioBase = (float)($candidato['categoria_precio'] ?? 0.0);
                if ($precioBase <= 0.0) {
                    $resumen['errores']++;
                    $resumen['detalles'][] = [
                        'socio_id' => $socioId,
                        'socio' => $nombreCompleto,
                        'estado' => 'ERROR',
                        'motivo' => 'La categoria del socio no posee una tarifa configurada mayor a cero.'
                    ];
                    continue;
                }

                // 3. Evaluar descuento familiar (Regla R5)
                $montoDescuento = 0.0;
                $grupoId = !empty($candidato['grupo_familiar_id']) ? (int)$candidato['grupo_familiar_id'] : null;

                if ($grupoId !== null) {
                    if (!isset($descuentoPorGrupo[$grupoId])) {
                        $integrantesActivos = $this->socioRepo->contarIntegrantesActivosGrupoFamiliar($grupoId);
                        $descuentoPorGrupo[$grupoId] = ($integrantesActivos >= 2);
                    }

                    if ($descuentoPorGrupo[$grupoId]) {
                        $porcentajeDesc = $config->porcentajeDescuentoFamiliar;
                        $montoDescuento = round(($precioBase * $porcentajeDesc) / 100.0, 2);
                    }
                }

                // 4. Monto final inicial (sin recargo al nacer)
                $montoFinal = round(max(0.0, $precioBase - $montoDescuento), 2);

                $cuota = new Cuota(
                    null,
                    $socioId,
                    $periodo,
                    $precioBase,
                    $montoDescuento,
                    0.00, // Sin recargo al nacer
                    $montoFinal,
                    0.00, // Monto pagado inicial
                    $fechaVencimiento,
                    'PENDIENTE'
                );

                $this->cuotaRepo->crear($cuota);
                $resumen['generadas']++;
                $resumen['detalles'][] = [
                    'socio_id' => $socioId,
                    'socio' => $nombreCompleto,
                    'estado' => 'GENERADA',
                    'monto_base' => $precioBase,
                    'descuento_familiar' => $montoDescuento,
                    'monto_final' => $montoFinal
                ];
            }

            // 5. Actualizar cuotas vencidas y recalcular estados de morosidad (Reglas R1 y R2)
            $this->actualizarEstadosPorVencimiento();

            BaseDatos::confirmarTransaccion();
        } catch (Throwable $e) {
            BaseDatos::revertirTransaccion();
            throw new ReglaNegocioException('Error al generar el lote de cuotas: ' . $e->getMessage());
        }

        return $resumen;
    }

    /**
     * Aplica el recargo por mora a las cuotas vencidas impagas que aun no lo hayan recibido (Regla R4).
     * Idempotente: no duplica recargos si ya fueron aplicados.
     */
    public function aplicarRecargosPorMora(): array
    {
        $config = $this->configRepo->obtener();
        $porcentajeRecargo = $config->porcentajeRecargo;
        $hoy = date('Y-m-d');

        BaseDatos::iniciarTransaccion();

        try {
            // Actualizar a VENCIDA las cuotas que pasaron fecha
            $this->cuotaRepo->actualizarCuotasVencidas($hoy);

            $cuotasParaRecargo = $this->cuotaRepo->buscarVencidasSinRecargo($hoy);
            $aplicadas = 0;
            $sociosAfectados = [];

            foreach ($cuotasParaRecargo as $cuota) {
                // Recargo sobre el monto base (Regla R4)
                $montoRecargo = round(($cuota->montoBase * $porcentajeRecargo) / 100.0, 2);
                $nuevoMontoFinal = round($cuota->montoFinal + $montoRecargo, 2);

                $this->cuotaRepo->aplicarRecargo($cuota->id, $montoRecargo, $nuevoMontoFinal);
                $aplicadas++;
                $sociosAfectados[$cuota->socioId] = true;
            }

            // Recalcular estado de los socios afectados (Regla R1)
            foreach (array_keys($sociosAfectados) as $socioId) {
                $this->estadoSocioServicio->recalcularEstado($socioId);
            }

            BaseDatos::confirmarTransaccion();

            return [
                'cuotas_actualizadas' => $aplicadas,
                'porcentaje_aplicado' => $porcentajeRecargo,
                'fecha_corte' => $hoy
            ];
        } catch (Throwable $e) {
            BaseDatos::revertirTransaccion();
            throw new ReglaNegocioException('Error al aplicar recargos por mora: ' . $e->getMessage());
        }
    }

    /**
     * Anula una cuota a solicitud del Tesorero (Regla R2).
     *
     * @param int $cuotaId ID de la cuota a anular
     * @param string $motivo Justificacion obligatoria
     * @return bool
     * @throws ReglaNegocioException Si la cuota esta pagada o ya anulada
     */
    public function anularCuota(int $cuotaId, string $motivo): bool
    {
        $motivo = trim($motivo);
        if (empty($motivo)) {
            throw new ValidacionException('Debe especificar el motivo de la anulacion.');
        }

        $cuota = $this->cuotaRepo->buscarPorId($cuotaId);
        if (!$cuota) {
            throw new NoEncontradoException('La cuota solicitada no existe.');
        }

        // Regla R2: Una cuota PAGADA no se puede anular
        if ($cuota->estado === 'PAGADA') {
            throw new ReglaNegocioException('No es posible anular una cuota que ya se encuentra pagada.');
        }

        if ($cuota->estado === 'ANULADA') {
            throw new ReglaNegocioException('La cuota ya se encuentra en estado anulada.');
        }

        BaseDatos::iniciarTransaccion();

        try {
            $this->cuotaRepo->anular($cuotaId, $motivo);

            // Recalcular estado del socio: una cuota anulada no se cuenta como impaga (Regla R1)
            $this->estadoSocioServicio->recalcularEstado($cuota->socioId);

            BaseDatos::confirmarTransaccion();
            return true;
        } catch (Throwable $e) {
            BaseDatos::revertirTransaccion();
            throw new ReglaNegocioException('Error al anular la cuota: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza el estado VENCIDA de todas las cuotas que superaron su fecha de vencimiento y recalcula morosidad (Reglas R1 y R2).
     */
    public function actualizarEstadosPorVencimiento(): void
    {
        $hoy = date('Y-m-d');
        $this->cuotaRepo->actualizarCuotasVencidas($hoy);
    }
}
