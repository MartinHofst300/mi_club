<?php
namespace Modelos;

/**
 * Entidad Division. Clase del diagrama UML asignada a una categoria y a cargo de un entrenador (Regla R8).
 * Capa: Modelos del dominio.
 */
class Division
{
    public ?int $id;
    public int $categoriaId;
    public int $entrenadorId;
    public string $nombre;
    public int $cupoMaximo;
    public bool $activo;

    public function __construct(
        ?int $id = null,
        int $categoriaId = 1,
        int $entrenadorId = 1,
        string $nombre = '',
        int $cupoMaximo = 30,
        bool $activo = true
    ) {
        $this->id = $id;
        $this->categoriaId = $categoriaId;
        $this->entrenadorId = $entrenadorId;
        $this->nombre = $nombre;
        $this->cupoMaximo = $cupoMaximo;
        $this->activo = $activo;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['categoria_id'] ?? 1),
            (int)($datos['entrenador_id'] ?? 1),
            $datos['nombre'] ?? '',
            (int)($datos['cupo_maximo'] ?? 30),
            (bool)($datos['activo'] ?? true)
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'categoria_id' => $this->categoriaId,
            'entrenador_id' => $this->entrenadorId,
            'nombre' => $this->nombre,
            'cupo_maximo' => $this->cupoMaximo,
            'activo' => $this->activo
        ];
    }
}
