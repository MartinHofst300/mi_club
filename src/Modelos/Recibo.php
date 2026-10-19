<?php
namespace Modelos;

/**
 * Entidad Recibo. Agregada por diseno de persistencia para la emision documental correlativa de pagos (Regla R7).
 * Capa: Modelos del dominio.
 */
class Recibo
{
    public ?int $id;
    public int $pagoId;
    public string $numeroRecibo;
    public string $fechaEmision;
    public float $montoTotal;
    public ?string $creadoEn;

    public function __construct(
        ?int $id = null,
        int $pagoId = 0,
        string $numeroRecibo = '',
        string $fechaEmision = '',
        float $montoTotal = 0.0,
        ?string $creadoEn = null
    ) {
        $this->id = $id;
        $this->pagoId = $pagoId;
        $this->numeroRecibo = $numeroRecibo;
        $this->fechaEmision = $fechaEmision;
        $this->montoTotal = $montoTotal;
        $this->creadoEn = $creadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['pago_id'] ?? 0),
            $datos['numero_recibo'] ?? '',
            $datos['fecha_emision'] ?? date('Y-m-d H:i:s'),
            (float)($datos['monto_total'] ?? 0.0),
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'pago_id' => $this->pagoId,
            'numero_recibo' => $this->numeroRecibo,
            'fecha_emision' => $this->fechaEmision,
            'monto_total' => $this->montoTotal,
            'creado_en' => $this->creadoEn
        ];
    }
}
