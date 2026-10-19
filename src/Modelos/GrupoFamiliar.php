<?php
namespace Modelos;

/**
 * Entidad GrupoFamiliar. Agregada por diseno de persistencia para soportar la Regla R5 (descuento familiar).
 * Capa: Modelos del dominio.
 */
class GrupoFamiliar
{
    public ?int $id;
    public string $nombre;
    public ?string $creadoEn;

    public function __construct(?int $id = null, string $nombre = '', ?string $creadoEn = null)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->creadoEn = $creadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            $datos['nombre'] ?? '',
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'creado_en' => $this->creadoEn
        ];
    }
}
