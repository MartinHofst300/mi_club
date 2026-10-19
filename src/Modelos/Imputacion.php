<?php
namespace Modelos;

/**
 * Entidad Imputacion. Agregada por diseno de persistencia para asociar cada pago a cuotas especificas (Regla R7).
 * Capa: Modelos del dominio.
 */
class Imputacion
{
    public ?int $id;
    public int $pagoId;
    public int $cuotaId;
    public float $montoImputado;
    public ?string $creadoEn;

    public function __construct(
        ?int $id = null,
        int $pagoId = 0,
        int $cuotaId = 0,
        float $montoImputado = 0.0,
        ?string $creadoEn = null
    ) {
        $this->id = $id;
        $this->pagoId = $pagoId;
        $this->cuotaId = $cuotaId;
        $this->montoImputado = $montoImputado;
        $this->creadoEn = $creadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['pago_id'] ?? 0),
            (int)($datos['cuota_id'] ?? 0),
            (float)($datos['monto_imputado'] ?? 0.0),
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'pago_id' => $this->pagoId,
            'cuota_id' => $this->cuotaId,
            'monto_imputado' => $this->montoImputado,
            'creado_en' => $this->creadoEn
        ];
    }
}
