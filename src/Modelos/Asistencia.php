<?php
namespace Modelos;

/**
 * Entidad Asistencia. Registra la concurrencia de un socio a un entrenamiento (Regla R9).
 * Capa: Modelos del dominio.
 *
 * Estados validos: PRESENTE, AUSENTE, JUSTIFICADO.
 */
class Asistencia
{
    public ?int $id;
    public int $entrenamientoId;
    public int $socioId;
    public string $estado;
    public ?string $observaciones;

    public function __construct(
        ?int $id = null,
        int $entrenamientoId = 0,
        int $socioId = 0,
        string $estado = 'PRESENTE',
        ?string $observaciones = null
    ) {
        $this->id = $id;
        $this->entrenamientoId = $entrenamientoId;
        $this->socioId = $socioId;
        $this->estado = $estado;
        $this->observaciones = $observaciones;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['entrenamiento_id'] ?? 0),
            (int)($datos['socio_id'] ?? 0),
            $datos['estado'] ?? 'PRESENTE',
            $datos['observaciones'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'entrenamiento_id' => $this->entrenamientoId,
            'socio_id' => $this->socioId,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones
        ];
    }
}
