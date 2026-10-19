<?php
namespace Servicios;

use Core\BaseDatos;
use Repositorios\PagoRepositorio;
use Repositorios\CuotaRepositorio;
use Repositorios\ReciboRepositorio;
use Repositorios\SocioRepositorio;
use Repositorios\ConfiguracionRepositorio;
use Modelos\Pago;
use Modelos\Imputacion;
use Modelos\Recibo;
use Excepciones\ReglaNegocioException;
use Excepciones\ValidacionException;
use Excepciones\NoEncontradoException;
use Throwable;

/**
 * Servicio de Pagos e Imputaciones (Regla R7).
 * Capa: Servicios de dominio.
 *
 * Responsable de la cancelacion total o parcial de cuotas sociales,
 * priorizando cronologicamente las deudas mas antiguas, emision correlativa de recibos
 * y actualizacion transaccional del estado contable y del socio.
 */
class PagoServicio
{
    private PagoRepositorio $pagoRepo;
    private CuotaRepositorio $cuotaRepo;
    private ReciboRepositorio $reciboRepo;
    private SocioRepositorio $socioRepo;
    private ConfiguracionRepositorio $configRepo;
    private EstadoSocioServicio $estadoSocioServicio;

    public function __construct(
        ?PagoRepositorio $pagoRepo = null,
        ?CuotaRepositorio $cuotaRepo = null,
        ?ReciboRepositorio $reciboRepo = null,
        ?SocioRepositorio $socioRepo = null,
        ?ConfiguracionRepositorio $configRepo = null,
        ?EstadoSocioServicio $estadoSocioServicio = null
    ) {
        $this->pagoRepo = $pagoRepo ?? new PagoRepositorio();
        $this->cuotaRepo = $cuotaRepo ?? new CuotaRepositorio();
        $this->reciboRepo = $reciboRepo ?? new ReciboRepositorio();
        $this->socioRepo = $socioRepo ?? new SocioRepositorio();
        $this->configRepo = $configRepo ?? new ConfiguracionRepositorio();
        $this->estadoSocioServicio = $estadoSocioServicio ?? new EstadoSocioServicio($this->socioRepo, $this->cuotaRepo, $this->configRepo);
    }

