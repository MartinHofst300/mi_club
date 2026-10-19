<?php
namespace Modelos;

/**
 * Entidad Rol. Especializacion del Usuario segun el diagrama UML (ADMINISTRADOR, TESORERO, ENTRENADOR, SOCIO).
 * Capa: Modelos del dominio.
 */
class Rol
{
    public ?int $id;
    public string $nombre;
    public ?string $descripcion;

    public function __construct(?int $id = null, string $nombre = '', ?string $descripcion = null)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            $datos['nombre'] ?? '',
            $datos['descripcion'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion
        ];
    }
}
