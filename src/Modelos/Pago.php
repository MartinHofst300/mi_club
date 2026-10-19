<?php
namespace Modelos;

/**
 * Entidad Pago. Registra la percepcion de fondos para cancelar obligaciones del socio (Regla R7).
 * Capa: Modelos del dominio.
 */
class Pago
{
    public ?int $id;
    public int $socioId;
    public ?int $usuarioId;
    public float $montoTotal;
    public string $medioPago; // EFECTIVO, TRANSFERENCIA, TARJETA_SIMULADA
    public bool $autogestionado;
    public string $fechaPago;
    public ?string $observaciones;

    public function __construct(
        ?int $id = null,
        int $socioId = 0,
        ?int $usuarioId = null,
        float $montoTotal = 0.0,
        string $medioPago = 'EFECTIVO',
        bool $autogestionado = false,
        string $fechaPago = '',
        ?string $observaciones = null
    ) {
        $this->id = $id;
        $this->socioId = $socioId;
        $this->usuarioId = $usuarioId;
        $this->montoTotal = $montoTotal;
        $this->medioPago = $medioPago;
        $this->autogestionado = $autogestionado;
        $this->fechaPago = $fechaPago;
        $this->observaciones = $observaciones;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['socio_id'] ?? 0),
            isset($datos['usuario_id']) && $datos['usuario_id'] !== '' ? (int)$datos['usuario_id'] : null,
            (float)($datos['monto_total'] ?? 0.0),
            $datos['medio_pago'] ?? 'EFECTIVO',
            (bool)($datos['autogestionado'] ?? false),
            $datos['fecha_pago'] ?? date('Y-m-d H:i:s'),
            $datos['observaciones'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'socio_id' => $this->socioId,
            'usuario_id' => $this->usuarioId,
            'monto_total' => $this->montoTotal,
            'medio_pago' => $this->medioPago,
            'autogestionado' => $this->autogestionado,
            'fecha_pago' => $this->fechaPago,
            'observaciones' => $this->observaciones
        ];
    }
}
