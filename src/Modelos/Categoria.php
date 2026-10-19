<?php
namespace Modelos;

/**
 * Entidad Categoria. Modela rangos etarios y tarifa base de cuota mensual (Reglas R3 y R8).
 * Capa: Modelos del dominio.
 */
class Categoria
{
    public ?int $id;
    public string $nombre;
    public ?string $descripcion;
    public int $edadMinima;
    public int $edadMaxima;
    public float $precioCuota;
    public bool $activo;

    public function __construct(
        ?int $id = null,
        string $nombre = '',
        ?string $descripcion = null,
        int $edadMinima = 0,
        int $edadMaxima = 99,
        float $precioCuota = 0.0,
        bool $activo = true
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->edadMinima = $edadMinima;
        $this->edadMaxima = $edadMaxima;
        $this->precioCuota = $precioCuota;
        $this->activo = $activo;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            $datos['nombre'] ?? '',
            $datos['descripcion'] ?? null,
            (int)($datos['edad_minima'] ?? 0),
            (int)($datos['edad_maxima'] ?? 99),
            (float)($datos['precio_cuota'] ?? 0.0),
            (bool)($datos['activo'] ?? true)
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'edad_minima' => $this->edadMinima,
            'edad_maxima' => $this->edadMaxima,
            'precio_cuota' => $this->precioCuota,
            'activo' => $this->activo
        ];
    }
}
