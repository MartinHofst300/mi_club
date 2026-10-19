<?php
namespace Modelos;

/**
 * Entidad Inscripcion. Agregada por diseno de persistencia para asociar socios a divisiones deportivas.
 * Capa: Modelos del dominio.
 */
class Inscripcion
{
    public ?int $id;
    public int $divisionId;
    public int $socioId;
    public string $fechaInscripcion;
    public bool $activo;

    public function __construct(
        ?int $id = null,
        int $divisionId = 0,
        int $socioId = 0,
        string $fechaInscripcion = '',
        bool $activo = true
    ) {
        $this->id = $id;
        $this->divisionId = $divisionId;
        $this->socioId = $socioId;
        $this->fechaInscripcion = $fechaInscripcion;
        $this->activo = $activo;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['division_id'] ?? 0),
            (int)($datos['socio_id'] ?? 0),
            $datos['fecha_inscripcion'] ?? '',
            (bool)($datos['activo'] ?? true)
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'division_id' => $this->divisionId,
            'socio_id' => $this->socioId,
            'fecha_inscripcion' => $this->fechaInscripcion,
            'activo' => $this->activo
        ];
    }
}
