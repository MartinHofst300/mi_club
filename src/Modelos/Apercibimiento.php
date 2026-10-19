<?php
namespace Modelos;

/**
 * Entidad Apercibimiento. Registra llamadas de atencion disciplinarias a los socios del plantel (Regla R10).
 * Capa: Modelos del dominio.
 */
class Apercibimiento
{
    public ?int $id;
    public int $socioId;
    public int $entrenadorId;
    public string $fecha;
    public string $gravedad; // LEVE, GRAVE
    public string $motivo;
    public ?string $creadoEn;

    public function __construct(
        ?int $id = null,
        int $socioId = 0,
        int $entrenadorId = 0,
        string $fecha = '',
        string $gravedad = 'LEVE',
        string $motivo = '',
        ?string $creadoEn = null
    ) {
        $this->id = $id;
        $this->socioId = $socioId;
        $this->entrenadorId = $entrenadorId;
        $this->fecha = $fecha;
        $this->gravedad = $gravedad;
        $this->motivo = $motivo;
        $this->creadoEn = $creadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['socio_id'] ?? 0),
            (int)($datos['entrenador_id'] ?? 0),
            $datos['fecha'] ?? '',
            $datos['gravedad'] ?? 'LEVE',
            $datos['motivo'] ?? '',
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'socio_id' => $this->socioId,
            'entrenador_id' => $this->entrenadorId,
            'fecha' => $this->fecha,
            'gravedad' => $this->gravedad,
            'motivo' => $this->motivo,
            'creado_en' => $this->creadoEn
        ];
    }
}
