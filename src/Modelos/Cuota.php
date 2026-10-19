<?php
namespace Modelos;

/**
 * Entidad Cuota. Gestiona obligaciones mensuales, recargos y descuentos (Reglas R2, R3, R4, R5, R6).
 * Capa: Modelos del dominio.
 *
 * Estados validos: PENDIENTE, PAGADA, VENCIDA, ANULADA.
 */
class Cuota
{
    public ?int $id;
    public int $socioId;
    public string $periodo; // Formato AAAA-MM
    public float $montoBase;
    public float $montoDescuento;
    public float $montoRecargo;
    public float $montoFinal;
    public float $montoPagado;
    public string $fechaVencimiento;
    public string $estado;
    public ?string $fechaAnulacion;
    public ?string $motivoAnulacion;
    public ?string $creadoEn;

    public function __construct(
        ?int $id = null,
        int $socioId = 0,
        string $periodo = '',
        float $montoBase = 0.0,
        float $montoDescuento = 0.0,
        float $montoRecargo = 0.0,
        float $montoFinal = 0.0,
        float $montoPagado = 0.0,
        string $fechaVencimiento = '',
        string $estado = 'PENDIENTE',
        ?string $fechaAnulacion = null,
        ?string $motivoAnulacion = null,
        ?string $creadoEn = null
    ) {
        $this->id = $id;
        $this->socioId = $socioId;
        $this->periodo = $periodo;
        $this->montoBase = $montoBase;
        $this->montoDescuento = $montoDescuento;
        $this->montoRecargo = $montoRecargo;
        $this->montoFinal = $montoFinal;
        $this->montoPagado = $montoPagado;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->estado = $estado;
        $this->fechaAnulacion = $fechaAnulacion;
        $this->motivoAnulacion = $motivoAnulacion;
        $this->creadoEn = $creadoEn;
    }

    /**
     * Calcula el saldo adeudado de la cuota.
     */
    public function getSaldo(): float
    {
        return round(max(0.0, $this->montoFinal - $this->montoPagado), 2);
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['socio_id'] ?? 0),
            $datos['periodo'] ?? '',
            (float)($datos['monto_base'] ?? 0.0),
            (float)($datos['monto_descuento'] ?? 0.0),
            (float)($datos['monto_recargo'] ?? 0.0),
            (float)($datos['monto_final'] ?? 0.0),
            (float)($datos['monto_pagado'] ?? 0.0),
            $datos['fecha_vencimiento'] ?? '',
            $datos['estado'] ?? 'PENDIENTE',
            $datos['fecha_anulacion'] ?? null,
            $datos['motivo_anulacion'] ?? null,
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'socio_id' => $this->socioId,
            'periodo' => $this->periodo,
            'monto_base' => $this->montoBase,
            'monto_descuento' => $this->montoDescuento,
            'monto_recargo' => $this->montoRecargo,
            'monto_final' => $this->montoFinal,
            'monto_pagado' => $this->montoPagado,
            'saldo' => $this->getSaldo(),
            'fecha_vencimiento' => $this->fechaVencimiento,
            'estado' => $this->estado,
            'fecha_anulacion' => $this->fechaAnulacion,
            'motivo_anulacion' => $this->motivoAnulacion,
            'creado_en' => $this->creadoEn
        ];
    }
}
