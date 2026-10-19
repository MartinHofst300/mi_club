<?php
namespace Modelos;

/**
 * Entidad Entrenamiento. Clase del diagrama UML correspondiente a una sesion de practica (Regla R9).
 * Capa: Modelos del dominio.
 */
class Entrenamiento
{
    public ?int $id;
    public int $divisionId;
    public int $entrenadorId;
    public string $fecha;
    public string $hora;
    public string $lugar;
    public ?string $observaciones;
    public ?string $creadoEn;

    public function __construct(
        ?int $id = null,
        int $divisionId = 0,
        int $entrenadorId = 0,
        string $fecha = '',
        string $hora = '',
        string $lugar = '',
        ?string $observaciones = null,
        ?string $creadoEn = null
    ) {
        $this->id = $id;
        $this->divisionId = $divisionId;
        $this->entrenadorId = $entrenadorId;
        $this->fecha = $fecha;
        $this->hora = $hora;
        $this->lugar = $lugar;
        $this->observaciones = $observaciones;
        $this->creadoEn = $creadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['division_id'] ?? 0),
            (int)($datos['entrenador_id'] ?? 0),
            $datos['fecha'] ?? '',
            $datos['hora'] ?? '',
            $datos['lugar'] ?? '',
            $datos['observaciones'] ?? null,
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'division_id' => $this->divisionId,
            'entrenador_id' => $this->entrenadorId,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
            'lugar' => $this->lugar,
            'observaciones' => $this->observaciones,
            'creado_en' => $this->creadoEn
        ];
    }
}
