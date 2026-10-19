<?php
namespace Modelos;

/**
 * Entidad Socio. Clase principal del diagrama UML con atributos propios.
 * Capa: Modelos del dominio.
 *
 * Estados posibles (Regla R1): ACTIVO, MOROSO, SUSPENDIDO, BAJA.
 */
class Socio
{
    public ?int $id;
    public ?int $usuarioId;
    public ?int $grupoFamiliarId;
    public int $categoriaId;
    public string $numeroSocio;
    public string $nombre;
    public string $apellido;
    public string $dni;
    public string $email;
    public ?string $telefono;
    public ?string $domicilio;
    public string $fechaNacimiento;
    public string $fechaAlta;
    public string $estado;
    public ?string $creadoEn;

    public function __construct(
        ?int $id = null,
        ?int $usuarioId = null,
        ?int $grupoFamiliarId = null,
        int $categoriaId = 1,
        string $numeroSocio = '',
        string $nombre = '',
        string $apellido = '',
        string $dni = '',
        string $email = '',
        ?string $telefono = null,
        ?string $domicilio = null,
        string $fechaNacimiento = '',
        string $fechaAlta = '',
        string $estado = 'ACTIVO',
        ?string $creadoEn = null
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->grupoFamiliarId = $grupoFamiliarId;
        $this->categoriaId = $categoriaId;
        $this->numeroSocio = $numeroSocio;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->dni = $dni;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->domicilio = $domicilio;
        $this->fechaNacimiento = $fechaNacimiento;
        $this->fechaAlta = $fechaAlta;
        $this->estado = $estado;
        $this->creadoEn = $creadoEn;
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int)$datos['id'] : null,
            isset($datos['usuario_id']) && $datos['usuario_id'] !== '' ? (int)$datos['usuario_id'] : null,
            isset($datos['grupo_familiar_id']) && $datos['grupo_familiar_id'] !== '' ? (int)$datos['grupo_familiar_id'] : null,
            (int)($datos['categoria_id'] ?? 1),
            $datos['numero_socio'] ?? '',
            $datos['nombre'] ?? '',
            $datos['apellido'] ?? '',
            $datos['dni'] ?? '',
            $datos['email'] ?? '',
            $datos['telefono'] ?? null,
            $datos['domicilio'] ?? null,
            $datos['fecha_nacimiento'] ?? '',
            $datos['fecha_alta'] ?? '',
            $datos['estado'] ?? 'ACTIVO',
            $datos['creado_en'] ?? null
        );
    }

    public function aArreglo(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'grupo_familiar_id' => $this->grupoFamiliarId,
            'categoria_id' => $this->categoriaId,
            'numero_socio' => $this->numeroSocio,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'dni' => $this->dni,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'domicilio' => $this->domicilio,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'fecha_alta' => $this->fechaAlta,
            'estado' => $this->estado,
            'creado_en' => $this->creadoEn
        ];
    }
}