    /**
     * Registra un pago y ejecuta la imputacion automatica sobre las cuotas mas antiguas (Regla R7).
     *
     * @param int $socioId ID del socio que abona
     * @param ?int $usuarioId ID del usuario que opera la transaccion (cajero o propio socio)
     * @param float $montoTotal Monto dinerario percibido
     * @param string $medioPago EFECTIVO, TRANSFERENCIA o TARJETA_SIMULADA
     * @param bool $autogestionado True si fue iniciado por el propio socio desde su portal
     * @param ?string $observaciones Detalle opcional
     * @return array Detalle del pago y recibo emitido
     * @throws ReglaNegocioException Si el monto excede la deuda o si ocurre inconsistencia contable
     */
    public function registrarPagoEImputar(
        int $socioId,
        ?int $usuarioId,
        float $montoTotal,
        string $medioPago,
        bool $autogestionado = false,
        ?string $observaciones = null
    ): array {
        $montoTotal = round($montoTotal, 2);

        if ($montoTotal <= 0.0) {
            throw new ValidacionException('El monto a abonar debe ser superior a cero.');
        }

        $mediosValidos = ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA_SIMULADA'];
        if (!in_array($medioPago, $mediosValidos, true)) {
            throw new ValidacionException('Medio de pago no valido.');
        }

        $socio = $this->socioRepo->buscarPorId($socioId);
        if (!$socio) {
            throw new NoEncontradoException('El socio indicado no existe.');
        }

        if ($socio->estado === 'BAJA') {
            throw new ReglaNegocioException('Un socio en estado BAJA no puede operar ni registrar pagos.');
        }

        // Obtener cuotas con saldo impago ordenadas por periodo ASC y luego id ASC (Regla R7)
        $cuotasImpagas = $this->cuotaRepo->buscarCuotasImpagasPorSocio($socioId);
        if (empty($cuotasImpagas)) {
            throw new ReglaNegocioException('El socio no registra cuotas pendientes de pago.');
        }

        // Calcular la deuda total actual
        $deudaTotal = 0.0;
        foreach ($cuotasImpagas as $c) {
            $deudaTotal += $c->getSaldo();
        }
        $deudaTotal = round($deudaTotal, 2);

        // Regla R7: Si el monto excede el total adeudado, rechazar con error claro 409 (no se maneja saldo a favor)
        if ($montoTotal > $deudaTotal) {
            throw new \Excepciones\ConflictoException(sprintf(
                'El monto ingresado ($%.2f) excede la deuda total del socio ($%.2f). El club no admite saldo a favor.',
                $montoTotal,
                $deudaTotal
            ));
        }

        $hoy = date('Y-m-d');
        $ahora = date('Y-m-d H:i:s');

        BaseDatos::iniciarTransaccion();

        try {
            // 1. Insertar cabecera del pago
            $pago = new Pago(
                null,
                $socioId,
                $usuarioId,
                $montoTotal,
                $medioPago,
                $autogestionado,
                $ahora,
                $observaciones
            );
            $pagoId = $this->pagoRepo->crear($pago);

            // 2. Imputar a las cuotas mas antiguas
            $remanente = $montoTotal;
            $imputacionesRealizadas = [];

            foreach ($cuotasImpagas as $cuota) {
                if ($remanente <= 0.0) {
                    break;
                }

                $saldoCuota = $cuota->getSaldo();
                $montoImputar = min($saldoCuota, $remanente);
                $montoImputar = round($montoImputar, 2);

                if ($montoImputar <= 0.0) {
                    continue;
                }

                // Guardar imputacion
                $imputacion = new Imputacion(null, $pagoId, $cuota->id, $montoImputar);
                $this->pagoRepo->crearImputacion($imputacion);

                // Actualizar saldo y estado de la cuota (Regla R2)
                $nuevoMontoPagado = round($cuota->montoPagado + $montoImputar, 2);
                $nuevoEstado = ($nuevoMontoPagado >= $cuota->montoFinal)
                    ? 'PAGADA'
                    : (($hoy > $cuota->fechaVencimiento) ? 'VENCIDA' : 'PENDIENTE');

                $this->cuotaRepo->actualizarPagoCuota($cuota->id, $nuevoMontoPagado, $nuevoEstado);

                $imputacionesRealizadas[] = [
                    'cuota_id' => $cuota->id,
                    'periodo' => $cuota->periodo,
                    'monto_imputado' => $montoImputar,
                    'saldo_restante' => round(max(0.0, $cuota->montoFinal - $nuevoMontoPagado), 2),
                    'estado_final' => $nuevoEstado
                ];

                $remanente = round($remanente - $montoImputar, 2);
            }

            // 3. Generar recibo con numeracion correlativa unica (Regla R7)
            $numeroRecibo = $this->reciboRepo->obtenerSiguienteNumero();
            $recibo = new Recibo(
                null,
                $pagoId,
                $numeroRecibo,
                $ahora,
                $montoTotal
            );
            $reciboId = $this->reciboRepo->crear($recibo);

            // 4. Recalcular estado del socio tras el pago (Regla R1)
            $estadoSocioFinal = $this->estadoSocioServicio->recalcularEstado($socioId);

            BaseDatos::confirmarTransaccion();

            return [
                'pago_id' => $pagoId,
                'recibo_id' => $reciboId,
                'numero_recibo' => $numeroRecibo,
                'monto_total' => $montoTotal,
                'medio_pago' => $medioPago,
                'fecha' => $ahora,
                'socio' => [
                    'id' => $socio->id,
                    'numero_socio' => $socio->numero_socio ?? $socio->numeroSocio,
                    'nombre' => $socio->nombre . ' ' . $socio->apellido,
                    'estado_actualizado' => $estadoSocioFinal
                ],
                'imputaciones' => $imputacionesRealizadas
            ];
        } catch (Throwable $e) {
            BaseDatos::revertirTransaccion();
            throw new ReglaNegocioException('Error al procesar el pago e imputacion: ' . $e->getMessage());
        }
    }
}
