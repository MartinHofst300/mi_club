<?php
namespace Modelos;

/**
 * Entidad Usuario. Representa al actor abstracto del diagrama de clases UML.
 * Capa: Modelos del dominio.
 */
class Usuario
{
    public ?int $id;
    public int $rolId;
    public string $nombre;
    public string $apellido;
    public string $email;
    public string $dni;
    public string $passwordHash;
    public bool $activo;
    public ?string $creadoEn;

    public function __construct(
        ?int $id = null,
        int $rolId = 4,
        string $nombre = '',
        string $apellido = '',
        string $email = '',
        string $dni = '',
        string $passwordHash = '',
        bool $activo = true,
        ?string $creadoEn = null
    ) {
        $this->id = $id;
        $this->rolId = $rolId;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->dni = $dni;
        $this->passwordHash = $passwordHash;
        $this->activo = $activo;
        $this->creadoEn = $creadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            (int)($datos['rol_id'] ?? 4),
            $datos['nombre'] ?? '',
            $datos['apellido'] ?? '',
            $datos['email'] ?? '',
            $datos['dni'] ?? '',
            $datos['password_hash'] ?? '',
            (bool)($datos['activo'] ?? true),
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(bool $incluirHash = false): array
    {
        $arr = [
            'id' => $this->id,
            'rol_id' => $this->rolId,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'email' => $this->email,
            'dni' => $this->dni,
            'activo' => $this->activo,
            'creado_en' => $this->creadoEn
        ];

        if ($incluirHash) {
            $arr['password_hash'] = $this->passwordHash;
        }

        return $arr;
    }
}
